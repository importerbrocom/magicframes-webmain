<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TestimonialResource;
use App\Models\Page;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;

/** Pages, services and testimonials. */
class ContentController extends Controller
{
    public function pages(): JsonResponse
    {
        return response()->json(ContentCache::remember('pages:list', fn () => [
            'data' => Page::published()->orderBy('id')->get(['title', 'slug', 'template'])->map(fn ($p) => [
                'title' => $p->title, 'slug' => $p->slug, 'template' => $p->template, 'path' => $p->path,
            ])->all(),
        ]));
    }

    public function page(string $slug): JsonResponse
    {
        return response()->json(ContentCache::remember("pages:show:{$slug}", function () use ($slug) {
            $page = Page::published()->with(['sections', 'seo'])->where('slug', $slug)->firstOrFail();

            return ['data' => (new PageResource($page))->resolve()];
        }));
    }

    public function services(): JsonResponse
    {
        return response()->json(ContentCache::remember('services:list', fn () => ServiceResource::collection(Service::published()->get())->response()->getData(true)));
    }

    public function service(string $slug): JsonResponse
    {
        return response()->json(ContentCache::remember("services:show:{$slug}", fn () => [
            'data' => (new ServiceResource(Service::published()->where('slug', $slug)->firstOrFail()))->resolve(request()),
        ]));
    }

    public function testimonials(): JsonResponse
    {
        return response()->json(ContentCache::remember('testimonials:list', fn () => TestimonialResource::collection(Testimonial::published()->get())->response()->getData(true)));
    }
}
