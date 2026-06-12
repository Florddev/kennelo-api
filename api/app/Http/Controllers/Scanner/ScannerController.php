<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scanner;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $scanners = $request->user()->scanners()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => $scanners,
            'timestamp' => human_date(now()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'unique:scanners,code'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $scanner = Scanner::create([
            'user_id' => $request->user()->id,
            'code' => $validated['code'],
            'name' => $validated['name'] ?? null,
        ]);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => $scanner,
            'timestamp' => human_date(now()),
        ], 201);
    }

    public function update(Request $request, Scanner $scanner): JsonResponse
    {
        if ($scanner->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $scanner->update($validated);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => $scanner,
            'timestamp' => human_date(now()),
        ]);
    }

    public function destroy(Request $request, Scanner $scanner): JsonResponse
    {
        if ($scanner->user_id !== $request->user()->id) {
            abort(403);
        }

        $scanner->delete();

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
