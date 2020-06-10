<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200610092032 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE conference ADD slug VARCHAR(255)');
        $this->addSql("UPDATE conference SET slug=CONCAT(LOWER(city), '-', year)");
        $this->addSql('ALTER TABLE conference ALTER slug SET NOT NULL');
        $this->addSql('ALTER TABLE conference ALTER year TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE conference ALTER year DROP DEFAULT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE conference DROP slug');
        $this->addSql('ALTER TABLE conference ALTER year TYPE INT');
        $this->addSql('ALTER TABLE conference ALTER year DROP DEFAULT');
    }
}
