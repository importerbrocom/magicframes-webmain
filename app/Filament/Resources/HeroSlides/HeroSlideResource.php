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
use App\Services\VideoLinkParser;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
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
            FileUpload::make('video_path')->label('Background video (optional)')->disk('public')->directory('hero/video')->visibility('public')
                ->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(config('media.max_hero_video_kb'))
                ->hint('Max file size: '.number_format(config('media.max_hero_video_kb') / 1024).' MB')
                ->helperText('MP4 or WebM. Plays automatically, muted and on a loop behind the headline. Keep it short (10–30 seconds) and compressed for fast loading. Use the image below as the picture shown while the video loads.')
                ->columnSpanFull(),
            TextInput::make('video_link')->label('…or a video link (YouTube, Vimeo or Google Drive)')->url()->maxLength(500)
                ->placeholder('https://www.youtube.com/watch?v=… or https://drive.google.com/file/d/…/view')
                ->helperText('Used when no video file is uploaded above. YouTube and Vimeo play automatically, muted and looping. Google Drive: the file must be shared as "Anyone with the link"; Drive shows its own player, so visitors may need to press play (the browser/Drive decides) - for guaranteed autoplay use YouTube or upload the file.')
                ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                    if ($value && ! VideoLinkParser::heroEmbed($value)) {
                        $fail('Please enter a valid YouTube, Vimeo or Google Drive video link.');
                    }
                })
                ->columnSpanFull(),
            FileUpload::make('image_path')->label('Hero image (wide, 1900px+ recommended)')->image()->disk('public')->directory('hero')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)
                ->hint('Max file size: 15 MB')
                ->required(fn (Get $get) => blank($get('video_path')) && blank($get('video_link')))
                ->helperText('Shown as the slide picture. If a video is added above, this is its poster image (recommended).')
                ->columnSpanFull(),
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
                TextColumn::make('video_path')->label('Video')->badge()->default('')->formatStateUsing(fn ($state, $record) => ($state || $record?->video_link) ? 'Video' : '')->color('warning')->toggleable(),
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
