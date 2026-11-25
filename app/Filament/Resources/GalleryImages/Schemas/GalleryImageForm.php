<?php

namespace App\Filament\Resources\GalleryImages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryImageForm
{
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('gallery_category_id')
                    ->label('Gallery category')
                    ->relationship('category', 'name')
                    ->searchable()->preload()->required()
                    ->default(fn () => request()->integer('category') ?: null)
                    ->createOptionForm([
                        TextInput::make('name')->required()->maxLength(120),
                    ])
                    ->createOptionUsing(fn (array $data) => \App\Models\GalleryCategory::create($data)->getKey()),

                // Create: select many files at once (preview before publishing).
                FileUpload::make('uploads')
                    ->label('Images')
                    ->helperText('Select one or many images (JPG, PNG or WebP, max 20 MB each). Web-optimised versions are generated automatically.')
                    ->multiple()->reorderable()->appendFiles()
                    ->image()->imagePreviewHeight('140')->panelLayout('grid')
                    ->acceptedFileTypes(self::MIMES)->maxSize(20480)->hint('Max file size: 20 MB')->maxFiles(50)
                    ->disk('public')->directory('gallery')->visibility('public')
                    ->required()->visibleOn('create')->columnSpanFull(),

                // Edit: replace the existing file.
                FileUpload::make('path')
                    ->label('Image (upload a new file to replace)')
                    ->image()->imagePreviewHeight('220')
                    ->acceptedFileTypes(self::MIMES)->maxSize(20480)->hint('Max file size: 20 MB')
                    ->disk('public')->directory('gallery')->visibility('public')
                    ->required()->visibleOn('edit')->columnSpanFull(),

                TextInput::make('title')->maxLength(160)
                    ->helperText(fn ($operation) => $operation === 'create'
                        ? 'Shown as the caption in the lightbox. When you upload several images, this title is used for all of them (tick the box below to number them) - you can change each one later.'
                        : 'Shown as the caption in the lightbox.'),
                TextInput::make('alt_text')->label('Alt text (accessibility / SEO)')->maxLength(200)
                    ->helperText('Short description of the picture for Google and screen readers. Leave empty to use the title.'),
                Toggle::make('number_titles')->label('Number the titles (Title 1, Title 2, Title 3…)')->inline(false)
                    ->visibleOn('create')->dehydrated()->columnSpanFull(),
                Textarea::make('description')->label('Description / caption')->rows(3)->maxLength(1000)->columnSpanFull(),

                Toggle::make('is_featured')->label('Featured')->inline(false),
                Toggle::make('is_published')->label('Published')->default(true)->inline(false),
            ]),
        ]);
    }
}
