<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
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
        ])->fromUser($user);
    }

    public function generateRefreshToken(User $user): string
    {
        $this->jwt->factory()->setTTL((int) config('jwt.refresh_token_ttl'));
        $token = $this->jwt->claims(['type' => 'refresh'])->fromUser($user);
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

        try {
            $this->jwt->setToken($token)->invalidate();
        } catch (\Exception $e) {
            logger()->error('Failed to blacklist token: '.$e->getMessage());
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $payload = $this->validateToken($refreshToken);

        if (! isset($payload->type) || $payload->type !== 'refresh') {
            throw new \Exception('Invalid token type');
        }

        $user = User::find($payload->sub);

        if (! $user) {
            throw new \Exception('User not found');
        }

        return ['access_token' => $this->generateAccessToken($user)];
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

        if (! isset($payload->type) || $payload->type !== '2fa') {
            throw new \Exception('Invalid token type');
        }

        $user = User::find($payload->sub);

        if (! $user) {
            throw new \Exception('User not found');
        }

        return $user;
    }
}
