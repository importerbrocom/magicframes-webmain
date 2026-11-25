<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Models\HomeSection;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageHomeSections extends ManageRecords
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore')->label('Restore deleted sections')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                ->visible(fn () => HomeSection::whereIn('key', array_keys(HomeSection::DEFAULTS))->count() < count(HomeSection::DEFAULTS))
                ->requiresConfirmation()
                ->modalDescription('Deleted blocks come back empty and switched off at the end of the list. Fill them in, switch them on and drag them into place.')
                ->action(function () {
                    $n = HomeSection::restoreMissing();
                    Notification::make()->success()->title($n.' section'.($n === 1 ? '' : 's').' restored')->send();
                }),
        ];
    }
}
