<?php

namespace App\Filament\Resources\HeroSlides\Pages;

use App\Filament\Resources\HeroSlides\HeroSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHeroSlides extends ManageRecords
{
    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
