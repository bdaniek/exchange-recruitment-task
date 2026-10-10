<?php

declare(strict_types=1);

namespace App\Exception;

use App\Enum\Currency;
use RuntimeException;

final class WalletAlreadyExistsException extends RuntimeException
{
    public function __construct(int $userId, Currency $currency)
    {
        parent::__construct(
            sprintf('Wallet for user %d in currency %s already exists.', $userId, $currency->value),
        );
    }
}
