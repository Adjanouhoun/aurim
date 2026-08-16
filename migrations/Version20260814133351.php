<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814133351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD payer_phone VARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE payment_method ADD recipient_account VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD account_holder VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP payer_phone');
        $this->addSql('ALTER TABLE payment_method DROP recipient_account');
        $this->addSql('ALTER TABLE payment_method DROP account_holder');
    }
}
