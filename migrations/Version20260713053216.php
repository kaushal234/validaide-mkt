<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713053216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dataset and reading tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dataset (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, submitted_at DATETIME NOT NULL, mkt DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reading (id INT AUTO_INCREMENT NOT NULL, recorded_at DATETIME NOT NULL, temperature DOUBLE PRECISION NOT NULL, dataset_id INT NOT NULL, INDEX IDX_C11AFC41D47C2D1B (dataset_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reading ADD CONSTRAINT FK_C11AFC41D47C2D1B FOREIGN KEY (dataset_id) REFERENCES dataset (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reading DROP FOREIGN KEY FK_C11AFC41D47C2D1B');
        $this->addSql('DROP TABLE dataset');
        $this->addSql('DROP TABLE reading');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
