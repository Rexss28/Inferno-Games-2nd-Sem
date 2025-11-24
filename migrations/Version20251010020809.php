<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010020809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE license_key ADD CONSTRAINT FK_C54B2E48FD905 FOREIGN KEY (game_id) REFERENCES game_management (id)');
        $this->addSql('CREATE INDEX IDX_C54B2E48FD905 ON license_key (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key DROP FOREIGN KEY FK_C54B2E48FD905');
        $this->addSql('DROP INDEX IDX_C54B2E48FD905 ON license_key');
        $this->addSql('ALTER TABLE license_key DROP game_id');
    }
}
