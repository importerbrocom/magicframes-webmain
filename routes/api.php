<?php

use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\GalleryController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('site', [SiteController::class, 'show']);
    Route::get('home', [HomeController::class, 'show']);
    Route::get('pages', [ContentController::class, 'pages']);
    Route::get('pages/{slug}', [ContentController::class, 'page']);
    Route::get('services', [ContentController::class, 'services']);
    Route::get('services/{slug}', [ContentController::class, 'service']);
    Route::get('testimonials', [ContentController::class, 'testimonials']);

    Route::get('gallery/categories', [GalleryController::class, 'categories']);
    Route::get('gallery/categories/{slug}', [GalleryController::class, 'category']);
    Route::get('gallery/images', [GalleryController::class, 'images']);

    Route::get('video-categories', [VideoController::class, 'categories']);
    Route::get('videos', [VideoController::class, 'index']);
    Route::get('videos/{slug}', [VideoController::class, 'show']);
});
