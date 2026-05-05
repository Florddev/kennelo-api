<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\Pet;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\EstablishmentPolicy;
use App\Policies\PetPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ReviewReportPolicy;
use App\Policies\UserPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Establishment::class, EstablishmentPolicy::class);
        Gate::policy(Pet::class, PetPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ReviewReport::class, ReviewReportPolicy::class);

        Route::bind('media', fn (string $value) => Media::where('uuid', $value)->firstOrFail());

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Scramble::routes(fn () => app()->environment('local', 'staging'));

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });
    }
}
