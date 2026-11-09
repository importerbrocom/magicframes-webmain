<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Models\Enquiry;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Received')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('event_type')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('event_date')->date('d M Y')->sortable()->placeholder('—'),
                SelectColumn::make('status')->options(Enquiry::STATUSES)->selectablePlaceholder(false)->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(Enquiry::STATUSES)->multiple(),
                SelectFilter::make('event_type')->options(fn () => array_combine(config('enquiry.event_types'), config('enquiry.event_types'))),
                Filter::make('received')->schema([
                    DatePicker::make('from'), DatePicker::make('until'),
                ])->query(fn (Builder $q, array $data) => $q
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()->requiresConfirmation()])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...collect(Enquiry::STATUSES)->map(fn ($label, $status) => BulkAction::make("mark_$status")
                        ->label("Mark as $label")
                        ->action(fn (Collection $records) => $records->each->update(['status' => $status]))
                        ->deselectRecordsAfterCompletion())->values()->all(),
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }
}
