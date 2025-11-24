<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010014616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_management ADD stock_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_management ADD CONSTRAINT FK_E59252A2DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E59252A2DCD6110 ON game_management (stock_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_management DROP FOREIGN KEY FK_E59252A2DCD6110');
        $this->addSql('DROP INDEX UNIQ_E59252A2DCD6110 ON game_management');
        $this->addSql('ALTER TABLE game_management DROP stock_id');
    }
}
