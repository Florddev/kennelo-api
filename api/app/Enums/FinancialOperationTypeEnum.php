<?php

declare(strict_types=1);

namespace App\Enums;

enum FinancialOperationTypeEnum: string
{
    case AUTHORIZE = 'authorize';
    case CAPTURE = 'capture';
    case CAPTURE_FAILED = 'capture_failed';
    case RELEASE = 'release';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case STATUS_CHANGE = 'status_change';
    case SUBSCRIPTION_PAYMENT = 'subscription_payment';
    case SUBSCRIPTION_REFUND = 'subscription_refund';
}
