<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthenticationService;
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
    public function __construct(private readonly AuthenticationService $authentication) {}

    /**
     * Register
     *
     * Avec device_name, renvoie { user, token } au lieu d'ouvrir une session.
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
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make((string) $request->string('password')),
            'password_changed_at' => now(),
            'locale' => $request->locale ?? config('app.locale', 'en'),
        ]);

        $user->assignRole('user');

        event(new Registered($user));

        return $this->authentication->login($request, $user, status: 201);
    }
}
