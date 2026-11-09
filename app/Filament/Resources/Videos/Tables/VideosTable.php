<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Models\Video;
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

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumb')->label('')->getStateUsing(fn (Video $r) => $r->thumbnail_url)->width(96)->height(54),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('category.name')->label('Category')->badge()->sortable(),
                TextColumn::make('source_type')->label('Source')->badge(),
                ToggleColumn::make('is_featured')->label('Featured'),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('video_category_id')->label('Category')->relationship('category', 'name')->preload(),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }
}
