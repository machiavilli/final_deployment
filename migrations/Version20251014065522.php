<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Legacy schema cleanup — made idempotent for fresh Railway DBs and partial runs.
 *
 * Original intent: drop order→customer FK before dropping an old customer table,
 * then remove product.category_id. On new databases the FK often never existed
 * (order was created without customer_id in Version20251014022329).
 */
final class Version20251014065522 extends AbstractMigration
{
    use MigrationHelpers;

    public function getDescription(): string
    {
        return 'Idempotent cleanup of legacy customer FK/table and product category (safe for Railway)';
    }

    public function up(Schema $schema): void
    {
        // FK_F52993989395C3F3 = Doctrine default name for order.customer_id → customer.id
        $this->dropForeignKeyIfExists('order', 'FK_F52993989395C3F3');

        // Old customer table from an earlier schema iteration; later migrations recreate it
        $this->dropTableIfExists('customer');

        // Product category link — only drop if present
        $this->dropForeignKeyIfExists('product', 'FK_D34A04AD12469DE2');
        $this->dropIndexIfExists('product', 'IDX_D34A04AD12469DE2');
        $this->dropColumnIfExists('product', 'category_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone DOUBLE PRECISION NOT NULL, address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('SET @product_cat_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = \'product\' AND column_name = \'category_id\')');
        $this->addSql('SET @product_cat_sql := IF(@product_cat_exists = 0, \'ALTER TABLE product ADD category_id INT DEFAULT NULL\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt_product_cat FROM @product_cat_sql');
        $this->addSql('EXECUTE stmt_product_cat');
        $this->addSql('DEALLOCATE PREPARE stmt_product_cat');

        $this->addSql('SET @product_fk_exists := (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = \'product\' AND constraint_name = \'FK_D34A04AD12469DE2\')');
        $this->addSql('SET @product_fk_sql := IF(@product_fk_exists = 0, \'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt_product_fk FROM @product_fk_sql');
        $this->addSql('EXECUTE stmt_product_fk');
        $this->addSql('DEALLOCATE PREPARE stmt_product_fk');

        $this->addSql('SET @product_idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = \'product\' AND index_name = \'IDX_D34A04AD12469DE2\')');
        $this->addSql('SET @product_idx_sql := IF(@product_idx_exists = 0, \'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt_product_idx FROM @product_idx_sql');
        $this->addSql('EXECUTE stmt_product_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_product_idx');
    }
}
