<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320120000 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Add product.stock column if missing (idempotent)';
    }

    public function up(Schema $schema): void
    {
        $this->addColumnIfNotExists('product', 'stock', 'stock INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->dropColumnIfExists('product', 'stock');
    }
}
