<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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
        return $schema->columns(2)->components([
            TextInput::make('title')->required()->maxLength(120),
            TextInput::make('icon')->maxLength(80)->placeholder('fa-solid fa-camera')
                ->helperText('Optional Font Awesome class shown when no image is set.'),
            Textarea::make('short_description')->rows(2)->maxLength(400)->helperText('Shown on cards (home page & services list).')->columnSpanFull(),
            RichEditor::make('description')->columnSpanFull(),
            FileUpload::make('image_path')->label('Image')->image()->disk('public')->directory('services')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(10240)->hint('Max file size: 10 MB')->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('is_published')->label('Published')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->label('')->square()->size(56),
                TextColumn::make('title')->searchable(),
                TextColumn::make('short_description')->limit(60)->toggleable(),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
