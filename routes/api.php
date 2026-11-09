<?php

use App\Http\Controllers\Api\V1\GalleryController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('gallery/categories', [GalleryController::class, 'categories']);
    Route::get('gallery/categories/{slug}', [GalleryController::class, 'category']);
    Route::get('gallery/images', [GalleryController::class, 'images']);

    Route::get('video-categories', [VideoController::class, 'categories']);
    Route::get('videos', [VideoController::class, 'index']);
    Route::get('videos/{slug}', [VideoController::class, 'show']);
});
