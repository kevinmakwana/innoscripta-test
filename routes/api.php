<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ArticleController;

Route::group(['prefix' => 'v1', 'middleware' => 'api'], function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'index']);
        Route::post('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'store']);
        Route::put('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'update']);
        Route::delete('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'destroy']);
    });

    Route::get('/articles', [ArticleController::class, 'index'])->middleware('throttle:60,1');
    
    // Personalized articles endpoint (requires authentication) - must come before {id} route
    Route::middleware('auth:sanctum')->get('/articles/personalized', [ArticleController::class, 'personalized']);
    
    Route::get('/articles/{id}', [ArticleController::class, 'show']);

    Route::get('/authors', [\App\Http\Controllers\Api\AuthorController::class, 'index']);
    Route::get('/authors/{id}', [\App\Http\Controllers\Api\AuthorController::class, 'show']);
    
    // Category management endpoints
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    Route::get('/categories/{category}/articles', [\App\Http\Controllers\Api\CategoryController::class, 'articles']);
    
    // Protected category management (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
        Route::put('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);
    });
    
    Route::get('/openapi.json', [\App\Http\Controllers\Api\DocsController::class, 'openapi']);
    Route::get('/docs', [\App\Http\Controllers\Api\DocsController::class, 'ui']);
});
