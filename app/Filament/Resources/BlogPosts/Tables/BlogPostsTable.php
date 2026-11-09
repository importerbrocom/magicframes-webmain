<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use App\Models\BlogPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (BlogPost $r) => $r->cover_url)->square()->size(56),
                TextColumn::make('title')->searchable()->limit(60)->weight('bold'),
                TextColumn::make('category')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge()->getStateUsing(fn (BlogPost $r) => ! $r->is_published ? 'Draft' : ($r->published_at?->isFuture() ? 'Scheduled' : 'Live'))
                    ->color(fn ($state) => match ($state) { 'Live' => 'success', 'Scheduled' => 'info', default => 'gray' }),
                TextColumn::make('published_at')->dateTime('d M Y, H:i')->sortable()->placeholder('—'),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([TernaryFilter::make('is_published')->label('Published')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }
}
