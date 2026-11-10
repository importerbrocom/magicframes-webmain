<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class SiteSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'site-settings';

    public const KEYS = [
        'site.name', 'site.tagline', 'site.effects', 'footer.about', 'footer.copyright',
        'contact.phone', 'contact.whatsapp', 'contact.email', 'contact.address', 'contact.hours', 'contact.map_embed_url',
        'social.instagram', 'social.facebook', 'social.youtube', 'social.pinterest',
        'seo.default_description', 'seo.default_og_image',
    ];

    protected static function permission(): string
    {
        return 'manage settings';
    }

    protected function fields(): array
    {
        return [
            Section::make('Studio')->columns(2)->schema([
                TextInput::make('site.name')->label('Studio name')->required()->maxLength(80),
                TextInput::make('site.tagline')->maxLength(160),
                Select::make('site.effects')->label('Decorative motion effects')->native(false)
                    ->options(['1' => 'On (recommended)', '0' => 'Off'])->selectablePlaceholder(false)
                    ->helperText('Golden sparkles, photo reveals, tilt and hover effects. Visitors who prefer reduced motion never see them. Switch off for the simplest, fastest pages.'),
                Textarea::make('footer.about')->label('Footer description')->rows(2)->maxLength(300)->columnSpanFull(),
                TextInput::make('footer.copyright')->label('Footer copyright text')->maxLength(160)->helperText('The © and year are added automatically.'),
            ]),
            Section::make('Contact details')->description('Shown in the footer and on the Contact page. Leave a field empty to hide it.')->columns(2)->schema([
                TextInput::make('contact.phone')->tel()->maxLength(40),
                TextInput::make('contact.whatsapp')->label('WhatsApp number')->tel()->maxLength(40)->helperText('International format, e.g. +91…'),
                TextInput::make('contact.email')->email()->maxLength(120),
                TextInput::make('contact.hours')->label('Opening hours')->maxLength(120),
                Textarea::make('contact.address')->rows(2)->maxLength(300)->columnSpanFull(),
                TextInput::make('contact.map_embed_url')->label('Google Maps embed URL')->url()->maxLength(600)
                    ->helperText('In Google Maps: Share → Embed a map → copy the src="…" address.')->columnSpanFull(),
            ]),
            Section::make('Social media')->columns(2)->schema([
                TextInput::make('social.instagram')->url()->maxLength(255),
                TextInput::make('social.facebook')->url()->maxLength(255),
                TextInput::make('social.youtube')->url()->maxLength(255),
                TextInput::make('social.pinterest')->url()->maxLength(255),
            ]),
            Section::make('Default SEO')->collapsed()->schema([
                Textarea::make('seo.default_description')->rows(2)->maxLength(320)->label('Default meta description'),
                FileUpload::make('seo.default_og_image')->label('Default social sharing image')->image()->disk('public')->directory('seo')->visibility('public')->maxSize(5120)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
            ]),
        ];
    }

    protected function load(): array
    {
        $values = Setting::allValues();
        $out = [];
        foreach (self::KEYS as $key) {
            data_set($out, $key, $values[$key] ?? null);
        }
        data_set($out, 'site.effects', ($values['site.effects'] ?? null) === '0' ? '0' : '1');

        return $out;
    }

    protected function store(array $data): void
    {
        $flat = [];
        foreach (self::KEYS as $key) {
            $flat[$key] = data_get($data, $key);
        }
        $old = Setting::get('seo.default_og_image');
        if ($old && $old !== ($flat['seo.default_og_image'] ?? null)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }
        Setting::setMany($flat);
    }
}
