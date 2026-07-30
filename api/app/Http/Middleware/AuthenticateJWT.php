<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJWT
{
    /**
     * The JWT service instance.
     */
    protected JWTService $jwtService;

    /**
     * Create a new middleware instance.
     */
    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $payload = $this->jwtService->validateToken($token);

            throw_if(data_get($payload, 'type') !== 'access', \Exception::class, 'Invalid token type');

            $user = User::find($payload->sub);

            throw_unless($user, \Exception::class, 'User not found');

            throw_if(
                (int) data_get($payload, 'token_version') !== (int) $user->token_version,
                \Exception::class,
                'Token version mismatch',
            );

            $user->load('roles');

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            $request->attributes->set('jwt_payload', $payload);
        } catch (\Exception) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
