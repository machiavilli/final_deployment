<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414094554 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Create cart tables if missing (idempotent)';
    }

    public function up(Schema $schema): void
    {
        $this->createTableIfNotExists('cart', 'CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, total DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_BA388B7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->createTableIfNotExists('cart_item', 'CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, cart_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), INDEX IDX_F0FE25274584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addForeignKeyIfReferencedTableExists(
            'cart',
            'FK_BA388B7A76ED395',
            'user',
            'ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'cart_item',
            'FK_F0FE25271AD5CDBF',
            'cart',
            'ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'cart_item',
            'FK_F0FE25274584665A',
            'product',
            'ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeyIfExists('cart_item', 'FK_F0FE25274584665A');
        $this->dropForeignKeyIfExists('cart_item', 'FK_F0FE25271AD5CDBF');
        $this->dropForeignKeyIfExists('cart', 'FK_BA388B7A76ED395');
        $this->dropTableIfExists('cart_item');
        $this->dropTableIfExists('cart');
    }
}
