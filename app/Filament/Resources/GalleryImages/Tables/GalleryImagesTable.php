<?php

namespace App\Filament\Resources\GalleryImages\Tables;

use App\Models\GalleryImage;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GalleryImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumb')->label('')->getStateUsing(fn (GalleryImage $r) => $r->thumb_url)->square()->size(64),
                TextColumn::make('title')->searchable()->placeholder('—')->limit(40),
                TextColumn::make('category.name')->label('Category')->sortable()->badge(),
                ToggleColumn::make('is_featured')->label('Featured'),
                ToggleColumn::make('is_published')->label('Published'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('gallery_category_id')->label('Category')->relationship('category', 'name')->preload(),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Publish')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->label('Unpublish')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('feature')->label('Mark featured')->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('move')->label('Move to category')->icon('heroicon-o-arrow-right-circle')
                        ->schema([\Filament\Forms\Components\Select::make('gallery_category_id')->label('Category')
                            ->options(\App\Models\GalleryCategory::orderBy('name')->pluck('name', 'id'))->required()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['gallery_category_id' => $data['gallery_category_id']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }
}
