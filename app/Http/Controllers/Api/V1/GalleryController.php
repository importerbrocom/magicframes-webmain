<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryCategoryResource;
use App\Http\Resources\GalleryImageResource;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function categories(): JsonResponse
    {
        $data = ContentCache::remember('gallery:categories', function () {
            $categories = GalleryCategory::published()
                ->withCount(['publishedImages as published_images_count'])
                ->orderBy('sort_order')->orderBy('name')->get();

            return GalleryCategoryResource::collection($categories)->response()->getData(true);
        });

        return response()->json($data);
    }

    public function category(Request $request, string $slug): JsonResponse
    {
        $perPage = $this->perPage($request);
        $page = max(1, (int) $request->query('page', 1));

        $data = ContentCache::remember("gallery:category:{$slug}:{$perPage}:{$page}", function () use ($slug, $perPage) {
            $category = GalleryCategory::published()
                ->withCount(['publishedImages as published_images_count'])
                ->where('slug', $slug)->firstOrFail();

            $images = $category->publishedImages()->paginate($perPage);

            return [
                'category' => (new GalleryCategoryResource($category))->resolve(),
                'images' => GalleryImageResource::collection($images)->response()->getData(true),
            ];
        });

        return response()->json($data);
    }

    public function images(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $this->perPage($request);
        $key = 'gallery:images:'.md5(json_encode($validated));

        $data = ContentCache::remember($key, function () use ($validated, $perPage) {
            $query = GalleryImage::published()->with('category')
                ->when($validated['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
                ->when($validated['featured'] ?? null, fn ($q) => $q->where('is_featured', true))
                ->when($validated['search'] ?? null, function ($q, $term) {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                    $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
                })
                ->orderBy('sort_order')->orderByDesc('id');

            return GalleryImageResource::collection($query->paginate($perPage))->response()->getData(true);
        });

        return response()->json($data);
    }

    private function perPage(Request $request): int
    {
        return min(60, max(1, (int) $request->query('per_page', 24)));
    }
}
