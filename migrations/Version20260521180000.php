<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create app_notification table for customer mobile notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(120) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(40) NOT NULL, order_id INT DEFAULT NULL, order_number VARCHAR(32) DEFAULT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_NOTIFICATION_USER (user_id), INDEX IDX_NOTIFICATION_CREATED (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app_notification ADD CONSTRAINT FK_NOTIFICATION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_notification DROP FOREIGN KEY FK_NOTIFICATION_USER');
        $this->addSql('DROP TABLE app_notification');
    }
}
