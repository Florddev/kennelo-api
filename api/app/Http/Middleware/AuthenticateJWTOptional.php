<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJWTOptional
{
    protected JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $next($request);
        }

        try {
            $payload = $this->jwtService->validateToken($token);

            if (! isset($payload->type) || $payload->type !== 'access') {
                return $next($request);
            }

            $user = User::find($payload->sub);

            if (! $user) {
                return $next($request);
            }

            $user->load('roles');

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
            $request->attributes->set('jwt_payload', $payload);
        } catch (\Exception) {
            return $next($request);
        }

        return $next($request);
    }
}
