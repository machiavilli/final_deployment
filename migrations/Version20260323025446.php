<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323025446 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Add email verification fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addColumnIfNotExists('user', 'is_verified', 'is_verified TINYINT(1) NOT NULL DEFAULT 0');
        $this->addColumnIfNotExists('user', 'verification_token', 'verification_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP verification_token');
    }
}
