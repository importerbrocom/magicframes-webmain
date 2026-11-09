<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryCategoryResource;
use App\Http\Resources\GalleryImageResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TestimonialResource;
use App\Http\Resources\VideoResource;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Service;
use App\Models\Statistic;
use App\Models\Testimonial;
use App\Models\Video;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(ContentCache::remember('home', fn () => ['data' => $this->build($request)]));
    }

    private function build(Request $request): array
    {
        $sections = HomeSection::visible()->get()->map(function (HomeSection $s) use ($request) {
            $limit = max(1, $s->items_limit ?: 6);
            $items = match ($s->key) {
                'services' => ServiceResource::collection(Service::published()->limit($limit)->get())->resolve($request),
                'stats' => Statistic::published()->limit($limit)->get()->map(fn ($x) => [
                    'label' => $x->label, 'value' => $x->value, 'suffix' => $x->suffix,
                ])->all(),
                'portfolio' => GalleryImageResource::collection(
                    GalleryImage::published()->with('category')->where('is_featured', true)->orderBy('sort_order')->orderByDesc('id')->limit($limit)->get()
                )->resolve($request),
                'videos' => VideoResource::collection(
                    Video::published()->with('category')->where('is_featured', true)->orderBy('sort_order')->orderByDesc('id')->limit($limit)->get()
                )->resolve($request),
                'recent' => GalleryCategoryResource::collection(
                    GalleryCategory::published()->withCount(['publishedImages as published_images_count'])
                        ->whereHas('publishedImages')->latest('updated_at')->limit($limit)->get()
                )->resolve($request),
                'testimonials' => TestimonialResource::collection(Testimonial::published()->limit($limit)->get())->resolve($request),
                default => null,
            };

            // List-type blocks with nothing to show are dropped so no empty section renders.
            if (in_array($s->key, ['services', 'stats', 'portfolio', 'videos', 'recent', 'testimonials'], true) && empty($items)) {
                return null;
            }

            return [
                'key' => $s->key,
                'label' => $s->label,
                'title' => $s->title,
                'body' => $s->body,
                'body_secondary' => $s->body_secondary,
                'image_url' => $s->image_url,
                'image_secondary_url' => $s->image_secondary_url,
                'button_label' => $s->button_label,
                'button_url' => $s->button_url,
                'items' => $items,
            ];
        })->filter()->values();

        return [
            'hero' => HeroSlide::published()->get()->map(fn ($h) => [
                'heading' => $h->heading, 'subheading' => $h->subheading, 'description' => $h->description,
                'image_url' => $h->image_url, 'button_label' => $h->button_label, 'button_url' => $h->button_url,
            ])->all(),
            'sections' => $sections,
        ];
    }
}
