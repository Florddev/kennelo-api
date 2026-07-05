<?php

declare(strict_types=1);

namespace App\Services\Admin\Prospect;

use App\Enums\PaginationEnum;
use App\Models\Prospect;
use App\Models\ProspectContact;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ProspectContactService
{
    public function paginate(Prospect $prospect, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return ProspectContact::with('author')
            ->where('prospect_id', $prospect->id)
            ->orderByDesc('contacted_at')
            ->paginate($perPage);
    }

    public function create(Prospect $prospect, User $author, array $data): ProspectContact
    {
        $contact = ProspectContact::create([
            'prospect_id' => $prospect->id,
            'author_id' => $author->id,
            'type' => $data['type'],
            'contacted_at' => $data['contacted_at'] ?? Carbon::now(),
            'outcome' => $data['outcome'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $contact->fresh('author');
    }

    public function delete(ProspectContact $contact): void
    {
        $contact->delete();
    }
}
