<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ProcessTransactionsCommand;
use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Repository\CompanyWalletRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use App\Service\TransactionProcessorService;
use Closure;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessTransactionsCommandTest extends TestCase
{
    public function testNonInteractiveRunDoesNotApproveFraudReview(): void
    {
        $pending = $this->makeTransaction(requiresAntiFraudCheck: false);
        $fraudReview = $this->makeTransaction(requiresAntiFraudCheck: true);

        $tester = $this->makeTester([$pending], [$fraudReview]);
        $exitCode = $tester->execute([], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(TransactionStatus::COMPLETED, $pending->getStatus());
        self::assertSame(TransactionStatus::FRAUD_REVIEW, $fraudReview->getStatus());
        self::assertStringContainsString('1 transaction(s) await fraud review', $tester->getDisplay());
    }

    public function testFraudReviewIsApprovedOnYes(): void
    {
        $fraudReview = $this->makeTransaction(requiresAntiFraudCheck: true);

        $tester = $this->makeTester([], [$fraudReview]);
        $tester->setInputs(['yes']);
        $tester->execute([], ['interactive' => true]);

        self::assertSame(TransactionStatus::COMPLETED, $fraudReview->getStatus());
    }

    public function testFraudReviewIsRejectedByDefault(): void
    {
        $fraudReview = $this->makeTransaction(requiresAntiFraudCheck: true);

        $tester = $this->makeTester([], [$fraudReview]);
        $tester->setInputs(['']);
        $tester->execute([], ['interactive' => true]);

        self::assertSame(TransactionStatus::REJECTED, $fraudReview->getStatus());
    }

    private function makeTester(array $pending, array $fraudReview): CommandTester
    {
        $transactionRepository = $this->createStub(TransactionRepositoryInterface::class);
        $transactionRepository
            ->method('findByStatus')
            ->willReturnMap([
                [TransactionStatus::PENDING, $pending],
                [TransactionStatus::FRAUD_REVIEW, $fraudReview],
            ]);

        $walletRepository = $this->createStub(WalletRepositoryInterface::class);
        $walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, Wallet::create(1, Currency::PLN)],
                [2, Wallet::create(1, Currency::EUR)],
            ]);

        $processor = new TransactionProcessorService(
            $walletRepository,
            $transactionRepository,
            $this->createStub(CompanyWalletRepositoryInterface::class),
            $this->createConnection(),
        );

        return new CommandTester(new ProcessTransactionsCommand($transactionRepository, $processor));
    }

    private function makeTransaction(bool $requiresAntiFraudCheck): Transaction
    {
        return Transaction::create(
            fromWalletId: 1,
            toWalletId: 2,
            fromAmount: '100.0000',
            toAmount: '25.0000',
            fromCurrency: Currency::PLN,
            toCurrency: Currency::EUR,
            spread: '0.1000',
            exchangeRate: '0.250000',
            requiresAntiFraudCheck: $requiresAntiFraudCheck,
        );
    }

    private function createConnection(): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $operation): mixed => $operation($connection));

        return $connection;
    }
}
