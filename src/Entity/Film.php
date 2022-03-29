<?php
declare(strict_types=1);

namespace App\Entity;

use App\Exception\InvalidDateException;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="films",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"import_source", "import_id"})
 *     }
 * )
 */
class Film
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", unique=true, nullable=false)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected Uuid $id;
    /**
     * @ORM\Column(name="title", type="string", nullable=false)
     */
    protected string $title;
    /**
     * @ORM\Column(name="publication_date", type="date", nullable=true)
     */
    protected ?DateTimeInterface $publicationDate = null;
    /**
     * @ORM\Column(name="genres", type="simple_array", nullable=true)
     */
    protected ?array $genres = null;
    /**
     * Duration in minutes
     *
     * @ORM\Column(name="duration", type="smallint", nullable=false)
     */
    protected int $duration = 0;
    /**
     * @ORM\Column(name="producer", type="string", nullable=true)
     */
    protected ?string $producer = null;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Actor", inversedBy="films", cascade={"persist"})
     * @ORM\JoinTable(name="films_actors")
     */
    protected Collection $actors;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Director", inversedBy="films", cascade={"persist"})
     * @ORM\JoinTable(name="films_directors")
     */
    protected Collection $directors;
    /**
     * @ORM\Column(name="import_source", type="string", nullable=true)
     */
    protected ?string $importSource = null;
    /**
     * @ORM\Column(name="import_id", type="string", nullable=true)
     */
    protected ?string $importId = null;
    /**
     * @ORM\Version
     * @ORM\Column(name="version", type="integer")
     */
    protected int $version;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->actors = new ArrayCollection();
        $this->directors = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getPublicationDate(): ?DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?DateTimeInterface $publicationDate): self
    {
        $this->publicationDate = $publicationDate;
        return $this;
    }

    /**
     * @throws InvalidDateException
     */
    public function setPublicationDateFromString(?string $publicationDate): self
    {
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

            $this->publicationDate = $publicationDate;
        } else {
            $this->publicationDate = null;
        }
        return $this;
    }

    public function getGenres(): ?array
    {
        return $this->genres;
    }

    public function setGenres(?array $genres): self
    {
        $this->genres = $genres;
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getProducer(): ?string
    {
        return $this->producer;
    }

    public function setProducer(?string $producer): self
    {
        $this->producer = $producer;
        return $this;
    }

    public function getActors(): ArrayCollection|Collection
    {
        return $this->actors;
    }

    public function setActors(ArrayCollection|Collection $actors): self
    {
        $this->actors = $actors;
        return $this;
    }

    public function addActor(Actor $actor): self
    {
        if (!$this->actors->contains($actor)) {
            $this->actors->add($actor);
        }

        return $this;
    }

    public function removeActor(Actor $actor): self
    {
        if ($this->actors->contains($actor)) {
            $this->actors->removeElement($actor);
        }

        return $this;
    }

    public function mergeActors(iterable $actors): self
    {
        foreach ($actors as $actor) {
            $this->addActor($actor);
        }

        return $this;
    }

    public function getDirectors(): ArrayCollection|Collection
    {
        return $this->directors;
    }

    public function setDirectors(ArrayCollection|Collection $directors): self
    {
        $this->directors = $directors;
        return $this;
    }

    public function addDirector(Director $director): self
    {
        if (!$this->directors->contains($director)) {
            $this->directors->add($director);
        }

        return $this;
    }

    public function removeDirector(Director $director): self
    {
        if ($this->directors->contains($director)) {
            $this->directors->removeElement($director);
        }

        return $this;
    }

    public function mergeDirectors(iterable $directors): self
    {
        foreach ($directors as $director) {
            $this->addDirector($director);
        }

        return $this;
    }

    public function getImportSource(): ?string
    {
        return $this->importSource;
    }

    public function setImportSource(?string $importSource): self
    {
        $this->importSource = $importSource;
        return $this;
    }

    public function getImportId(): ?string
    {
        return $this->importId;
    }

    public function setImportId(?string $importId): self
    {
        $this->importId = $importId;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function __toString(): string
    {
        if (!is_null($this->getPublicationDate())) {
            return sprintf("%s (%s)", $this->getTitle(), $this->getPublicationDate()->format('Y'));
        } else {
            return $this->getTitle();
        }
    }
}
