<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'email', 'phone', 'event_type', 'event_date', 'message', 'status', 'admin_notes',
    'source_page', 'ip_address', 'user_agent', 'status_changed_at',
])]
class Enquiry extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'status_changed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $enquiry) {
            if ($enquiry->isDirty('status')) {
                $enquiry->status_changed_at = now();
            }
        });
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
