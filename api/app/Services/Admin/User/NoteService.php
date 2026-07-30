<?php

declare(strict_types=1);

namespace App\Services\Admin\User;

use App\Enums\PaginationEnum;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteService
{
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value()), 100));

        return UserNote::with('author.media')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $user, User $author, array $data): UserNote
    {
        $note = UserNote::create([
            'user_id' => $user->id,
            'author_id' => $author->id,
            'body' => $data['body'],
        ]);

        return $note->fresh('author');
    }

    public function update(UserNote $note, array $data): UserNote
    {
        $note->update(['body' => $data['body']]);

        return $note->fresh('author');
    }

    public function delete(UserNote $note): void
    {
        $note->delete();
    }
}
