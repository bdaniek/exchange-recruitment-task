<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `user_tokens` (
                `id`         INT          NOT NULL AUTO_INCREMENT,
                `user_id`    INT          NOT NULL,
                `token`      VARCHAR(128) NOT NULL,
                `expires_at` DATETIME     NOT NULL,
                `created_at` DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token_unique` (`token`),
                CONSTRAINT `fk_user_tokens_user_id`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `user_tokens`');
    }
}
