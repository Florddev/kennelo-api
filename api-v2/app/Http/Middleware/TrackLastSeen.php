<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    private const THROTTLE_SECONDS = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user instanceof User) {
            $cacheKey = "last-seen:{$user->id}";

            if (! Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, self::THROTTLE_SECONDS);

                User::withInactive()
                    ->withTrashed()
                    ->whereKey($user->id)
                    ->update(['last_seen_at' => now()]);
            }
        }

        return $response;
    }
}
