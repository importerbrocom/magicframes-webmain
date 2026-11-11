<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\ManageTestimonials;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('client_name')->required()->maxLength(120),
            TextInput::make('client_role')->label('Role / event')->maxLength(120)->placeholder('e.g. Bride'),
            Select::make('rating')->options([5 => '★★★★★ (5)', 4 => '★★★★☆ (4)', 3 => '★★★☆☆ (3)', 2 => '★★☆☆☆ (2)', 1 => '★☆☆☆☆ (1)'])->default(5)->required()->native(false),
            FileUpload::make('photo_path')->label('Client photograph')->image()->avatar()->disk('public')->directory('testimonials')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->hint('Max file size: 5 MB'),
            Textarea::make('body')->label('Testimonial')->required()->rows(5)->maxLength(1500)->columnSpanFull()
                ->helperText('Only publish genuine client feedback.'),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0),
            Toggle::make('is_published')->label('Published')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')->disk('public')->label('')->circular(),
                TextColumn::make('client_name')->searchable(),
                TextColumn::make('client_role')->toggleable(),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),
                TextColumn::make('body')->limit(60),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->requiresConfirmation()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTestimonials::route('/')];
    }
}
