<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::prefix('v1')->group(function(){
    Route::get('/health', function () {
        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toDateTimeString(),
            'service' => 'Blog API',
            'message' => 'API is working'
        ]);
    });
});
