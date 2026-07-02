<?php

declare(strict_types=1);

namespace App\Services\Admin\User;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function streamCsv(array $filters = []): StreamedResponse
    {
        $query = User::withInactive()
            ->with('roles')
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($q) use ($filters) {
                    $q->where('first_name', 'like', "%{$filters['search']}%")
                        ->orWhere('last_name', 'like', "%{$filters['search']}%")
                        ->orWhere('email', 'like', "%{$filters['search']}%");
                });
            })
            ->when(isset($filters['role']), fn ($q) => $q->role($filters['role']));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['id', 'first_name', 'last_name', 'email', 'status', 'roles', 'created_at']);

            $query->chunk(500, function ($users) use ($handle): void {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->first_name,
                        $user->last_name,
                        $user->email,
                        $user->status->name,
                        $user->getRoleNames()->implode('|'),
                        $user->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, 'users.csv', $headers);
    }
}
