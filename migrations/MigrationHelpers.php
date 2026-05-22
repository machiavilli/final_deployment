<?php

declare(strict_types=1);

namespace DoctrineMigrations;

/**
 * Shared helpers for idempotent MySQL migrations (Railway / partial deploy safe).
 */
trait MigrationHelpers
{
    protected function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $constraintEscaped = str_replace("'", "''", $constraintName);

        $this->addSql(sprintf(
            "SET @fk_exists := (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = '%s' AND constraint_name = '%s' AND constraint_type = 'FOREIGN KEY')",
            $tableEscaped,
            $constraintEscaped
        ));
        $this->addSql(sprintf(
            "SET @fk_sql := IF(@fk_exists > 0, 'ALTER TABLE `%s` DROP FOREIGN KEY %s', 'SELECT 1')",
            $table,
            $constraintName
        ));
        $this->addSql('PREPARE stmt_drop_fk FROM @fk_sql');
        $this->addSql('EXECUTE stmt_drop_fk');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_fk');
    }

    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $indexEscaped = str_replace("'", "''", $indexName);

        $this->addSql(sprintf(
            "SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = '%s' AND index_name = '%s')",
            $tableEscaped,
            $indexEscaped
        ));
        $this->addSql(sprintf(
            "SET @idx_sql := IF(@idx_exists > 0, 'DROP INDEX %s ON `%s`', 'SELECT 1')",
            $indexName,
            $table
        ));
        $this->addSql('PREPARE stmt_drop_idx FROM @idx_sql');
        $this->addSql('EXECUTE stmt_drop_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_idx');
    }

    protected function dropTableIfExists(string $table): void
    {
        $this->addSql(sprintf('DROP TABLE IF EXISTS `%s`', $table));
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $columnEscaped = str_replace("'", "''", $column);

        $this->addSql(sprintf(
            "SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '%s' AND column_name = '%s')",
            $tableEscaped,
            $columnEscaped
        ));
        $this->addSql(sprintf(
            "SET @col_sql := IF(@col_exists > 0, 'ALTER TABLE `%s` DROP COLUMN %s', 'SELECT 1')",
            $table,
            $column
        ));
        $this->addSql('PREPARE stmt_drop_col FROM @col_sql');
        $this->addSql('EXECUTE stmt_drop_col');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_col');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
