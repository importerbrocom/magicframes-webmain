<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 9);

        $data = ContentCache::remember('blog:index:'.md5(json_encode($validated)), function () use ($validated, $perPage) {
            $query = BlogPost::published()
                ->when($validated['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
                ->when($validated['search'] ?? null, function ($q, $term) {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                    $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
                })
                ->orderByDesc('published_at')->orderByDesc('id');

            return BlogPostResource::collection($query->paginate($perPage))->response()->getData(true);
        }, 900);

        return response()->json($data);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $request->attributes->set('blog_full', true);

        $data = ContentCache::remember("blog:show:{$slug}", function () use ($slug, $request) {
            $post = BlogPost::published()->with('seo')->where('slug', $slug)->firstOrFail();

            return ['data' => (new BlogPostResource($post))->resolve($request)];
        }, 900);

        return response()->json($data);
    }

    /** Distinct categories of live posts (for filter links). */
    public function categories(): JsonResponse
    {
        return response()->json(ContentCache::remember('blog:categories', fn () => [
            'data' => BlogPost::published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all(),
        ], 900));
    }
}
