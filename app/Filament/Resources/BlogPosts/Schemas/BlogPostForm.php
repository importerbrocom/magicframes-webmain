<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        $img = ['image/jpeg', 'image/png', 'image/webp'];

        return $schema->components([
            Tabs::make('Post')->columnSpanFull()->tabs([
                Tab::make('Article')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('title')->required()->maxLength(180)->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, ?string $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')->required()->maxLength(200)->alphaDash()->unique(ignoreRecord: true)
                            ->helperText('Web address: /blog/your-slug'),
                        TextInput::make('category')->maxLength(80)->placeholder('e.g. Real Weddings')->datalist(fn () => \App\Models\BlogPost::whereNotNull('category')->distinct()->pluck('category')->all()),
                        TextInput::make('author')->maxLength(120),
                        Textarea::make('excerpt')->rows(2)->maxLength(500)->columnSpanFull()
                            ->helperText('Short summary on the blog list. Leave empty to use the start of the article.'),
                        FileUpload::make('cover_path')->label('Cover image')->image()->disk('public')->directory('blog')->visibility('public')
                            ->acceptedFileTypes($img)->maxSize(15360)->hint('Max file size: 15 MB')->columnSpanFull(),
                        TextInput::make('cover_alt')->label('Cover image description (alt text)')->maxLength(200)->columnSpanFull(),
                    ]),
                    Section::make('Article text')->schema([
                        RichEditor::make('content')->hiddenLabel()->required()
                            ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('blog/attachments'),
                    ]),
                ]),
                Tab::make('Publishing')->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('is_published')->label('Published')->inline(false)
                            ->helperText('Off = draft, not visible on the website.'),
                        DateTimePicker::make('published_at')->label('Publish date')->native(false)
                            ->helperText('Leave empty to publish immediately. A future date schedules the post (it appears within about 15 minutes of that time).'),
                    ]),
                ]),
                Tab::make('SEO')->schema([
                    Group::make()->relationship('seo')->columns(1)->schema([
                        TextInput::make('meta_title')->maxLength(70)->helperText('Leave empty to use the post title.'),
                        Textarea::make('meta_description')->rows(3)->maxLength(320),
                        FileUpload::make('og_image_path')->label('Social sharing image')->image()->disk('public')->directory('seo')->visibility('public')
                            ->acceptedFileTypes($img)->maxSize(5120)->hint('Max file size: 5 MB')->helperText('Defaults to the cover image.'),
                        Toggle::make('noindex')->label('Hide from search engines'),
                    ]),
                ]),
            ]),
        ]);
    }
}
