<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Setting\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\Setting\SettingService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Settings
 */
class SettingController extends Controller
{
    public function __construct(
        private SettingService $settings
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        return response()->json($this->settings->grouped());
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', Setting::class);

        $this->settings->update($request->validated('values', []));

        return response()->json($this->settings->grouped());
    }
}
