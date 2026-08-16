<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe directement chaque entrepôt à son marché.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO market (country_code, name, currency_code, active) SELECT 'US', 'États-Unis (stock central)', 'USD', FALSE WHERE NOT EXISTS (SELECT 1 FROM market WHERE country_code = 'US')");
        $this->addSql('ALTER TABLE warehouse ADD market_id INT DEFAULT NULL');
        $this->addSql('UPDATE warehouse SET market_id = market.id FROM market WHERE market.country_code = warehouse.country_code');
        $this->addSql('ALTER TABLE warehouse ALTER market_id SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ECB38BFC622F3F37 ON warehouse (market_id)');
        $this->addSql('ALTER TABLE warehouse ADD CONSTRAINT FK_ECB38BFC622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('DROP INDEX UNIQ_ECB38BFCF026BB7C');
        $this->addSql('ALTER TABLE warehouse DROP country_code');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE warehouse ADD country_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('UPDATE warehouse SET country_code = market.country_code FROM market WHERE market.id = warehouse.market_id');
        $this->addSql('ALTER TABLE warehouse ALTER country_code SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ECB38BFCF026BB7C ON warehouse (country_code)');
        $this->addSql('ALTER TABLE warehouse DROP CONSTRAINT FK_ECB38BFC622F3F37');
        $this->addSql('DROP INDEX UNIQ_ECB38BFC622F3F37');
        $this->addSql('ALTER TABLE warehouse DROP market_id');
        $this->addSql("DELETE FROM market WHERE country_code = 'US' AND name = 'États-Unis (stock central)'");
    }
}
