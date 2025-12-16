<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsGalleryController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toDateTimeString(),
            'service' => 'Blog API',
            'message' => 'API is working'
        ]);
    });
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    });
    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'indexPublic']);
        Route::get('/featured', [NewsController::class, 'featured']);
        Route::get('/recent', [NewsController::class, 'recent']);
        Route::get('/search', [NewsController::class, 'searchPublic']);
        Route::get('/slug/{slug}', [NewsController::class, 'showBySlug']);
        Route::get('/{id}', [NewsController::class, 'showPublic']);
        Route::post('/{id}/view', [NewsController::class, 'incrementView']);

        Route::get('/category/{categorySlug}', [NewsController::class, 'byCategory']);
        Route::get('/author/{authorSlug}', [NewsController::class, 'byAuthor']);
        Route::get('/tag/{tagSlug}', [NewsController::class, 'byTag']);
    });
});
Route::prefix('v1')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::post('/', [NewsController::class, 'store'])->middleware('can:create,App\Models\News');

        // crud news
        Route::get('/my-news', [NewsController::class, 'myNews']);
        Route::get('/drafts', [NewsController::class, 'drafts']);
        Route::get('/scheduled', [NewsController::class, 'scheduled']);
        Route::get('/{id}', [NewsController::class, 'show'])->middleware('can:view,news');
        Route::put('/{id}', [NewsController::class, 'update'])->middleware('can:update,news');
        Route::delete('/{id}', [NewsController::class, 'destroy'])->middleware('can:delete,news');
        Route::patch('/{id}/publish', [NewsController::class, 'publish'])->middleware('can:publish,news');
        Route::patch('/{id}/unpublish', [NewsController::class, 'unpublish'])->middleware('can:unpublish,news');
        Route::patch('/{id}/restore', [NewsController::class, 'restore'])->middleware('can:restore,news');
        Route::delete('/{id}/force-delete', [NewsController::class, 'forceDelete'])->middleware('can:force-delete,news');

        Route::post('/{id}/tags', [NewsController::class, 'syncTags']);
        Route::delete('/{id}/tags/{tagId}', [NewsController::class, 'detachTag']);
    });

    // crud news gallery
    Route::prefix('admin/news/{newsId}/gallery')->group(function () {
        Route::get('/', [NewsGalleryController::class, 'index']);
        Route::post('/', [NewsGalleryController::class, 'store']);
        Route::post('/upload-multiple', [NewsGalleryController::class, 'storeMultiple']);
        Route::put('/reorder', [NewsGalleryController::class, 'reorder']);
        Route::put('/{id}', [NewsGalleryController::class, 'update']);
        Route::delete('/{id}', [NewsGalleryController::class, 'destroy']);
    });

    // crud categories
    Route::prefix('admin/categories')->middleware('can:manage-categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
        Route::patch('/{id}/restore', [CategoryController::class, 'restore']);
        Route::delete('/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
    });

    Route::prefix('admin/tags')->middleware('can:manage-tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{id}', [TagController::class, 'show']);
        Route::put('/{id}', [TagController::class, 'update']);
        Route::delete('/{id}', [TagController::class, 'destroy']);
    });

    Route::prefix('admin/admins')->middleware('can:manage-admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::post('/', [AdminController::class, 'store']);
        Route::get('/{id}', [AdminController::class, 'show']);
        Route::put('/{id}', [AdminController::class, 'update']);
        Route::delete('/{id}', [AdminController::class, 'destroy']);
        Route::patch('/{id}/restore', [AdminController::class, 'restore']);
        Route::delete('/{id}/change-role', [AdminController::class, 'changeRole']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [AdminController::class, 'profile']);
        Route::put('/', [AdminController::class, 'updateProfile']);
        Route::put('/password', [AdminController::class, 'updatePassword']);
        Route::post('/avatar', [AdminController::class, 'updateAvatar']);
    });
});
