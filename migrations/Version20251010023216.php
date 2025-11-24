<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010023216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key ADD orders_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE license_key ADD CONSTRAINT FK_C54B2CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_C54B2CFFE9AD6 ON license_key (orders_id)');
        $this->addSql('ALTER TABLE `order` ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398E48FD905 FOREIGN KEY (game_id) REFERENCES game_management (id)');
        $this->addSql('CREATE INDEX IDX_F5299398E48FD905 ON `order` (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398E48FD905');
        $this->addSql('DROP INDEX IDX_F5299398E48FD905 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP game_id');
        $this->addSql('ALTER TABLE license_key DROP FOREIGN KEY FK_C54B2CFFE9AD6');
        $this->addSql('DROP INDEX IDX_C54B2CFFE9AD6 ON license_key');
        $this->addSql('ALTER TABLE license_key DROP orders_id');
    }
}
