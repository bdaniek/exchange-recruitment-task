<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CompanyWalletRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:company-wallet',
    description: 'Show company wallet balances (accumulated spread earnings)',
)]
class ShowCompanyWalletCommand extends Command
{
    public function __construct(private readonly CompanyWalletRepositoryInterface $companyWalletRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $wallets = $this->companyWalletRepository->findAll();

        if (empty($wallets)) {
            $io->info('No spread earnings recorded yet.');

            return Command::SUCCESS;
        }

        $rows = array_map(
            static fn ($wallet) => [$wallet->getCurrency()->value, number_format($wallet->getBalance(), 4, '.', '')],
            $wallets,
        );

        $io->table(['Currency', 'Balance'], $rows);

        return Command::SUCCESS;
    }
}
