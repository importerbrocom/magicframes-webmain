<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Models\GalleryImage;
use App\Services\DemoContent;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListGalleryImages extends ListRecords
{
    protected static string $resource = GalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removes ONLY the bundled sample photos; your own uploads, the categories and the rest of the demo stay.
            Action::make('removeSamples')->label('Remove sample photos')->icon('heroicon-o-trash')->color('danger')
                ->visible(fn () => (bool) app(DemoContent::class)->sampleIds(GalleryImage::class))
                ->requiresConfirmation()
                ->modalHeading('Remove the sample gallery photos?')
                ->modalDescription(fn () => 'This deletes the '.count(app(DemoContent::class)->sampleIds(GalleryImage::class)).' sample photos that came with the demo. Photos you uploaded yourself are NOT touched.')
                ->modalSubmitActionLabel('Yes, remove sample photos')
                ->action(function () {
                    $n = app(DemoContent::class)->removeOnly(GalleryImage::class);
                    Notification::make()->success()->title("$n sample photos removed")->body('Only your own photos are shown now.')->send();
                }),
            CreateAction::make(),
        ];
    }
}
