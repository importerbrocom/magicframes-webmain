<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use App\Models\PageSection;
use App\Services\VideoLinkParser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    private const IMG = ['image/jpeg', 'image/png', 'image/webp'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')->columnSpanFull()->tabs([
                Tab::make('Page')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('title')->required()->maxLength(160)->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, ?string $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')->required()->maxLength(140)->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->notIn(['admin', 'api', 'storage', 'login', 'logout', 'sitemap', 'up', 'blog-post', 'category'])
                            ->disabled(fn (?Page $record) => (bool) $record?->is_system)->dehydrated()
                            ->helperText('Web address of the page, e.g. /privacy-policy. Fixed for built-in pages.'),
                        Select::make('template')->options(Page::TEMPLATES)->required()->native(false)->default('default')
                            ->disabled(fn (?Page $record) => (bool) $record?->is_system)->dehydrated()
                            ->helperText('Built-in pages keep their layout; new pages use “Standard page”.'),
                        Toggle::make('is_published')->label('Published')->default(true)->inline(false),
                    ]),
                    Section::make('Banner')->columns(2)->schema([
                        TextInput::make('banner_label')->maxLength(60)->label('Small script label'),
                        TextInput::make('banner_title')->maxLength(160)->label('Banner heading'),
                        FileUpload::make('banner_image_path')->label('Banner image')->image()->disk('public')->directory('pages')->visibility('public')
                            ->acceptedFileTypes(self::IMG)->maxSize(15360)->columnSpanFull(),
                    ]),
                    Section::make('Main content')->schema([
                        RichEditor::make('content')->label('')->fileAttachmentsDisk('public')->fileAttachmentsDirectory('pages/attachments')->hiddenLabel(),
                    ]),
                ]),
                Tab::make('Content blocks')->schema([
                    Repeater::make('sections')->relationship()->orderColumn('sort_order')->reorderable()->collapsible()->cloneable()
                        ->addActionLabel('Add content block')
                        ->itemLabel(fn (array $state): ?string => ($state['heading'] ?? null) ?: (PageSection::TYPES[$state['type'] ?? ''] ?? 'Block'))
                        ->schema([
                            Select::make('type')->options(PageSection::TYPES)->required()->default('text')->live()->native(false),
                            Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
                            TextInput::make('label')->maxLength(60)->label('Small script label')->visible(fn (Get $g) => in_array($g('type'), ['text', 'image_text', 'cta', 'video'], true)),
                            TextInput::make('heading')->maxLength(160)->visible(fn (Get $g) => $g('type') !== 'image'),
                            RichEditor::make('body')->columnSpanFull()->visible(fn (Get $g) => in_array($g('type'), ['text', 'image_text', 'cta', 'video'], true)),
                            FileUpload::make('image_path')->label('Image')->image()->disk('public')->directory('pages')->visibility('public')
                                ->acceptedFileTypes(self::IMG)->maxSize(15360)
                                ->visible(fn (Get $g) => in_array($g('type'), ['image_text', 'image', 'cta'], true))
                                ->required(fn (Get $g) => in_array($g('type'), ['image_text', 'image'], true)),
                            TextInput::make('image_alt')->maxLength(200)->label('Image description (alt text)')->visible(fn (Get $g) => in_array($g('type'), ['image_text', 'image'], true)),
                            Toggle::make('image_right')->label('Image on the right')->inline(false)->visible(fn (Get $g) => $g('type') === 'image_text'),
                            TextInput::make('video_url')->url()->label('YouTube or Vimeo URL')->maxLength(255)
                                ->visible(fn (Get $g) => $g('type') === 'video')->required(fn (Get $g) => $g('type') === 'video')
                                ->rule(fn () => function (string $a, $v, \Closure $fail) {
                                    if ($v && ! VideoLinkParser::parse($v)) {
                                        $fail('Please enter a valid YouTube or Vimeo link.');
                                    }
                                }),
                            TextInput::make('button_label')->maxLength(40)->visible(fn (Get $g) => in_array($g('type'), ['text', 'image_text', 'cta'], true)),
                            TextInput::make('button_url')->maxLength(255)->placeholder('/contact')->visible(fn (Get $g) => in_array($g('type'), ['text', 'image_text', 'cta'], true)),
                        ])->columns(2),
                ]),
                Tab::make('SEO')->schema([
                    Group::make()->relationship('seo')->columns(1)->schema([
                        TextInput::make('meta_title')->maxLength(70)->helperText('Leave empty to use the page title. Ideal: under 60 characters.'),
                        Textarea::make('meta_description')->rows(3)->maxLength(320)->helperText('Ideal: 120–160 characters.'),
                        FileUpload::make('og_image_path')->label('Social sharing image (Open Graph)')->image()->disk('public')->directory('seo')->visibility('public')
                            ->acceptedFileTypes(self::IMG)->maxSize(5120)->helperText('1200×630 px recommended.'),
                        TextInput::make('canonical_url')->url()->maxLength(255),
                        Toggle::make('noindex')->label('Hide from search engines'),
                    ]),
                ]),
            ]),
        ]);
    }
}
