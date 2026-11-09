<?php

namespace App\Filament\Resources\Videos\Schemas;

use App\Services\VideoLinkParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(160),
                Select::make('video_category_id')->label('Category')
                    ->relationship('category', 'name')->searchable()->preload()
                    ->createOptionForm([TextInput::make('name')->required()->maxLength(120)]),
                Textarea::make('description')->rows(3)->maxLength(2000)->columnSpanFull(),
            ]),
            Section::make('Video source')->columns(1)->schema([
                Radio::make('source_type')->label('Source')->required()->live()->inline()
                    ->options(['youtube' => 'YouTube / Vimeo link', 'upload' => 'Upload a video file'])
                    ->afterStateHydrated(fn ($component, $state) => $component->state($state === 'vimeo' ? 'youtube' : ($state ?: 'youtube')))
                    ->default('youtube'),
                TextInput::make('video_url')->label('YouTube or Vimeo URL')->url()->maxLength(255)
                    ->placeholder('https://www.youtube.com/watch?v=… or https://vimeo.com/…')
                    ->visible(fn (Get $get) => $get('source_type') !== 'upload')
                    ->required(fn (Get $get) => $get('source_type') !== 'upload')
                    ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                        if ($value && ! VideoLinkParser::parse($value)) {
                            $fail('Please enter a valid YouTube or Vimeo video link.');
                        }
                    }),
                FileUpload::make('video_path')->label('Video file')
                    ->helperText('MP4, WebM or MOV. Max '.number_format(config('media.max_video_kb') / 1024).' MB. Large files are only loaded when a visitor presses play.')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                    ->maxSize(config('media.max_video_kb'))
                    ->disk('public')->directory('videos')->visibility('public')
                    ->visible(fn (Get $get) => $get('source_type') === 'upload')
                    ->required(fn (Get $get) => $get('source_type') === 'upload'),
                FileUpload::make('thumbnail_path')->label('Thumbnail')
                    ->helperText('Recommended for uploaded videos. For YouTube links it is fetched automatically if left empty.')
                    ->image()->imagePreviewHeight('160')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(config('media.max_image_kb'))
                    ->disk('public')->directory('videos/thumbnails')->visibility('public'),
            ]),
            Section::make('Display')->columns(3)->schema([
                TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
                Toggle::make('is_featured')->label('Featured')->inline(false),
                Toggle::make('is_published')->label('Published')->default(true)->inline(false),
            ]),
        ]);
    }
}
