<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            return new StripeClient((string) config('services.stripe.secret'));
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // Les policies sont découvertes automatiquement (App\Models\X → App\Policies\XPolicy).

        // Espace de gestion : réservé aux membres actifs d'au moins une entreprise (plus de rôle « manager »).
        Gate::define('access-management', fn (User $user): bool => $user->canAccessManagement());

        // {user} : identifiant uuid (sinon 404) ; inclut les comptes inactifs ou bannis (profil public et administration).
        Route::pattern('user', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
        Route::bind('user', fn (string $value): User => User::withInactive()->findOrFail($value));

        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers()->symbols());

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire')),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
            );

            parse_str((string) parse_url($signedUrl, PHP_URL_QUERY), $params);

            return config('app.frontend_url').'/verify-email?'.http_build_query([
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'expires' => $params['expires'] ?? '',
                'signature' => $params['signature'] ?? '',
            ]);
        });

        MagicLinkNotification::createUrlUsing(function (User $notifiable) {
            $signedUrl = URL::temporarySignedRoute(
                'magic-link.verify',
                now()->addMinutes((int) config('auth.magic_link.expire')),
                ['id' => $notifiable->getKey()],
            );

            parse_str((string) parse_url($signedUrl, PHP_URL_QUERY), $params);

            return config('app.frontend_url').'/magic-link/verify?'.http_build_query([
                'id' => $notifiable->getKey(),
                'expires' => $params['expires'] ?? '',
                'signature' => $params['signature'] ?? '',
            ]);
        });
    }
}
