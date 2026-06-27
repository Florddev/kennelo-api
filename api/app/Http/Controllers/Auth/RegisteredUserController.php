<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthTokenResource;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class RegisteredUserController extends Controller
{
    /**
     * The JWT service instance.
     */
    protected JWTService $jwtService;

    /**
     * Create a new controller instance.
     */
    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Register
     *
     * @unauthenticated
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'locale' => ['nullable', 'string', 'in:'.config('app.available_locales', 'en')],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make((string) $request->string('password')),
            'locale' => $request->locale ?? config('app.locale', 'en'),
        ]);

        $user->assignRole('user');

        event(new Registered($user));

        $user->load('roles');

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response()
            ->setStatusCode(201);
    }
}
