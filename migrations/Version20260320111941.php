<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Customer columns + product.stock tweak — idempotent for Railway fresh schema.
 *
 * stock column is added in Version20260320120000; only MODIFY here if it already exists.
 */
final class Version20260320111941 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Extend customer table and normalize product.stock when present (idempotent)';
    }

    public function up(Schema $schema): void
    {
        $this->addColumnIfNotExists('customer', 'created_by_id', 'created_by_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('customer', 'phone', 'phone VARCHAR(255) DEFAULT NULL');
        $this->addColumnIfNotExists('customer', 'username', "username VARCHAR(255) NOT NULL DEFAULT ''");

        $this->addForeignKeyIfReferencedTableExists(
            'customer',
            'FK_81398E09B03A8386',
            'user',
            'ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)',
        );

        $this->createIndexIfNotExists(
            'customer',
            'IDX_81398E09B03A8386',
            'CREATE INDEX IDX_81398E09B03A8386 ON customer (created_by_id)',
        );

        // Version20260320120000 adds stock; skip CHANGE if column not created yet
        $this->modifyColumnIfExists('product', 'stock', 'ALTER TABLE product CHANGE stock stock INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->modifyColumnIfExists('product', 'stock', 'ALTER TABLE product CHANGE stock stock INT DEFAULT 0 NOT NULL');

        $this->dropForeignKeyIfExists('customer', 'FK_81398E09B03A8386');
        $this->dropIndexIfExists('customer', 'IDX_81398E09B03A8386');
        $this->dropColumnIfExists('customer', 'username');
        $this->dropColumnIfExists('customer', 'phone');
        $this->dropColumnIfExists('customer', 'created_by_id');
    }
}
