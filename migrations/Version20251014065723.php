<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds product.category_id — idempotent; skips FK until category table exists (Version20260407163750).
 */
final class Version20251014065723 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Add product.category_id with optional FK to category (safe when category table not created yet)';
    }

    public function up(Schema $schema): void
    {
        $this->addColumnIfNotExists('product', 'category_id', 'category_id INT DEFAULT NULL');

        // category table is created later; do not fail if it is missing
        $this->addForeignKeyIfReferencedTableExists(
            'product',
            'FK_D34A04AD12469DE2',
            'category',
            'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)',
        );

        $this->createIndexIfNotExists(
            'product',
            'IDX_D34A04AD12469DE2',
            'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeyIfExists('product', 'FK_D34A04AD12469DE2');
        $this->dropIndexIfExists('product', 'IDX_D34A04AD12469DE2');
        $this->dropColumnIfExists('product', 'category_id');
    }
}
