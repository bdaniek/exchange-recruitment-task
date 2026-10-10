<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class InsufficientFundsException extends RuntimeException
{
    public function __construct(int $walletId)
    {
        parent::__construct(sprintf('Insufficient funds in wallet %d.', $walletId));
    }
}
