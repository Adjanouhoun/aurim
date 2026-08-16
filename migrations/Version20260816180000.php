<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les contenus anglais et arabes avec repli automatique vers le français.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_category ADD name_en VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_category ADD name_ar VARCHAR(120) DEFAULT NULL');

        $this->addSql('ALTER TABLE product ADD name_en VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD name_ar VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD type_en VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD type_ar VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD short_description_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD short_description_ar TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD description_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD description_ar TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD benefits_en JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD benefits_ar JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD ingredients_en JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD ingredients_ar JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD usage_instructions_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD usage_instructions_ar TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE shipping_rate ADD label_en VARCHAR(160) DEFAULT NULL');
        $this->addSql('ALTER TABLE shipping_rate ADD label_ar VARCHAR(160) DEFAULT NULL');

        $this->addSql('ALTER TABLE payment_method ADD name_en VARCHAR(160) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD name_ar VARCHAR(160) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD instructions_en TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD instructions_ar TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_category DROP name_en');
        $this->addSql('ALTER TABLE product_category DROP name_ar');

        $this->addSql('ALTER TABLE product DROP name_en');
        $this->addSql('ALTER TABLE product DROP name_ar');
        $this->addSql('ALTER TABLE product DROP type_en');
        $this->addSql('ALTER TABLE product DROP type_ar');
        $this->addSql('ALTER TABLE product DROP short_description_en');
        $this->addSql('ALTER TABLE product DROP short_description_ar');
        $this->addSql('ALTER TABLE product DROP description_en');
        $this->addSql('ALTER TABLE product DROP description_ar');
        $this->addSql('ALTER TABLE product DROP benefits_en');
        $this->addSql('ALTER TABLE product DROP benefits_ar');
        $this->addSql('ALTER TABLE product DROP ingredients_en');
        $this->addSql('ALTER TABLE product DROP ingredients_ar');
        $this->addSql('ALTER TABLE product DROP usage_instructions_en');
        $this->addSql('ALTER TABLE product DROP usage_instructions_ar');

        $this->addSql('ALTER TABLE shipping_rate DROP label_en');
        $this->addSql('ALTER TABLE shipping_rate DROP label_ar');

        $this->addSql('ALTER TABLE payment_method DROP name_en');
        $this->addSql('ALTER TABLE payment_method DROP name_ar');
        $this->addSql('ALTER TABLE payment_method DROP instructions_en');
        $this->addSql('ALTER TABLE payment_method DROP instructions_ar');
    }
}
