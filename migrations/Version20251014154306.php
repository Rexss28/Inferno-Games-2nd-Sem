<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014154306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key DROP FOREIGN KEY FK_C54B2CFFE9AD6');
        $this->addSql('DROP INDEX IDX_C54B2CFFE9AD6 ON license_key');
        $this->addSql('ALTER TABLE license_key CHANGE orders_id order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE license_key ADD CONSTRAINT FK_C54B28D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C54B28D9F6D38 ON license_key (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_key DROP FOREIGN KEY FK_C54B28D9F6D38');
        $this->addSql('DROP INDEX IDX_C54B28D9F6D38 ON license_key');
        $this->addSql('ALTER TABLE license_key CHANGE order_id orders_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE license_key ADD CONSTRAINT FK_C54B2CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C54B2CFFE9AD6 ON license_key (orders_id)');
    }
}
