<?php

namespace App\Command;

use App\Exception\InvalidDateException;
use App\Message\ImportMovieCsvMessage;
use App\Util\MovieImporter;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:import-movies-csv',
    description: 'Add a short description for your command',
)]
class ImportMoviesCsvCommand extends Command
{
    protected const DEFAULT_FILENAME = 'movies.csv';

    protected const IMPORT_ID_COLUMN_NAME = 'imdb_title_id';
    protected const TITLE_COLUMN_NAME = 'title';
    protected const DATE_PUBLISHED_COLUMN_NAME = 'date_published';
    protected const GENRE_COLUMN_NAME = 'genre';
    protected const DURATION_COLUMN_NAME = 'duration';
    protected const PRODUCER_COLUMN_NAME = 'production_company';
    protected const DIRECTOR_COLUMN_NAME = 'director';
    protected const ACTORS_COLUMN_NAME = 'actors';
    public const IMPORT_SOURCE = 'IMDB';

    protected LoggerInterface $logger;
    protected FilesystemOperator $storage;
    protected EntityManagerInterface $em;

    protected array $filmsForRelations;

    public function __construct(
        LoggerInterface        $logger,
        FilesystemOperator     $defaultStorage,
        EntityManagerInterface $em,
    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->storage = $defaultStorage;

        $this->filmsForRelations = [];

        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'filename',
                mode: InputArgument::OPTIONAL,
                description: sprintf('Filename to import. Default: "%s"', self::DEFAULT_FILENAME),
                default: self::DEFAULT_FILENAME
            )
            ->addOption(
                name: 'em-batch-size',
                mode: InputOption::VALUE_REQUIRED,
                description: 'EM batch size',
                default: MovieImporter::DEFAULT_EM_BATCH_SIZE
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $filename = $input->getArgument('filename');
            $emBatchSize = $input->getOption('em-batch-size');

            if (!$this->storage->fileExists($filename)) {
                throw new FileNotFoundException(sprintf('File "%s" not found', $filename));
            }

            $this->logger->info('Processing file', ['file' => $filename]);

            // create the reader
            $reader = Reader::createFromStream($this->storage->readStream($filename));
            $reader->setHeaderOffset(0);

            // insert records
            $this->em->beginTransaction();
            $this->processRecords($reader->getRecords(), $emBatchSize);
            $this->em->commit();

            // update relationships
            $this->em->beginTransaction();
            $this->handleFilmRelationships($emBatchSize);
            $this->em->commit();

            return Command::SUCCESS;
        } catch (FilesystemException|FileNotFoundException|\Throwable $e) {
            $this->logger->critical($e->getMessage(), ['errorFile' => $e->getFile(), 'errorLine' => $e->getLine()]);
            return Command::FAILURE;
        }
    }

    protected function processRecords(\Iterator $records, int $emBatchSize)
    {
        $this->logger->info('Procesing records');

        $filmInsertSql = "INSERT INTO films (title, publication_date, genres, duration, producer, import_source, import_id) VALUES %s ON CONFLICT DO NOTHING";
        $actorInsertSql = "INSERT INTO actors (full_name) VALUES %s ON CONFLICT DO NOTHING";
        $directorInsertSql = "INSERT INTO directors (full_name) VALUES %s ON CONFLICT DO NOTHING";
        $filmValues = [];
        $actorValues = [];
        $directorValues = [];

        $i = 0;
        foreach ($records as $record) {
            $id = trim($record[self::IMPORT_ID_COLUMN_NAME]);
            $filmForRelation = [
                'id' => $id
            ];
            $filmValues = array_merge($filmValues, [
                trim($record[self::TITLE_COLUMN_NAME]),
                $this->parsePublicationDate(trim($record[self::DATE_PUBLISHED_COLUMN_NAME])),
                join(',', array_map('trim', explode(',', $record[self::GENRE_COLUMN_NAME]))),
                $record[self::DURATION_COLUMN_NAME] ?? 0,
                trim($record[self::PRODUCER_COLUMN_NAME]),
                self::IMPORT_SOURCE,
                $id
            ]);

            foreach (explode(',', $record[self::DIRECTOR_COLUMN_NAME]) as $director) {
                $director = trim($director);
                $directorValues[] = $director;
                $filmForRelation['directors'][] = $director;
            }

            foreach (explode(',', $record[self::ACTORS_COLUMN_NAME]) as $actor) {
                $actor = trim($actor);
                $actorValues[] = $actor;
                $filmForRelation['actors'][] = $actor;
            }

            $this->filmsForRelations[] = $filmForRelation;
            $i++;

            if (($i % $emBatchSize) === 0) {
                $this->em->getConnection()->executeStatement(sprintf($filmInsertSql, join(',', array_fill(0, $i, '(?,?,?,?,?,?,?)'))), $filmValues);
                $this->em->getConnection()->executeStatement(sprintf($actorInsertSql, join(',', array_fill(0, count($actorValues), '(?)'))), $actorValues);
                $this->em->getConnection()->executeStatement(sprintf($directorInsertSql, join(',', array_fill(0, count($directorValues), '(?)'))), $directorValues);

                $filmValues = [];
                $actorValues = [];
                $directorValues = [];
                $i = 0;
            }
        }
    }

    protected function parsePublicationDate(string $publicationDate): ?string
    {
        try {
            if (!empty($publicationDate)) {
                if (substr_count($publicationDate, '-') === 2) {
                    $dateFormat = 'Y-m-d';
                } else {
                    $dateFormat = 'Y';
                }

                $publicationDate = \DateTime::createFromFormat($dateFormat, $publicationDate);

                if (!$publicationDate instanceof \DateTimeInterface) {
                    throw new InvalidDateException();
                }

                return $publicationDate->format('Y-m-d');
            } else {
                return null;
            }
        } catch (InvalidDateException $e) {
            return null;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    protected function handleFilmRelationships(int $emBatchSize)
    {
        $this->logger->info('Processing film relationships');
        $actorRelationBaseSql = "INSERT INTO films_actors (film_id, actor_id) %s ON CONFLICT DO NOTHING";
        $actorRelationPart = "SELECT f.id,a.id FROM films f FULL OUTER JOIN actors a ON a.full_name IN (%s) WHERE import_id=? AND import_source=?";
        $actorRelationParts = [];
        $actorRelationValues = [];

        $directorRelationBaseSql = "INSERT INTO films_directors (film_id, director_id) %s ON CONFLICT DO NOTHING";
        $directorRelationPart = "SELECT f.id,d.id FROM films f FULL OUTER JOIN directors d ON d.full_name IN (%s) WHERE import_id=? AND import_source=?";
        $directorRelationParts = [];
        $directorRelationValues = [];

        foreach ($this->filmsForRelations as $key => $filmForRelation) {
            if (count($filmForRelation['actors']) > 0) {
                $actorRelationParts[] = sprintf($actorRelationPart, str_repeat('?,', count($filmForRelation['actors']) - 1) . '?');
                $actorRelationValues = array_merge($actorRelationValues, $filmForRelation['actors'], [$filmForRelation['id'], self::IMPORT_SOURCE]);
            }
            if (count($filmForRelation['directors']) > 0) {
                $directorRelationParts[] = sprintf($directorRelationPart, str_repeat('?,', count($filmForRelation['directors']) - 1) . '?');
                $directorRelationValues = array_merge($directorRelationValues, $filmForRelation['directors'], [$filmForRelation['id'], self::IMPORT_SOURCE]);
            }

            if (($key % $emBatchSize) === 0) {
                $this->em->getConnection()->executeStatement(sprintf($actorRelationBaseSql, join(' UNION ALL ', $actorRelationParts)), $actorRelationValues);
                $actorRelationParts = [];
                $actorRelationValues = [];

                $this->em->getConnection()->executeStatement(sprintf($directorRelationBaseSql, join(' UNION ALL ', $directorRelationParts)), $directorRelationValues);
                $directorRelationParts = [];
                $directorRelationValues = [];
            }
            unset($this->filmsForRelations[$key]);
        }
    }
}
