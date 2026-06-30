<?php

declare(strict_types=1);

namespace App\Services\Service;

use App\Models\Activity;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceService
{
    /**
     * @return Collection<int, Service>
     */
    public function list(Activity $activity): Collection
    {
        return Service::where('activity_id', $activity->id)->get();
    }

    public function create(Activity $activity, array $data): Service
    {
        return Service::create([...$data, 'activity_id' => $activity->id]);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }
}
