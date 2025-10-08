<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API controller for listing and showing articles.
 *
 * Query params supported for `index`:
 *  - q: full text query (title/excerpt)
 *  - source: source slug
 *  - category: category slug
 *  - from, to: date range for published_at
 *  - per_page: pagination size
 */
class ArticleController extends Controller
{
    /**
     * List articles with optional filtering and pagination.
     * Returns a paginated collection of ArticleResource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($q = $request->query('q')) {
            $q = is_array($q) ? implode(' ', $q) : (string) $q;
            // We call the scope statically here (Article::search) so static analyzers
            // like phpstan can resolve the method on the model. This produces the
            // same Builder as calling the scope on an instance (Article::query()->search($q)).
            // Keeping the static call reduces false-positive warnings from static analysis.
            $query = Article::search($q)->with(['source','category','author']);
        } else {
            $query = Article::query()->with(['source','category','author']);
        }

        if ($source = $request->query('source')) {
            $query->whereHas('source', function ($q) use ($source) {
                $q->where('slug', $source);
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        if ($author = $request->query('author')) {
            if (is_numeric($author)) {
                $query->where('author_id', (int) $author);
            } else {
                $author = is_array($author) ? implode(' ', $author) : (string) $author;
                $query->whereHas('author', function ($q) use ($author) {
                    $q->where('name', 'like', '%'. $author . '%');
                });
            }
        }

        if ($from = $request->query('from')) {
            $query->where('published_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('published_at', '<=', $to);
        }

        $default = (int) config('api.per_page', 15);
        $max = (int) config('api.max_per_page', 100);

        $requested = $request->query('per_page');
        $perPage = $requested ? min((int) $requested, $max) : $default;

        return ArticleResource::collection($query->orderBy('published_at','desc')->paginate($perPage));
    }

    /**
     * Show an individual article by id.
     *
     * @param int|string $id
     */
    public function show(int|string $id): ArticleResource
    {
        $article = Article::with(['source','category','author'])->findOrFail($id);
        return new ArticleResource($article);
    }

    /**
     * Get personalized articles based on user preferences.
     * Combines user preferences with optional additional filtering.
     * Requires authentication.
     */
    public function personalized(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        
        if (!$user) {
            // Auth guard should have prevented this; throw to return proper HTTP 401.
            abort(401, 'Authentication required');
        }

        $preferences = $user->userPreference;
        if ($q = $request->query('q')) {
            $q = is_array($q) ? implode(' ', $q) : (string) $q;
            $query = Article::search($q)->with(['source','category','author']);
        } else {
            $query = Article::query()->with(['source','category','author']);
        }

        // Apply user preferences if they exist
        if ($preferences) {
            $query->where(function ($q) use ($preferences) {
                $added = false;

                if (!empty($preferences->sources) && is_array($preferences->sources)) {
                    $q->orWhereHas('source', function ($sub) use ($preferences) {
                        $sub->whereIn('slug', $preferences->sources);
                    });
                    $added = true;
                }

                if (!empty($preferences->categories) && is_array($preferences->categories)) {
                    $q->orWhereHas('category', function ($sub) use ($preferences) {
                        $sub->whereIn('slug', $preferences->categories);
                    });
                    $added = true;
                }

                if (!empty($preferences->authors) && is_array($preferences->authors)) {
                    $q->orWhereIn('author_id', $preferences->authors);
                    $added = true;
                }

                // If nothing matched (no preference filters), make the closure a no-op
                if (! $added) {
                    $q->whereRaw('1 = 1');
                }
            });
        }

        // Additional filtering (search already applied when present earlier)

        if ($from = $request->query('from')) {
            $query->where('published_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('published_at', '<=', $to);
        }

    $default = (int) config('api.per_page', 15);
    $max = (int) config('api.max_per_page', 100);

    $requested = $request->query('per_page');
    $perPage = $requested ? min((int) $requested, $max) : $default;

    return ArticleResource::collection($query->orderBy('published_at','desc')->paginate($perPage));
    }
}
