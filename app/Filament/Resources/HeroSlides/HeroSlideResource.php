<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Hero slides';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('heading')->required()->maxLength(80)->helperText('Large headline over the image.'),
            TextInput::make('subheading')->maxLength(120),
            Textarea::make('description')->rows(3)->maxLength(400)->columnSpanFull(),
            FileUpload::make('image_path')->label('Hero image (wide, 1900px+ recommended)')->image()->disk('public')->directory('hero')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)->required()->columnSpanFull(),
            TextInput::make('button_label')->maxLength(40),
            TextInput::make('button_url')->maxLength(255)->placeholder('/contact'),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('is_published')->label('Published')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->label('')->width(96)->height(54),
                TextColumn::make('heading')->searchable(),
                TextColumn::make('subheading')->toggleable(),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHeroSlides::route('/')];
    }
}
