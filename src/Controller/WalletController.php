<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\TransactionResponse;
use App\Dto\WalletResponse;
use App\Entity\User;
use App\Enum\Currency;
use App\Exception\WalletAlreadyExistsException;
use App\Exception\WalletBlockedException;
use App\Exception\WalletNotFoundException;
use App\Repository\WalletRepositoryInterface;
use App\Service\DepositService;
use App\Service\TransferService;
use App\Service\WalletService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use ValueError;

#[Route('/api/wallets')]
final class WalletController extends AbstractController
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransferService $transferService,
        private readonly DepositService $depositService,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $wallets = $this->walletRepository->findByUserId($user->getIdNotNull());

        return new JsonResponse(array_map(static fn ($w) => new WalletResponse($w), $wallets));
    }

    /**
     * @throws JsonException
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

        if (!isset($data['currency'])) {
            return new JsonResponse(['error' => 'Missing required field: currency.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $currency = Currency::from($data['currency']);
        } catch (ValueError) {
            return new JsonResponse(['error' => 'Invalid currency.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $wallet = $this->walletService->createWallet($user->getIdNotNull(), $currency);
        } catch (WalletAlreadyExistsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(new WalletResponse($wallet), Response::HTTP_CREATED);
    }

    /**
     * @throws JsonException
     */
    #[Route('/transfer', methods: ['POST'])]
    public function transfer(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

        foreach (['fromWalletId', 'toWalletId', 'amount'] as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => sprintf('Missing required field: %s.', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Amount must be a positive number.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $transaction = $this->transferService->transfer(
                $user->getIdNotNull(),
                (int) $data['fromWalletId'],
                (int) $data['toWalletId'],
                (string) $data['amount'],
            );
        } catch (WalletNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(new TransactionResponse($transaction), Response::HTTP_CREATED);
    }

    /**
     * @throws JsonException
     */
    #[Route('/{id}/deposit', methods: ['POST'])]
    public function deposit(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];

        if (!isset($data['amount'])) {
            return new JsonResponse(['error' => 'Missing required field: amount.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Amount must be a positive number.'], Response::HTTP_BAD_REQUEST);
        }

        if ((float) $data['amount'] > DepositService::MAX_AMOUNT) {
            return new JsonResponse(['error' => sprintf('Amount cannot exceed %s.', DepositService::MAX_AMOUNT)], Response::HTTP_BAD_REQUEST);
        }

        try {
            $wallet = $this->depositService->deposit(
                $user->getIdNotNull(),
                $id,
                (string) $data['amount'],
            );
        } catch (WalletNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (WalletBlockedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(new WalletResponse($wallet));
    }
}
