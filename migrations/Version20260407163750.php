<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Consolidated schema snapshot — idempotent: skips tables/FKs that earlier migrations already created.
 */
final class Version20260407163750 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Ensure core tables and foreign keys exist (idempotent for Railway incremental migrations)';
    }

    public function up(Schema $schema): void
    {
        $this->createTableIfNotExists('activity_log', 'CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(255) NOT NULL, entity_type VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, affected_data LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, timestamp DATETIME NOT NULL, ip_address VARCHAR(255) DEFAULT NULL, INDEX IDX_FD06F647A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('category', 'CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_64C19C1B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('customer', 'CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, INDEX IDX_81398E09B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('order', 'CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, product_name VARCHAR(255) NOT NULL, quantity DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, order_date DATETIME NOT NULL, INDEX IDX_F52993989395C3F3 (customer_id), INDEX IDX_F5299398B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('product', 'CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) NOT NULL, stock INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04ADB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('user', 'CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('messenger_messages', 'CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Align legacy tables (created by earlier migrations) with expected columns
        $this->addColumnIfNotExists('order', 'customer_id', 'customer_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('order', 'created_by_id', 'created_by_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('category', 'created_by_id', 'created_by_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('customer', 'created_by_id', 'created_by_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('product', 'created_by_id', 'created_by_id INT DEFAULT NULL');
        $this->addColumnIfNotExists('product', 'category_id', 'category_id INT DEFAULT NULL');

        $this->createIndexIfNotExists('category', 'IDX_64C19C1B03A8386', 'CREATE INDEX IDX_64C19C1B03A8386 ON category (created_by_id)');
        $this->createIndexIfNotExists('customer', 'IDX_81398E09B03A8386', 'CREATE INDEX IDX_81398E09B03A8386 ON customer (created_by_id)');
        $this->createIndexIfNotExists('order', 'IDX_F52993989395C3F3', 'CREATE INDEX IDX_F52993989395C3F3 ON `order` (customer_id)');
        $this->createIndexIfNotExists('order', 'IDX_F5299398B03A8386', 'CREATE INDEX IDX_F5299398B03A8386 ON `order` (created_by_id)');
        $this->createIndexIfNotExists('product', 'IDX_D34A04AD12469DE2', 'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->createIndexIfNotExists('product', 'IDX_D34A04ADB03A8386', 'CREATE INDEX IDX_D34A04ADB03A8386 ON product (created_by_id)');

        $this->addForeignKeyIfReferencedTableExists(
            'activity_log',
            'FK_FD06F647A76ED395',
            'user',
            'ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)',
            'user_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'category',
            'FK_64C19C1B03A8386',
            'user',
            'ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)',
            'created_by_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'customer',
            'FK_81398E09B03A8386',
            'user',
            'ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)',
            'created_by_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'order',
            'FK_F52993989395C3F3',
            'customer',
            'ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)',
            'customer_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'order',
            'FK_F5299398B03A8386',
            'user',
            'ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)',
            'created_by_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'product',
            'FK_D34A04AD12469DE2',
            'category',
            'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)',
            'category_id',
        );
        $this->addForeignKeyIfReferencedTableExists(
            'product',
            'FK_D34A04ADB03A8386',
            'user',
            'ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)',
            'created_by_id',
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeyIfExists('product', 'FK_D34A04ADB03A8386');
        $this->dropForeignKeyIfExists('product', 'FK_D34A04AD12469DE2');
        $this->dropForeignKeyIfExists('order', 'FK_F5299398B03A8386');
        $this->dropForeignKeyIfExists('order', 'FK_F52993989395C3F3');
        $this->dropForeignKeyIfExists('customer', 'FK_81398E09B03A8386');
        $this->dropForeignKeyIfExists('category', 'FK_64C19C1B03A8386');
        $this->dropForeignKeyIfExists('activity_log', 'FK_FD06F647A76ED395');

        $this->dropTableIfExists('messenger_messages');
        $this->dropTableIfExists('user');
        $this->dropTableIfExists('product');
        $this->dropTableIfExists('order');
        $this->dropTableIfExists('customer');
        $this->dropTableIfExists('category');
        $this->dropTableIfExists('activity_log');
    }
}
