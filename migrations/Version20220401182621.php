<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220401182621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create actor, director and film tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE actors (id UUID NOT NULL DEFAULT uuid_generate_v4(), full_name VARCHAR(255) NOT NULL, death_date DATE DEFAULT NULL, birthplace VARCHAR(255) DEFAULT NULL, birthday DATE DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DF2BF0E5DBC463C4 ON actors (full_name)');
        $this->addSql('COMMENT ON COLUMN actors.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE directors (id UUID NOT NULL DEFAULT uuid_generate_v4(), full_name VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A6ADADC4DBC463C4 ON directors (full_name)');
        $this->addSql('COMMENT ON COLUMN directors.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE films (id UUID NOT NULL DEFAULT uuid_generate_v4(), title VARCHAR(255) NOT NULL, publication_date DATE DEFAULT NULL, genres TEXT DEFAULT NULL, duration SMALLINT NOT NULL, producer VARCHAR(255) DEFAULT NULL, import_source VARCHAR(255) DEFAULT NULL, import_id VARCHAR(255) DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CEECCA5123BBC56BB6A263D9 ON films (import_source, import_id)');
        $this->addSql('COMMENT ON COLUMN films.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN films.genres IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE films_actors (film_id UUID NOT NULL, actor_id UUID NOT NULL, PRIMARY KEY(film_id, actor_id))');
        $this->addSql('CREATE INDEX IDX_687F7B5C567F5183 ON films_actors (film_id)');
        $this->addSql('CREATE INDEX IDX_687F7B5C10DAF24A ON films_actors (actor_id)');
        $this->addSql('COMMENT ON COLUMN films_actors.film_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN films_actors.actor_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE films_directors (film_id UUID NOT NULL, director_id UUID NOT NULL, PRIMARY KEY(film_id, director_id))');
        $this->addSql('CREATE INDEX IDX_D853BC81567F5183 ON films_directors (film_id)');
        $this->addSql('CREATE INDEX IDX_D853BC81899FB366 ON films_directors (director_id)');
        $this->addSql('COMMENT ON COLUMN films_directors.film_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN films_directors.director_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE films_actors ADD CONSTRAINT FK_687F7B5C567F5183 FOREIGN KEY (film_id) REFERENCES films (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE films_actors ADD CONSTRAINT FK_687F7B5C10DAF24A FOREIGN KEY (actor_id) REFERENCES actors (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE films_directors ADD CONSTRAINT FK_D853BC81567F5183 FOREIGN KEY (film_id) REFERENCES films (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE films_directors ADD CONSTRAINT FK_D853BC81899FB366 FOREIGN KEY (director_id) REFERENCES directors (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE films_actors DROP CONSTRAINT FK_687F7B5C10DAF24A');
        $this->addSql('ALTER TABLE films_directors DROP CONSTRAINT FK_D853BC81899FB366');
        $this->addSql('ALTER TABLE films_actors DROP CONSTRAINT FK_687F7B5C567F5183');
        $this->addSql('ALTER TABLE films_directors DROP CONSTRAINT FK_D853BC81567F5183');
        $this->addSql('DROP TABLE actors');
        $this->addSql('DROP TABLE directors');
        $this->addSql('DROP TABLE films');
        $this->addSql('DROP TABLE films_actors');
        $this->addSql('DROP TABLE films_directors');
    }
}
