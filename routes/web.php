<?php

use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Admin-managed pages: /about, /contact, /services, /privacy-policy, any page the studio creates…
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('page');
