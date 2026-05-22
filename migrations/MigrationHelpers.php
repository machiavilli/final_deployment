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

    protected function createTableIfNotExists(string $table, string $createTableSql): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $createTableSqlEscaped = str_replace("'", "''", $createTableSql);

        $this->addSql(sprintf(
            "SET @create_tbl_exists := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '%s')",
            $tableEscaped
        ));
        $this->addSql(sprintf(
            "SET @create_tbl_sql := IF(@create_tbl_exists = 0, '%s', 'SELECT 1')",
            $createTableSqlEscaped
        ));
        $this->addSql('PREPARE stmt_create_tbl FROM @create_tbl_sql');
        $this->addSql('EXECUTE stmt_create_tbl');
        $this->addSql('DEALLOCATE PREPARE stmt_create_tbl');
    }

    protected function dropTableIfExists(string $table): void
    {
        $this->addSql(sprintf('DROP TABLE IF EXISTS `%s`', $table));
    }

    protected function addColumnIfNotExists(string $table, string $column, string $columnDefinition): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $columnEscaped = str_replace("'", "''", $column);

        $this->addSql(sprintf(
            "SET @add_col_table_exists := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '%s')",
            $tableEscaped
        ));
        $this->addSql(sprintf(
            "SET @add_col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '%s' AND column_name = '%s')",
            $tableEscaped,
            $columnEscaped
        ));
        $this->addSql(sprintf(
            "SET @add_col_sql := IF(@add_col_table_exists > 0 AND @add_col_exists = 0, 'ALTER TABLE `%s` ADD %s', 'SELECT 1')",
            $table,
            $columnDefinition
        ));
        $this->addSql('PREPARE stmt_add_col FROM @add_col_sql');
        $this->addSql('EXECUTE stmt_add_col');
        $this->addSql('DEALLOCATE PREPARE stmt_add_col');
    }

    protected function addForeignKeyIfReferencedTableExists(
        string $table,
        string $constraintName,
        string $referencedTable,
        string $addConstraintSql,
    ): void {
        $tableEscaped = str_replace("'", "''", $table);
        $constraintEscaped = str_replace("'", "''", $constraintName);
        $refTableEscaped = str_replace("'", "''", $referencedTable);
        $addConstraintSqlEscaped = str_replace("'", "''", $addConstraintSql);

        $this->addSql(sprintf(
            "SET @ref_table_exists := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '%s')",
            $refTableEscaped
        ));
        $this->addSql(sprintf(
            "SET @add_fk_exists := (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = '%s' AND constraint_name = '%s' AND constraint_type = 'FOREIGN KEY')",
            $tableEscaped,
            $constraintEscaped
        ));
        $this->addSql(sprintf(
            "SET @add_fk_sql := IF(@ref_table_exists > 0 AND @add_fk_exists = 0, '%s', 'SELECT 1')",
            $addConstraintSqlEscaped
        ));
        $this->addSql('PREPARE stmt_add_fk FROM @add_fk_sql');
        $this->addSql('EXECUTE stmt_add_fk');
        $this->addSql('DEALLOCATE PREPARE stmt_add_fk');
    }

    protected function createIndexIfNotExists(string $table, string $indexName, string $createIndexSql): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $indexEscaped = str_replace("'", "''", $indexName);
        $createIndexSqlEscaped = str_replace("'", "''", $createIndexSql);

        $this->addSql(sprintf(
            "SET @add_idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = '%s' AND index_name = '%s')",
            $tableEscaped,
            $indexEscaped
        ));
        $this->addSql(sprintf(
            "SET @add_idx_sql := IF(@add_idx_exists = 0, '%s', 'SELECT 1')",
            $createIndexSqlEscaped
        ));
        $this->addSql('PREPARE stmt_add_idx FROM @add_idx_sql');
        $this->addSql('EXECUTE stmt_add_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_add_idx');
    }

    protected function modifyColumnIfExists(string $table, string $column, string $alterSql): void
    {
        $tableEscaped = str_replace("'", "''", $table);
        $columnEscaped = str_replace("'", "''", $column);
        $alterSqlEscaped = str_replace("'", "''", $alterSql);

        $this->addSql(sprintf(
            "SET @mod_col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '%s' AND column_name = '%s')",
            $tableEscaped,
            $columnEscaped
        ));
        $this->addSql(sprintf(
            "SET @mod_col_sql := IF(@mod_col_exists > 0, '%s', 'SELECT 1')",
            $alterSqlEscaped
        ));
        $this->addSql('PREPARE stmt_mod_col FROM @mod_col_sql');
        $this->addSql('EXECUTE stmt_mod_col');
        $this->addSql('DEALLOCATE PREPARE stmt_mod_col');
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
