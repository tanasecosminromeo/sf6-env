<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903075326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jobs CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE query query VARCHAR(255) DEFAULT NULL, CHANGE message_id message_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', CHANGE sent_at sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jobs CHANGE location location VARCHAR(255) NOT NULL, CHANGE query query VARCHAR(255) NOT NULL, CHANGE message_id message_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE sent_at sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resolved_at resolved_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
