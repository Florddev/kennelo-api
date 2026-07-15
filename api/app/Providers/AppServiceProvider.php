<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Pet;
use App\Models\Prospect;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\Scanner;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\BookingPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PetPolicy;
use App\Policies\ProspectPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ReviewReportPolicy;
use App\Policies\ScannerPolicy;
use App\Policies\SettingPolicy;
use App\Policies\SubscriptionPlanPolicy;
use App\Policies\UserPolicy;
use App\Services\Prospect\ApifyDiscoveryService;
use App\Services\Prospect\Contracts\PlaceDiscoveryService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            return new StripeClient((string) config('services.stripe.secret'));
        });

        $this->app->bind(PlaceDiscoveryService::class, ApifyDiscoveryService::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Pet::class, PetPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ReviewReport::class, ReviewReportPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Scanner::class, ScannerPolicy::class);
        Gate::policy(Prospect::class, ProspectPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);

        Route::bind('media', fn (string $value) => Media::where('uuid', $value)->firstOrFail());

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

        Scramble::routes(fn () => app()->environment('local', 'staging'));

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });
    }
}
