<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class JWTService
{
    public function __construct(private readonly JWTAuth $jwt) {}

    public function generateAccessToken(User $user): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.ttl'));

        return $this->jwt->claims([
            'type' => 'access',
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'locale' => $user->locale ?? config('app.locale', 'en'),
            'token_version' => $user->token_version,
        ])->fromUser($user);
    }

    public function generateRefreshToken(User $user): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.refresh_token_ttl'));
        $token = $this->jwt->claims(['type' => 'refresh', 'token_version' => $user->token_version])->fromUser($user);
        $this->jwt->factory()->setTTL((int) config('jwt.ttl'));

        return $token;
    }

    public function validateToken(string $token): object
    {
        try {
            $payload = $this->jwt->setToken($token)->getPayload();

            return (object) $payload->toArray();
        } catch (TokenExpiredException) {
            throw new \Exception('Token has expired');
        } catch (TokenInvalidException) {
            throw new \Exception('Token signature is invalid');
        } catch (JWTException $e) {
            throw new \Exception('Token validation failed: '.$e->getMessage());
        }
    }

    public function blacklistToken(string $token): void
    {
        if (! config('jwt.blacklist_enabled')) {
            return;
        }

        rescue(
            fn () => $this->jwt->setToken($token)->invalidate(),
            report: fn (\Throwable $e) => Log::error('Failed to blacklist token: '.$e->getMessage()),
        );
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $payload = $this->validateToken($refreshToken);

        throw_if(data_get($payload, 'type') !== 'refresh', \Exception::class, 'Invalid token type');

        $user = User::find($payload->sub);

        throw_unless($user, \Exception::class, 'User not found');

        throw_if(
            (int) data_get($payload, 'token_version') !== (int) $user->token_version,
            \Exception::class,
            'Token version mismatch',
        );

        $this->blacklistToken($refreshToken);

        return [
            'access_token' => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user),
        ];
    }

    public function generateImpersonationToken(User $target, User $impersonator): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.impersonation_ttl', 15));

        $token = $this->jwt->claims([
            'type' => 'access',
            'email' => $target->email,
            'roles' => $target->roles->pluck('name')->toArray(),
            'locale' => $target->locale ?? config('app.locale', 'en'),
            'impersonator_id' => $impersonator->id,
            'token_version' => $target->token_version,
        ])->fromUser($target);

        $this->jwt->factory()->setTTL((int) config('jwt.ttl'));

        return $token;
    }

    public function generateChallengeToken(User $user): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.two_factor_challenge_ttl', 5));
        $token = $this->jwt->claims(['type' => '2fa'])->fromUser($user);
        $this->jwt->factory()->setTTL((int) config('jwt.ttl'));

        return $token;
    }

    public function validateChallengeToken(string $token): User
    {
        $payload = $this->validateToken($token);

        throw_if(data_get($payload, 'type') !== '2fa', \Exception::class, 'Invalid token type');

        $user = User::find($payload->sub);

        throw_unless($user, \Exception::class, 'User not found');

        return $user;
    }

    public function generatePasswordResetChallengeToken(User $user): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.password_reset_challenge_ttl', 15));
        $token = $this->jwt->claims(['type' => 'password_reset_challenge'])->fromUser($user);
        $this->jwt->factory()->setTTL((int) config('jwt.ttl'));

        return $token;
    }

    public function validatePasswordResetChallengeToken(string $token): User
    {
        $payload = $this->validateToken($token);

        throw_if(data_get($payload, 'type') !== 'password_reset_challenge', \Exception::class, 'Invalid token type');

        $user = User::find($payload->sub);

        throw_unless($user, \Exception::class, 'User not found');

        return $user;
    }
}
