<?php

namespace App\Filament\Pages;

use App\Models\Header;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class HeaderSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static string|\UnitEnum|null $navigationGroup = 'Header & Menus';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Header settings';

    protected static ?string $title = 'Header settings';

    protected static ?string $slug = 'header-settings';

    protected static function permission(): string
    {
        return 'manage header';
    }

    protected function fields(): array
    {
        return [
            Section::make('Logo & display')->columns(2)->schema([
                FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('branding')->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])->maxSize(2048)
                    ->helperText('Leave empty to use the original Magic Frames logo.'),
                TextInput::make('logo_alt')->label('Logo alt text')->required()->maxLength(120),
                Toggle::make('is_visible')->label('Show header')->inline(false),
                Toggle::make('is_sticky')->label('Sticky header (stays on screen while scrolling)')->inline(false),
                Select::make('background_style')->native(false)->label('Header background')->options([
                    'transparent_on_top' => 'Transparent over the banner, solid after scrolling (original look)',
                    'solid' => 'Always solid',
                ])->required()->columnSpanFull(),
            ]),
            Section::make('Call-to-action button')->columns(2)->schema([
                Toggle::make('show_cta')->label('Show button')->live()->inline(false)->columnSpanFull(),
                TextInput::make('cta_label')->maxLength(30)->required(fn (Get $g) => (bool) $g('show_cta')),
                TextInput::make('cta_url')->maxLength(255)->placeholder('/contact')->required(fn (Get $g) => (bool) $g('show_cta')),
                Toggle::make('show_floating_button')->label('Show floating enquiry button (side of the screen)')->inline(false)->columnSpanFull(),
            ]),
            Section::make('Mobile navigation')->schema([
                Toggle::make('show_social_in_mobile_menu')->label('Show social icons in the mobile menu'),
            ]),
        ];
    }

    protected function load(): array
    {
        return Header::current()->only([
            'logo_path', 'logo_alt', 'is_visible', 'is_sticky', 'background_style', 'show_cta', 'cta_label', 'cta_url',
            'show_floating_button', 'show_social_in_mobile_menu',
        ]);
    }

    protected function store(array $data): void
    {
        $header = Header::current();
        if ($header->logo_path && $header->logo_path !== ($data['logo_path'] ?? null)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($header->logo_path);
        }
        $header->update($data);
    }
}
