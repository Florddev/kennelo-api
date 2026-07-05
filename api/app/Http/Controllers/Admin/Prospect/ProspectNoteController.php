<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prospect;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prospect\StoreProspectNoteRequest;
use App\Http\Requests\Admin\Prospect\UpdateProspectNoteRequest;
use App\Http\Resources\ProspectNoteResource;
use App\Models\Prospect;
use App\Models\ProspectNote;
use App\Services\Admin\Prospect\ProspectNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @tags Admin Prospect Notes
 */
class ProspectNoteController extends Controller
{
    public function __construct(
        private ProspectNoteService $notes
    ) {}

    public function index(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('manageNotes', $prospect);

        $notes = $this->notes->paginate($prospect, $request->query());

        return ProspectNoteResource::collection($notes)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreProspectNoteRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('manageNotes', $prospect);

        $note = $this->notes->create($prospect, $request->user(), $request->validated());

        return (new ProspectNoteResource($note))
            ->additional([
                'message' => 'Note created successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProspectNoteRequest $request, Prospect $prospect, ProspectNote $note): JsonResponse
    {
        $this->authorize('manageNotes', $prospect);

        if ($note->prospect_id !== $prospect->id) {
            return $this->notFound();
        }

        $note = $this->notes->update($note, $request->validated());

        return (new ProspectNoteResource($note))
            ->additional([
                'message' => 'Note updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Prospect $prospect, ProspectNote $note): JsonResponse
    {
        $this->authorize('manageNotes', $prospect);

        if ($note->prospect_id !== $prospect->id) {
            return $this->notFound();
        }

        $this->notes->delete($note);

        return response()->json([
            'message' => 'Note deleted successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
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
