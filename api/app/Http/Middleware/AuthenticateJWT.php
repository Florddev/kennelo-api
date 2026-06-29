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
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $payload = $this->jwtService->validateToken($token);

            if (! isset($payload->type) || $payload->type !== 'access') {
                throw new \Exception('Invalid token type');
            }

            $user = User::find($payload->sub);

            if (! $user) {
                throw new \Exception('User not found');
            }

            $user->load('roles');

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            $request->attributes->set('jwt_payload', $payload);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }

    /**
     * Extract the JWT token from the request.
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
