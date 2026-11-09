<?php

namespace App\Filament\Resources\VideoCategories\Pages;

use App\Filament\Resources\VideoCategories\VideoCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoCategory extends CreateRecord
{
    protected static string $resource = VideoCategoryResource::class;
}
