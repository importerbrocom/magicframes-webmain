<?php

namespace App\Filament\Resources\VideoCategories\Pages;

use App\Filament\Resources\VideoCategories\VideoCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideoCategory extends EditRecord
{
    protected static string $resource = VideoCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
