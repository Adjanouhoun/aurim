<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la référence SKU unique au produit.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD sku VARCHAR(40) DEFAULT NULL');
        $this->addSql("UPDATE product SET sku = CASE slug WHEN 'radiance-boosting-body-scrub' THEN 'TVCNRBBS450' WHEN 'radiant-body-butter' THEN 'TVCNRBB350' ELSE CONCAT('AURIM-', id) END");
        $this->addSql('ALTER TABLE product ALTER sku SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04ADF9038C4 ON product (sku)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D34A04ADF9038C4');
        $this->addSql('ALTER TABLE product DROP sku');
    }
}
