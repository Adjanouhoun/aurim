<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814170010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne le nom de l’index unique des catégories avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX UNIQ_1E71339D989D9B62 RENAME TO UNIQ_CDFC7356989D9B62');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX UNIQ_CDFC7356989D9B62 RENAME TO UNIQ_1E71339D989D9B62');
    }
}
