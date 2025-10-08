<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API controller for managing categories.
 * Provides CRUD operations for categories with proper validation.
 */
class CategoryController extends Controller
{
    /**
     * List all categories with optional pagination.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = Category::query();
        
        // Optional search by name
        if ($search = $request->query('search')) {
            $search = is_array($search) ? implode(' ', $search) : (string) $search;
            $query->where('name', 'like', '%'. $search . '%');
        }
        
        // Optional pagination (default to all if no per_page specified)
        if ($request->has('per_page')) {
            $perPage = (int) $request->query('per_page', null) ?: 15;
            return CategoryResource::collection($query->orderBy('name')->paginate($perPage));
        }
        
        return CategoryResource::collection($query->orderBy('name')->get());
    }

    /**
     * Store a newly created category.
     * 
     * @param Request $request
     * @return CategoryResource
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'sometimes|string|max:255|unique:categories,slug'
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        
        // Ensure slug uniqueness
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = Category::create($validated);
        
        return new CategoryResource($category);
    }

    /**
     * Display the specified category.
     * 
     * @param Category $category
     * @return CategoryResource
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     * 
     * @param Request $request
     * @param Category $category
     * @return CategoryResource
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string', 
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id)
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255', 
                Rule::unique('categories', 'slug')->ignore($category->id)
            ]
        ]);

        // Auto-update slug if name changed but slug not provided
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure slug uniqueness (excluding current category)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $category->update($validated);
        
        return new CategoryResource($category);
    }

    /**
     * Remove the specified category.
     * 
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {
        // Check if category has associated articles
        if ($category->articles()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category that has associated articles', 'data' => ['articles_count' => $category->articles()->count()]], 400);
        }
        
        $category->delete();
        
    return response()->json(['message' => 'Category deleted successfully', 'data' => new \stdClass()], 200);
    }

    /**
     * Get articles for a specific category.
     * 
     * @param Category $category
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function articles(Category $category, Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = $category->articles()->with(['source', 'author']);
        
        // Optional search within category articles
        if ($search = $request->query('q')) {
            $search = is_array($search) ? implode(' ', $search) : (string) $search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'. $search . '%')
                  ->orWhere('excerpt', 'like', '%'. $search . '%');
            });
        }
        
        // Date filtering
        if ($from = $request->query('from')) {
            $query->where('published_at', '>=', $from);
        }
        
        if ($to = $request->query('to')) {
            $query->where('published_at', '<=', $to);
        }
        
        $perPage = (int) $request->query('per_page', null) ?: 15;
        
        return \App\Http\Resources\ArticleResource::collection(
            $query->orderBy('published_at', 'desc')->paginate($perPage)
        );
    }
}