<?php

declare(strict_types=1);

namespace App\Services\Admin\Prospect;

use App\Enums\PaginationEnum;
use App\Models\Prospect;
use App\Models\ProspectNote;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProspectNoteService
{
    public function paginate(Prospect $prospect, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return ProspectNote::with('author')
            ->where('prospect_id', $prospect->id)
            ->latest()
            ->paginate($perPage);
    }

    public function create(Prospect $prospect, User $author, array $data): ProspectNote
    {
        $note = ProspectNote::create([
            'prospect_id' => $prospect->id,
            'author_id' => $author->id,
            'body' => $data['body'],
        ]);

        return $note->fresh('author');
    }

    public function update(ProspectNote $note, array $data): ProspectNote
    {
        $note->update(['body' => $data['body']]);

        return $note->fresh('author');
    }

    public function delete(ProspectNote $note): void
    {
        $note->delete();
    }
}
