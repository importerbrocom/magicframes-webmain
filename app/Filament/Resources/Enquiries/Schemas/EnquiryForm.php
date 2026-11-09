<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Models\Enquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Only the handling fields are editable; what the customer sent is read-only. */
class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Handling')->schema([
                Select::make('status')->options(Enquiry::STATUSES)->required()->native(false),
                Textarea::make('admin_notes')->label('Internal notes')->rows(5)->maxLength(5000)
                    ->helperText('Only visible to admin users.'),
            ]),
        ]);
    }
}
