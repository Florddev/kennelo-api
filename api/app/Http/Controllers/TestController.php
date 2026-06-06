<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApiStatusEnum;
use Illuminate\Support\Carbon;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Hello Thami',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => Carbon::now(),
        ]);
    }
}
