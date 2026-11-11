<?php

namespace App\Filament\Resources\HomeSections;

use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Home sections';

    protected static ?string $modelLabel = 'home section';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('admin_label')->disabled()->dehydrated(false)->label('Section'),
            Toggle::make('is_visible')->label('Show on home page')->inline(false),
            TextInput::make('label')->label('Small script label')->maxLength(60),
            TextInput::make('title')->maxLength(160),
            Textarea::make('body')->label('Text')->rows(4)->maxLength(2000)->columnSpanFull(),
            Textarea::make('body_secondary')->label('Additional text')->rows(3)->maxLength(2000)->columnSpanFull(),
            FileUpload::make('image_path')->label('Main image')->image()->disk('public')->directory('home')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)->hint('Max file size: 15 MB'),
            FileUpload::make('image_secondary_path')->label('Second image')->image()->disk('public')->directory('home')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)->hint('Max file size: 15 MB'),
            TextInput::make('button_label')->maxLength(40),
            TextInput::make('button_url')->maxLength(255)->placeholder('/about'),
            TextInput::make('items_limit')->label('Items shown')->numeric()->minValue(1)->maxValue(24)->default(6)
                ->helperText('For sections that list items (services, photos, films, testimonials…).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('admin_label')->label('Section')->weight('bold'),
                TextColumn::make('title')->limit(50)->placeholder('—'),
                ToggleColumn::make('is_visible')->label('Visible'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')->paginated(false)
            ->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false; // the block list is fixed; admins edit, reorder and hide blocks
    }

    public static function getPages(): array
    {
        return ['index' => ManageHomeSections::route('/')];
    }
}
