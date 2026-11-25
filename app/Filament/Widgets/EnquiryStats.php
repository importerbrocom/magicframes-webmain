<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enquiries\EnquiryResource;
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
        // Each box opens the Enquiries list already filtered to that status.
        $link = fn (string $status) => EnquiryResource::getUrl('index', ['filters' => ['status' => ['values' => [$status]]]]);
        $month = EnquiryResource::getUrl('index', ['filters' => ['received' => ['from' => now()->startOfMonth()->toDateString()]]]);

        return [
            Stat::make('New', $count(Enquiry::STATUS_NEW))->description('Waiting for a reply')->color('warning')->url($link(Enquiry::STATUS_NEW)),
            Stat::make('Contacted', $count(Enquiry::STATUS_CONTACTED))->color('info')->url($link(Enquiry::STATUS_CONTACTED)),
            Stat::make('In progress', $count(Enquiry::STATUS_IN_PROGRESS))->color('primary')->url($link(Enquiry::STATUS_IN_PROGRESS)),
            Stat::make('Completed', $count(Enquiry::STATUS_COMPLETED))->color('success')->url($link(Enquiry::STATUS_COMPLETED)),
            Stat::make('Received this month', Enquiry::where('created_at', '>=', now()->startOfMonth())->count())->url($month),
        ];
    }
}
