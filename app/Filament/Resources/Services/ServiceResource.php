<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Services\VideoLinkParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        $img = ['image/jpeg', 'image/png', 'image/webp'];

        return $schema->columns(1)->components([
            Tabs::make('Service')->persistTabInQueryString()->tabs([
                Tab::make('Basics')->icon('heroicon-o-information-circle')->columns(2)->schema([
                    TextInput::make('title')->required()->maxLength(120),
                    TextInput::make('icon')->maxLength(80)->placeholder('fa-solid fa-camera')
                        ->helperText('Optional Font Awesome class shown on the card when no image is set.'),
                    Textarea::make('short_description')->rows(2)->maxLength(400)->helperText('Shown on the service cards and at the top of the service page.')->columnSpanFull(),
                    FileUpload::make('image_path')->label('Main image (card and page banner)')->image()->disk('public')->directory('services')->visibility('public')
                        ->acceptedFileTypes($img)->maxSize(10240)->hint('Max file size: 10 MB')->columnSpanFull(),
                    TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
                    Toggle::make('is_published')->label('Published')->default(true)->inline(false),
                ]),
                Tab::make('Details')->icon('heroicon-o-document-text')->schema([
                    RichEditor::make('description')->label('Full details (writing)')
                        ->helperText('The main text of this service\'s own page: what you offer, how it works, packages, anything you like.'),
                    Repeater::make('highlights')->label('What is included (short list)')->defaultItems(0)->addActionLabel('Add point')->reorderable()->reorderableWithButtons()->collapsible()
                        ->simple(TextInput::make('text')->required()->maxLength(160)->placeholder('e.g. Two photographers for the full day'))
                        ->helperText('Shown as a tick list on the page.'),
                ]),
                Tab::make('Photos')->icon('heroicon-o-photo')->schema([
                    Repeater::make('gallery')->label('Photos for this service')->defaultItems(0)->addActionLabel('Add photo')->reorderable()->reorderableWithButtons()->collapsible()->collapsed(false)
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->columns(2)
                        ->schema([
                            FileUpload::make('image')->label('Photo')->image()->disk('public')->directory('services/gallery')->visibility('public')
                                ->acceptedFileTypes($img)->maxSize(20480)->hint('Max file size: 20 MB')->required()->columnSpanFull(),
                            TextInput::make('title')->label('Title (caption)')->maxLength(160),
                            TextInput::make('alt')->label('Alt text (accessibility / SEO)')->maxLength(200),
                        ])
                        ->helperText('Shown as a photo gallery on the service page. Drag to reorder.'),
                ]),
                Tab::make('Videos')->icon('heroicon-o-film')->schema([
                    Repeater::make('videos')->label('Videos for this service')->defaultItems(0)->addActionLabel('Add video')->reorderable()->reorderableWithButtons()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->columns(2)
                        ->schema([
                            TextInput::make('title')->required()->maxLength(160),
                            Radio::make('source')->label('Source')->options(['link' => 'YouTube / Vimeo link', 'upload' => 'Upload a video file'])->default('link')->inline()->live()->required(),
                            TextInput::make('url')->label('YouTube or Vimeo link')->url()->maxLength(255)->columnSpanFull()
                                ->placeholder('https://www.youtube.com/watch?v=…')
                                ->visible(fn (Get $get) => ($get('source') ?? 'link') === 'link')
                                ->required(fn (Get $get) => ($get('source') ?? 'link') === 'link')
                                ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                                    if ($value && ! VideoLinkParser::parse($value)) {
                                        $fail('Please enter a valid YouTube or Vimeo link.');
                                    }
                                }),
                            FileUpload::make('file')->label('Video file')->disk('public')->directory('services/videos')->visibility('public')
                                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])->maxSize(config('media.max_video_kb'))
                                ->hint('Max file size: '.number_format(config('media.max_video_kb') / 1024).' MB')
                                ->helperText('MP4 (H.264 + AAC) or WebM. It is only loaded when a visitor presses play.')
                                ->visible(fn (Get $get) => ($get('source') ?? 'link') === 'upload')
                                ->required(fn (Get $get) => ($get('source') ?? 'link') === 'upload')->columnSpanFull(),
                            FileUpload::make('thumbnail')->label('Thumbnail (optional)')->image()->disk('public')->directory('services/videos/thumbs')->visibility('public')
                                ->acceptedFileTypes($img)->maxSize(config('media.max_image_kb'))
                                ->hint('Max file size: '.number_format(config('media.max_image_kb') / 1024).' MB')
                                ->helperText('YouTube thumbnails are fetched automatically. Recommended for uploaded files and Vimeo.')->columnSpanFull(),
                            Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull(),
                        ])
                        ->helperText('Shown as a video list on the service page; visitors press play to watch.'),
                ]),
                Tab::make('Extra')->icon('heroicon-o-question-mark-circle')->schema([
                    Repeater::make('faqs')->label('Questions and answers (FAQ)')->defaultItems(0)->addActionLabel('Add question')->reorderable()->reorderableWithButtons()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                        ->schema([
                            TextInput::make('question')->required()->maxLength(200),
                            Textarea::make('answer')->required()->rows(3)->maxLength(1500),
                        ])
                        ->helperText('Shown as an open-and-close list on the page (and helps Google).'),
                ]),
                Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->columns(1)->schema([
                    TextInput::make('slug')->maxLength(120)->helperText('The page address: /services/your-slug. Leave empty to create it from the title.')
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true),
                    TextInput::make('seo_title')->label('Google title')->maxLength(160)->helperText('Leave empty to use the service title.'),
                    Textarea::make('seo_description')->label('Google description')->rows(2)->maxLength(320)->helperText('Leave empty to use the short description.'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->label('')->square()->size(56),
                TextColumn::make('title')->searchable(),
                TextColumn::make('short_description')->limit(60)->toggleable(),
                TextColumn::make('gallery')->label('Photos')->formatStateUsing(fn ($state, $record) => count($record->gallery ?? []))->badge()->color('gray'),
                TextColumn::make('videos')->label('Videos')->formatStateUsing(fn ($state, $record) => count($record->videos ?? []))->badge()->color('gray'),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
