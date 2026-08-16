<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Marque les mouvements qui déclenchent une alerte de stock faible.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movement ADD triggers_low_stock_alert BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_movement DROP triggers_low_stock_alert');
    }
}
