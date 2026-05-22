<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add checkout and payment fields to orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD order_number VARCHAR(32) DEFAULT NULL, ADD payment_method VARCHAR(32) DEFAULT NULL, ADD payment_status VARCHAR(32) DEFAULT NULL, ADD order_source VARCHAR(20) DEFAULT \'manual\' NOT NULL, ADD shipping_full_name VARCHAR(255) DEFAULT NULL, ADD shipping_phone VARCHAR(64) DEFAULT NULL, ADD shipping_address LONGTEXT DEFAULT NULL, ADD shipping_city VARCHAR(128) DEFAULT NULL, ADD shipping_postal_code VARCHAR(32) DEFAULT NULL, ADD order_notes LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_ORDER_NUMBER ON `order` (order_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ORDER_NUMBER ON `order`');
        $this->addSql('ALTER TABLE `order` DROP order_number, DROP payment_method, DROP payment_status, DROP order_source, DROP shipping_full_name, DROP shipping_phone, DROP shipping_address, DROP shipping_city, DROP shipping_postal_code, DROP order_notes');
    }
}
