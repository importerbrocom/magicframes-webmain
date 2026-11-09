<?php

use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\GalleryController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\SeoController;
use App\Http\Controllers\Web\VideoController;
use Illuminate\Support\Facades\Route;

$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Built-in sections (their banner/SEO/slug are still managed as Pages in the admin)
Route::get('/portfolio', [GalleryController::class, 'portfolio'])->name('portfolio');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/gallery/{slug}', [GalleryController::class, 'show'])->where('slug', $slug)->name('gallery.show');
Route::get('/videos', [VideoController::class, 'index'])->name('videos');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->where('slug', $slug)->name('blog.show');

// Every other admin-managed page: /about, /contact, /services, /shop, /privacy-policy, custom pages…
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', $slug)->name('page');
