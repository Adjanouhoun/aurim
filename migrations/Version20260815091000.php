<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815091000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les noms des index de transferts de stock avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX uniq_stock_transfer_reference RENAME TO UNIQ_FF2F782AEA34913');
        $this->addSql('ALTER INDEX idx_stock_transfer_source RENAME TO IDX_FF2F782866A3066');
        $this->addSql('ALTER INDEX idx_stock_transfer_destination RENAME TO IDX_FF2F782E1AE3711');
        $this->addSql('ALTER INDEX idx_stock_transfer_item_transfer RENAME TO IDX_A37123988CBEEE9B');
        $this->addSql('ALTER INDEX idx_stock_transfer_item_product RENAME TO IDX_A37123984584665A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX uniq_ff2f782aea34913 RENAME TO uniq_stock_transfer_reference');
        $this->addSql('ALTER INDEX idx_ff2f782866a3066 RENAME TO idx_stock_transfer_source');
        $this->addSql('ALTER INDEX idx_ff2f782e1ae3711 RENAME TO idx_stock_transfer_destination');
        $this->addSql('ALTER INDEX idx_a37123988cbeee9b RENAME TO idx_stock_transfer_item_transfer');
        $this->addSql('ALTER INDEX idx_a37123984584665a RENAME TO idx_stock_transfer_item_product');
    }
}
