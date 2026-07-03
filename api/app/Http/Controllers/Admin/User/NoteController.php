<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\User;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreNoteRequest;
use App\Http\Requests\Admin\User\UpdateNoteRequest;
use App\Http\Resources\UserNoteResource;
use App\Models\User;
use App\Models\UserNote;
use App\Services\Admin\User\NoteService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @tags Admin User Notes
 */
class NoteController extends Controller
{
    public function __construct(
        private NoteService $notes,
        private UserService $userService
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('manageNotes', $target);

        $notes = $this->notes->paginate($target, $request->query());

        return UserNoteResource::collection($notes)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreNoteRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('manageNotes', $target);

        $note = $this->notes->create($target, $request->user(), $request->validated());

        return (new UserNoteResource($note))
            ->additional([
                'message' => 'Note created successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateNoteRequest $request, string $id, UserNote $note): JsonResponse
    {
        if ($note->user_id !== $id) {
            return $this->notFound();
        }

        $updated = $this->notes->update($note, $request->validated());

        return (new UserNoteResource($updated))
            ->additional([
                'message' => 'Note updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(string $id, UserNote $note): JsonResponse
    {
        if ($note->user_id !== $id) {
            return $this->notFound();
        }

        $this->notes->delete($note);

        return response()->json([
            'message' => 'Note deleted successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }

    private function resolveUser(string $id): User|JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => 'Invalid UUID format',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 400);
        }

        $user = $this->userService->getPublicProfile($id);

        if (! $user) {
            return $this->notFound();
        }

        return $user;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Not found',
            'status' => ApiStatusEnum::ERROR,
            'timestamp' => human_date(Carbon::now()),
        ], 404);
    }
}
