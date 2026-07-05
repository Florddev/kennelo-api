<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prospect;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prospect\StoreProspectContactRequest;
use App\Http\Resources\ProspectContactResource;
use App\Models\Prospect;
use App\Models\ProspectContact;
use App\Services\Admin\Prospect\ProspectContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @tags Admin Prospect Contacts
 */
class ProspectContactController extends Controller
{
    public function __construct(
        private ProspectContactService $contacts
    ) {}

    public function index(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('manageContacts', $prospect);

        $contacts = $this->contacts->paginate($prospect, $request->query());

        return ProspectContactResource::collection($contacts)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreProspectContactRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('manageContacts', $prospect);

        $contact = $this->contacts->create($prospect, $request->user(), $request->validated());

        return (new ProspectContactResource($contact))
            ->additional([
                'message' => 'Contact logged successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Prospect $prospect, ProspectContact $contact): JsonResponse
    {
        $this->authorize('manageContacts', $prospect);

        if ($contact->prospect_id !== $prospect->id) {
            return response()->json([
                'message' => 'Not found',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 404);
        }

        $this->contacts->delete($contact);

        return response()->json([
            'message' => 'Contact deleted successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }
}
