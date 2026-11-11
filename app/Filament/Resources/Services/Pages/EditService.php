<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_page')->label('View page')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => url($this->record->url))->openUrlInNewTab()
                ->visible(fn () => $this->record->is_published),
            DeleteAction::make(),
        ];
    }
}
