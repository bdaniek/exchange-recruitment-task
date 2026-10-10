<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store wallet balances as DECIMAL instead of DOUBLE';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `wallets` MODIFY `balance` DECIMAL(15,4) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `wallets` MODIFY `balance` DOUBLE NOT NULL DEFAULT 0');
    }
}
