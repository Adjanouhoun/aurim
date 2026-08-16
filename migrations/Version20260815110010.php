<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815110010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la valeur par défaut du marqueur d’alerte avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movement ALTER triggers_low_stock_alert DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movement ALTER triggers_low_stock_alert SET DEFAULT FALSE');
    }
}
