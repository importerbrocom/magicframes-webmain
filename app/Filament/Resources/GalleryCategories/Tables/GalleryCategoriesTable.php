<?php

namespace App\Filament\Resources\GalleryCategories\Tables;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Models\GalleryCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class GalleryCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (GalleryCategory $r) => $r->cover_url)->square()->size(56),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('images_count')->counts('images')->label('Images')->sortable(),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                Action::make('addImages')->label('Add images')->icon('heroicon-o-photo')
                    ->url(fn (GalleryCategory $r) => GalleryImageResource::getUrl('create', ['category' => $r->id])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()]),
            ]);
    }
}
