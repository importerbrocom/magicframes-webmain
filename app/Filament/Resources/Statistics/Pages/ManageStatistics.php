<?php

namespace App\Filament\Resources\Statistics\Pages;

use App\Filament\Resources\Statistics\StatisticResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatistics extends ManageRecords
{
    protected static string $resource = StatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
