<?php

namespace App\Filament\Resources\VideoCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VideoCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, ?string $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            TextInput::make('slug')->required()->maxLength(140)->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('is_published')->label('Published')->default(true)->inline(false),
        ]);
    }
}
