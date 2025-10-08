<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    public function openapi(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $path = base_path('docs/openapi.json');
        if (! file_exists($path)) {
            return response()->json(['message' => 'OpenAPI spec not found', 'data' => new \stdClass()], 404);
        }

        return response()->file($path, ['Content-Type' => 'application/json']);
    }

    /**
     * Serve a Swagger UI page that points to the openapi.json spec.
     */
    public function ui(Request $request): \Illuminate\Contracts\View\View
    {
        return view('docs');
    }
}
