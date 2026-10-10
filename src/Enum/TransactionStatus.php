<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FRAUD_REVIEW = 'fraud_review';
    case REJECTED = 'rejected';
}
