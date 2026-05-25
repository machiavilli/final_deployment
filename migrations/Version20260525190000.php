<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Railway legacy DB: product table may use product_name instead of name (Version20251003035956).
 */
final class Version20260525190000 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Align product table with Product entity (rename product_name to name, ensure stock/FKs)';
    }

    public function up(Schema $schema): void
    {
        $this->createTableIfNotExists(
            'product',
            'CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) NOT NULL, stock INT NOT NULL DEFAULT 0, INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04ADB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
        );

        $this->renameColumnIfExists('product', 'product_name', 'name', 'name VARCHAR(255) NOT NULL');
        $this->addColumnIfNotExists('product', 'name', "name VARCHAR(255) NOT NULL DEFAULT ''");
        $this->copyColumnIfBothExist('product', 'product_name', 'name');
        $this->dropColumnIfExists('product', 'product_name');

        $this->addColumnIfNotExists('product', 'description', 'description LONGTEXT NOT NULL');
        $this->addColumnIfNotExists('product', 'price', 'price DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addColumnIfNotExists('product', 'image', "image VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfNotExists('product', 'stock', 'stock INT NOT NULL DEFAULT 0');
        $this->addColumnIfNotExists('product', 'category_id', 'category_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('product', 'created_by_id', 'created_by_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->dropColumnIfExists('product', 'created_by_id');
        $this->dropColumnIfExists('product', 'category_id');
        $this->dropColumnIfExists('product', 'stock');
    }
}
