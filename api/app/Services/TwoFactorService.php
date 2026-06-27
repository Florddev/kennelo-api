<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeDataUri(string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeInline(
            (string) config('services.two_factor.issuer'),
            $email,
            $secret,
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->values()
            ->all();
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        $remaining = array_values(array_filter(
            $codes,
            fn (string $stored) => ! hash_equals($stored, $code),
        ));

        if (count($remaining) === count($codes)) {
            return false;
        }

        $user->two_factor_recovery_codes = $remaining;
        $user->save();

        return true;
    }
}
