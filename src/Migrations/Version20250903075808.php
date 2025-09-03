<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903075808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // This migration is on purpose amended to showcase how this column can be added as not null but when the table already has existing data

        // First add the column as nullable
        $this->addSql('ALTER TABLE jobs ADD created_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Update existing rows with the current timestamp
        $this->addSql('UPDATE jobs SET created_at = NOW() WHERE created_at IS NULL');
        
        // Then make it NOT NULL if needed
        $this->addSql('ALTER TABLE jobs MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jobs DROP created_at');
    }
}
