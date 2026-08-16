<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mémorise la langue du client sur chaque commande pour localiser les e-mails.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE customer_order ADD locale VARCHAR(2) DEFAULT 'fr' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP locale');
    }
}
