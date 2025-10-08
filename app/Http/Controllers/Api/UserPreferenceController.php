<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserPreferenceResource;
use App\Models\UserPreference;
use Illuminate\Http\Request;

/**
 * Controller for managing a user's reading preferences (selected sources,
 * categories and authors). All endpoints require authentication via
 * sanctum (`auth:sanctum` middleware applied in routes).
 */
class UserPreferenceController extends Controller
{
    /**
     * Return the authenticated user's preferences or null if none exist.
     */
    public function index(Request $request): \App\Http\Resources\UserPreferenceResource
    {
        $prefs = UserPreference::where('user_id', $request->user()->id)->first();

        return new UserPreferenceResource($prefs);
    }

    /**
     * Create or replace the authenticated user's preferences.
     */
    public function store(Request $request): \App\Http\Resources\UserPreferenceResource
    {
        $data = $request->validate([
            'sources' => 'array',
            'categories' => 'array',
            'authors' => 'array',
        ]);

        $prefs = UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return new UserPreferenceResource($prefs);
    }

    /**
     * Update the authenticated user's options (partial update allowed).
     */
    public function update(Request $request): \App\Http\Resources\UserPreferenceResource
    {
        $data = $request->validate([
            'sources' => 'array',
            'categories' => 'array',
            'authors' => 'array',
        ]);

        $prefs = UserPreference::where('user_id', $request->user()->id)->firstOrFail();
        $prefs->update($data);

        return new UserPreferenceResource($prefs);
    }

    /**
     * Remove the user's stored preferences.
     */
    public function destroy(Request $request): \Illuminate\Http\Response
    {
        $prefs = UserPreference::where('user_id', $request->user()->id)->first();
        if ($prefs) {
            $prefs->delete();
        }

        return response()->noContent();
    }
}
