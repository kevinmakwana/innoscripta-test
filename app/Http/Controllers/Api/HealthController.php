<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => [
                'status' => 'ok',
                'app' => config('app.name'),
                'env' => config('app.env'),
                'time' => now()->toIso8601String(),
            ],
        ], 200);
    }
}
