<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserRepositoryInterface;
use App\Repository\UserTokenRepositoryInterface;
use DateTimeImmutable;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-user', description: 'Creates a user with a random email and generates an auth token')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserTokenRepositoryInterface $userTokenRepository,
    ) {
        parent::__construct();
    }

    /**
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = bin2hex(random_bytes(8)).'@example.com';

        $user = new User(
            id: null,
            email: $email,
            roles: ['ROLE_USER'],
            createdAt: new DateTimeImmutable(),
        );

        $this->userRepository->save($user);

        $token = UserToken::create(
            userId: $user->getId(),
            expiresAt: new DateTimeImmutable('+1 year'),
        );

        $this->userTokenRepository->save($token);

        $io = new SymfonyStyle($input, $output);
        $io->success('User created successfully.');
        $io->definitionList(
            ['Email' => $email],
            ['Authorization Token' => $token->getToken()],
        );

        return Command::SUCCESS;
    }
}
