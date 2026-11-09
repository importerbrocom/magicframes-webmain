<?php

namespace App\Filament\Widgets;

use App\Models\Enquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EnquiryStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Enquiries';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('manage enquiries');
    }

    protected function getStats(): array
    {
        $count = fn (string $status) => Enquiry::status($status)->count();

        return [
            Stat::make('New', $count(Enquiry::STATUS_NEW))->description('Waiting for a reply')->color('warning'),
            Stat::make('Contacted', $count(Enquiry::STATUS_CONTACTED))->color('info'),
            Stat::make('In progress', $count(Enquiry::STATUS_IN_PROGRESS))->color('primary'),
            Stat::make('Completed', $count(Enquiry::STATUS_COMPLETED))->color('success'),
            Stat::make('Received this month', Enquiry::where('created_at', '>=', now()->startOfMonth())->count()),
        ];
    }
}
