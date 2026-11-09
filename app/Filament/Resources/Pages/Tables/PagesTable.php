<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('path')->label('URL')->color('gray'),
                TextColumn::make('template')->badge()->formatStateUsing(fn ($s) => Page::TEMPLATES[$s] ?? $s),
                IconColumn::make('is_system')->label('Built-in')->boolean(),
                ToggleColumn::make('is_published')->label('Published'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('id')
            ->filters([TernaryFilter::make('is_published')->label('Published')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Page $r) => $r->is_system),
            ])
            ->toolbarActions([BulkActionGroup::make([
                DeleteBulkAction::make()->requiresConfirmation()
                    ->modalDescription('Built-in pages are skipped and cannot be deleted.')
                    ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->reject(fn (Page $p) => $p->is_system)->each->delete()),
            ])]);
    }
}
