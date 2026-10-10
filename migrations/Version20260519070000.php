<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `users` (
                `id`         INT          NOT NULL AUTO_INCREMENT,
                `email`      VARCHAR(130) NOT NULL,
                `roles`      JSON         NOT NULL,
                `created_at` DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email_unique` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `users`');
    }
}
