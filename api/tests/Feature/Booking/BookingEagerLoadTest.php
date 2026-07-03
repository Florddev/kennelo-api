<?php

declare(strict_types=1);

use App\Http\Resources\BookingResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\DB;

function countQueriesRenderingUserBookings(User $user): int
{
    $service = app(BookingService::class);

    $bookings = $service->getUserBookings($user);

    DB::enableQueryLog();
    BookingResource::collection($bookings)->resolve(request());
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $queries;
}

it('does not run extra queries per booking when rendering user bookings', function () {
    $user = User::factory()->create();

    $oneActivity = Activity::factory()->create();
    Booking::factory()->create(['user_id' => $user->id, 'activity_id' => $oneActivity->id]);

    $queriesForOne = countQueriesRenderingUserBookings($user);

    collect(range(1, 3))->each(function () use ($user): void {
        $activity = Activity::factory()->create();
        Booking::factory()->create(['user_id' => $user->id, 'activity_id' => $activity->id]);
    });

    $queriesForFour = countQueriesRenderingUserBookings($user);

    expect($queriesForFour)->toBe($queriesForOne);
});
