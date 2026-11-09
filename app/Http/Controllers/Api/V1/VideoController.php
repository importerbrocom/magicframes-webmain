<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoCategoryResource;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function categories(): JsonResponse
    {
        $data = ContentCache::remember('video:categories', function () {
            $categories = VideoCategory::published()
                ->withCount(['videos as published_videos_count' => fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort_order')->orderBy('name')->get();

            return VideoCategoryResource::collection($categories)->response()->getData(true);
        });

        return response()->json($data);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 12);

        $data = ContentCache::remember('video:index:'.md5(json_encode($validated)), function () use ($validated, $perPage) {
            $query = Video::published()->with('category')
                ->when($validated['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)->where('is_published', true)))
                ->when($validated['featured'] ?? null, fn ($q) => $q->where('is_featured', true))
                ->when($validated['search'] ?? null, function ($q, $term) {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                    $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
                })
                ->orderBy('sort_order')->orderByDesc('id');

            return VideoResource::collection($query->paginate($perPage))->response()->getData(true);
        });

        return response()->json($data);
    }

    public function show(string $slug): JsonResponse
    {
        $data = ContentCache::remember("video:show:{$slug}", function () use ($slug) {
            $video = Video::published()->with('category')->where('slug', $slug)->firstOrFail();

            return ['data' => (new VideoResource($video))->resolve()];
        });

        return response()->json($data);
    }
}
