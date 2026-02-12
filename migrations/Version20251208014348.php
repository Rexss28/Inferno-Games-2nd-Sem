<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208014348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key ADD created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE license_key ADD CONSTRAINT FK_C54B2B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C54B2B03A8386 ON license_key (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key DROP FOREIGN KEY FK_C54B2B03A8386');
        $this->addSql('DROP INDEX IDX_C54B2B03A8386 ON license_key');
        $this->addSql('ALTER TABLE license_key DROP created_by_id');
    }
}
