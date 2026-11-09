<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Models\Enquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable()->url(fn (Enquiry $r) => 'mailto:'.$r->email),
                TextEntry::make('phone')->placeholder('—')->copyable()->url(fn (Enquiry $r) => $r->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $r->phone) : null),
            ]),
            Section::make('Event')->columns(3)->schema([
                TextEntry::make('event_type')->placeholder('—'),
                TextEntry::make('event_date')->date('d M Y')->placeholder('—'),
                TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y, H:i'),
                TextEntry::make('message')->columnSpanFull()->prose(),
            ]),
            Section::make('Handling')->columns(3)->schema([
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn ($state) => Enquiry::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        Enquiry::STATUS_NEW => 'warning', Enquiry::STATUS_CONTACTED => 'info',
                        Enquiry::STATUS_IN_PROGRESS => 'primary', default => 'success',
                    }),
                TextEntry::make('status_changed_at')->dateTime('d M Y, H:i')->placeholder('—'),
                TextEntry::make('source_page')->placeholder('—'),
                TextEntry::make('admin_notes')->placeholder('No notes yet')->columnSpanFull(),
            ]),
        ]);
    }
}
