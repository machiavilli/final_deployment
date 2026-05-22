<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Safety net for Railway deploys where api_token migration was skipped or failed.
 */
final class Version20260522130000 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Ensure user.api_token column exists';
    }

    public function up(Schema $schema): void
    {
        $this->addColumnIfNotExists('user', 'api_token', 'api_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->dropColumnIfExists('user', 'api_token');
    }
}
