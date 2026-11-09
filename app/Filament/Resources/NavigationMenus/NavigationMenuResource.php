<?php

namespace App\Filament\Resources\NavigationMenus;

use App\Filament\Resources\NavigationMenus\Pages\ManageNavigationMenus;
use App\Models\NavigationMenu;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NavigationMenuResource extends Resource
{
    protected static ?string $model = NavigationMenu::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|\UnitEnum|null $navigationGroup = 'Header & Menus';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Menu items';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('location')->options(NavigationMenu::LOCATIONS)->required()->default('header_left')->native(false),
            TextInput::make('label')->required()->maxLength(40)->label('Menu label'),
            Select::make('page_id')->label('Link to a page')->relationship('page', 'title')->searchable()->preload()
                ->helperText('Pick a page, or leave empty and type a custom link below.'),
            TextInput::make('url')->label('Custom link')->maxLength(255)->placeholder('/gallery or https://…')
                ->requiredWithout('page_id')
                ->rule(fn () => function (string $a, $v, \Closure $fail) {
                    if ($v && ! preg_match('~^(/|#|https?://|mailto:|tel:)~i', $v)) {
                        $fail('Use a path starting with /, or a full http(s)://, mailto: or tel: link.');
                    }
                }),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('open_in_new_tab')->inline(false),
            Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location')->badge()->formatStateUsing(fn ($state) => NavigationMenu::LOCATIONS[$state] ?? $state)->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('href')->label('Links to')->getStateUsing(fn (NavigationMenu $r) => $r->href)->color('gray'),
                ToggleColumn::make('is_visible')->label('Visible'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')->defaultGroup('location')
            ->filters([SelectFilter::make('location')->options(NavigationMenu::LOCATIONS)])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNavigationMenus::route('/')];
    }
}
