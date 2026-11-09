<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    /** Exports exactly what the current filters/search show. */
    public function exportCsv(): StreamedResponse
    {
        $query = $this->getFilteredSortedTableQuery();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, ['Received', 'Name', 'Email', 'Phone', 'Event type', 'Event date', 'Status', 'Message', 'Notes']);
            $query->clone()->reorder('created_at', 'desc')->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, array_map([self::class, 'safeCell'], [
                        $e->created_at->format('Y-m-d H:i'), $e->name, $e->email, $e->phone, $e->event_type,
                        $e->event_date?->format('Y-m-d'), $e->status_label, $e->message, $e->admin_notes,
                    ]));
                }
            });
            fclose($out);
        }, 'enquiries-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise spreadsheet formula injection (=, +, -, @ at the start of a cell). */
    public static function safeCell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
