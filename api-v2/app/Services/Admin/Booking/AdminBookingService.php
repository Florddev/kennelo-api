<?php

declare(strict_types=1);

namespace App\Services\Admin\Booking;

use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminBookingService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Booking::query()
            ->with(BookingService::RELATIONS)
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['organization_id']), fn (Builder $query) => $query->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['activity_id']), fn (Builder $query) => $query->where('activity_id', $filters['activity_id']))
            ->when(isset($filters['from']), fn (Builder $query) => $query->whereDate('end_date', '>=', $filters['from']))
            ->when(isset($filters['to']), fn (Builder $query) => $query->whereDate('start_date', '<=', $filters['to']))
            ->when(isset($filters['disputed']), fn (Builder $query) => $filters['disputed']
                ? $query->whereHas('disputes', fn (Builder $disputes) => $disputes->open())
                : $query->whereDoesntHave('disputes', fn (Builder $disputes) => $disputes->open()))
            ->when(isset($filters['search']), function (Builder $query) use ($filters): void {
                $search = "%{$filters['search']}%";

                $query->where(fn (Builder $query) => $query
                    ->whereHas('user', fn (Builder $user) => $user
                        ->whereLike('first_name', $search)
                        ->orWhereLike('last_name', $search)
                        ->orWhereLike('email', $search))
                    ->orWhereHas('activity', fn (Builder $activity) => $activity->whereLike('name', $search))
                    ->orWhereHas('organization', fn (Builder $organization) => $organization->whereLike('legal_name', $search)));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    public function load(Booking $booking): Booking
    {
        return $booking->load([...BookingService::RELATIONS, 'operations']);
    }
}
