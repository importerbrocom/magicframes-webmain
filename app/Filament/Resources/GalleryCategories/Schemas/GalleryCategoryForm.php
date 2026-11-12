<?php

namespace App\Filament\Resources\GalleryCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GalleryCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category')->columns(2)->schema([
                TextInput::make('name')
                    ->required()->maxLength(120)->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, ?string $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')
                    ->required()->maxLength(140)->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the website URL, e.g. /gallery/wedding-photography'),
                Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
                FileUpload::make('cover_path')
                    ->label('Cover image')->image()->disk('public')->directory('gallery/covers')
                    ->maxSize(10240)->hint('Max file size: 10 MB')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Optional. If empty, the first published image is used.')
                    ->columnSpanFull(),
                TextInput::make('sort_order')->numeric()->default(0)->minValue(0)->helperText('Lower numbers appear first. You can also drag rows in the list.'),
                Toggle::make('is_published')->label('Published')->default(true)->inline(false),
            ]),
            Section::make('SEO')->collapsed()->columns(1)->schema([
                TextInput::make('meta_title')->maxLength(70),
                Textarea::make('meta_description')->rows(2)->maxLength(320),
            ]),
        ]);
    }
}
