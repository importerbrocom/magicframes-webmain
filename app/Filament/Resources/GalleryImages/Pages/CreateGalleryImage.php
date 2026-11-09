<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Models\GalleryImage;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateGalleryImage extends CreateRecord
{
    protected static string $resource = GalleryImageResource::class;

    protected static bool $canCreateAnother = false;

    /** One record per uploaded file. */
    protected function handleRecordCreation(array $data): Model
    {
        $files = array_values($data['uploads'] ?? []);
        unset($data['uploads']);

        $order = (int) GalleryImage::where('gallery_category_id', $data['gallery_category_id'])->max('sort_order');
        $first = null;

        foreach ($files as $path) {
            $record = GalleryImage::create($data + [
                'path' => $path,
                'title' => null,
                'sort_order' => ++$order,
            ]);
            $first ??= $record;
        }

        return $first ?? new GalleryImage;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Images uploaded';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
