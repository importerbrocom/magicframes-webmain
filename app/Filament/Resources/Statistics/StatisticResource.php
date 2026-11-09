<?php

namespace App\Filament\Resources\Statistics;

use App\Filament\Resources\Statistics\Pages\ManageStatistics;
use App\Models\Statistic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class StatisticResource extends Resource
{
    protected static ?string $model = Statistic::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Statistics';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('label')->required()->maxLength(60)->placeholder('Weddings Photographed'),
            TextInput::make('value')->numeric()->required()->minValue(0)->helperText('Only enter real figures.'),
            TextInput::make('suffix')->maxLength(8)->placeholder('+'),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('is_published')->label('Published')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable(),
                TextColumn::make('value')->formatStateUsing(fn ($state, Statistic $r) => $state.$r->suffix),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStatistics::route('/')];
    }
}
