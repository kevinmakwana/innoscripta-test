<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Author;
use App\Http\Resources\AuthorResource;

class AuthorController extends Controller
{
    /**
     * List authors (paginated)
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', null) ?: 25;
        return AuthorResource::collection(Author::orderBy('name')->paginate($perPage));
    }

    /**
     * Show a specific author
     */
    public function show(int|string $id): AuthorResource
    {
        $author = Author::findOrFail($id);
        return new AuthorResource($author);
    }
}
