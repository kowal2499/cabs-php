<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240324140044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE driver_fee ALTER min TYPE INT');

        $this->addSql('COMMENT ON COLUMN driver_fee.min IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN transit.price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN transit.estimated_price IS \'(DC2Type:money)\'');
        $this->addSql('COMMENT ON COLUMN transit.drivers_fee IS \'(DC2Type:money)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('COMMENT ON COLUMN transit.price IS NULL');
        $this->addSql('COMMENT ON COLUMN transit.estimated_price IS NULL');
        $this->addSql('COMMENT ON COLUMN transit.drivers_fee IS NULL');
        $this->addSql('COMMENT ON COLUMN driver_fee.min IS NULL');
    }
}
