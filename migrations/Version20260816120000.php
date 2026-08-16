<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe les administrateurs locaux à leur marché et conserve les comptes existants comme super-administrateurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD market_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E94B2A4AC3 FOREIGN KEY (market_id) REFERENCES market (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_88BDF3E9622F3F37 ON app_user (market_id)');
        $this->addSql(<<<'SQL'
            UPDATE app_user
            SET roles = '["ROLE_SUPER_ADMIN"]'
            WHERE market_id IS NULL AND roles::text LIKE '%ROLE_ADMIN%'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E94B2A4AC3');
        $this->addSql('DROP INDEX IDX_88BDF3E9622F3F37');
        $this->addSql('ALTER TABLE app_user DROP market_id');
    }
}
