<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsGalleryController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

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
Route::prefix('v1')->middleware(['auth:sanctum', 'api'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [NewsController::class, 'dashboardStats']);
    });
    // crud news
    Route::prefix('admin/news')->middleware('author-or-superadmin')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::post('/', [NewsController::class, 'store']);
    
        Route::get('/my-news', [NewsController::class, 'myNews']);
        Route::get('/drafts', [NewsController::class, 'drafts']);
        Route::get('/scheduled', [NewsController::class, 'scheduled']);
        Route::get('/{id}', [NewsController::class, 'show']);
        // update precisa ser feito com POST, bug do laravel com PUT
        // Route::put('/{id}', [NewsController::class, 'update']);
        Route::post('/{id}', [NewsController::class, 'update']);
        Route::delete('/{id}', [NewsController::class, 'destroy']);
        Route::patch('/{id}/publish', [NewsController::class, 'publish']);
        Route::patch('/{id}/unpublish', [NewsController::class, 'unpublish']);
        Route::patch('/{id}/restore', [NewsController::class, 'restore']);
        Route::delete('/{id}/force-delete', [NewsController::class, 'forceDelete']);
    
        Route::post('/{id}/tags', [NewsController::class, 'syncTags']);
        Route::delete('/{id}/tags/{tagId}', [NewsController::class, 'detachTag']);
    });

    // crud news gallery
    Route::prefix('admin/news/{newsId}/gallery')->group(function () {
        Route::get('/', [NewsGalleryController::class, 'index']);
        Route::post('/', [NewsGalleryController::class, 'store']);
        Route::post('/single', [NewsGalleryController::class, 'storeSingle']);
        // Route::post('/upload-multiple', [NewsGalleryController::class, 'storeMultiple']);
        Route::put('/reorder', [NewsGalleryController::class, 'reorder']);
        Route::put('/{id}', [NewsGalleryController::class, 'update']);
        Route::delete('/{id}', [NewsGalleryController::class, 'destroy']);
    });

    // crud categories
    Route::prefix('admin/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::group(['middleware' => 'superadmin'], function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
            Route::patch('/{id}/restore', [CategoryController::class, 'restore']);
            Route::delete('/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
        });
    });

    Route::prefix('admin/tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::group(['middleware' => 'superadmin'], function () {
            Route::post('/', [TagController::class, 'store']);
            Route::get('/{id}', [TagController::class, 'show']);
            Route::put('/{id}', [TagController::class, 'update']);
            Route::delete('/{id}', [TagController::class, 'destroy']);
        });
    });

    Route::prefix('admin/admins')->middleware('superadmin')->group(function () {
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
