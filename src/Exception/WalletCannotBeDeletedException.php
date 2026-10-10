<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class WalletCannotBeDeletedException extends RuntimeException
{
    public function __construct(int $walletId, string $reason)
    {
        parent::__construct(sprintf('Wallet %d cannot be deleted: %s.', $walletId, $reason));
    }
}
