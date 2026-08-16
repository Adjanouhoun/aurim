<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814130620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order ADD fulfillment_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE customer_order ADD fulfillment_label VARCHAR(160) NOT NULL');
        $this->addSql('ALTER TABLE customer_order ADD fulfillment_address TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_shipping_market_city');
        $this->addSql('ALTER TABLE shipping_rate ADD fulfillment_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE shipping_rate ADD label VARCHAR(160) NOT NULL');
        $this->addSql('ALTER TABLE shipping_rate ADD address_line TEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_shipping_market_option ON shipping_rate (market_id, fulfillment_type, label)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order DROP fulfillment_type');
        $this->addSql('ALTER TABLE customer_order DROP fulfillment_label');
        $this->addSql('ALTER TABLE customer_order DROP fulfillment_address');
        $this->addSql('DROP INDEX uniq_shipping_market_option');
        $this->addSql('ALTER TABLE shipping_rate DROP fulfillment_type');
        $this->addSql('ALTER TABLE shipping_rate DROP label');
        $this->addSql('ALTER TABLE shipping_rate DROP address_line');
        $this->addSql('CREATE UNIQUE INDEX uniq_shipping_market_city ON shipping_rate (market_id, city)');
    }
}
