<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';
    case TRIALING = 'trialing';
    case PAST_DUE = 'past_due';
    case UNPAID = 'unpaid';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * L'offre souscrite s'applique : abonnement payé ou en période d'essai.
     */
    public function isEffective(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIALING], true);
    }

    public static function fromStripe(string $status): self
    {
        return match ($status) {
            'active' => self::ACTIVE,
            'trialing' => self::TRIALING,
            'past_due' => self::PAST_DUE,
            'unpaid', 'incomplete_expired' => self::UNPAID,
            default => self::CANCELED,
        };
    }
}
