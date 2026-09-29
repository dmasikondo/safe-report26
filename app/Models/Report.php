<?php

namespace App\Models;

use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tracking_token',
        'category',
        'status',
        'subject',
        'body',
        'incident_date',
        'incident_location',
        'organisation_involved',
        'contact_hint',
        'assigned_to',
        'internal_notes',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'category' => ReportCategoryEnum::class,
            'status'   => ReportStatusEnum::class,
            'priority' => 'integer',
        ];
    }

    // ── Boot: auto-generate tracking token ───────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            if (empty($report->tracking_token)) {
                $report->tracking_token = strtoupper(Str::random(8) . '-' . Str::random(8));
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ── Status helpers ────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status->isActive();
    }

    public function isClosed(): bool
    {
        return ! $this->status->isActive();
    }

    public function advance(): void
    {
        $timeline = ReportStatusEnum::timeline();
        $current  = array_search($this->status, $timeline, strict: true);

        if ($current !== false && isset($timeline[$current + 1])) {
            $this->update(['status' => $timeline[$current + 1]]);
        }
    }

    // ── Priority helpers ──────────────────────────────────────────────────

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            1 => 'Low',
            3 => 'High',
            default => 'Medium',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            1 => 'zinc',
            3 => 'red',
            default => 'amber',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            ReportStatusEnum::RESOLVED->value,
            ReportStatusEnum::CLOSED->value,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            ReportStatusEnum::RESOLVED->value,
            ReportStatusEnum::CLOSED->value,
        ]);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeForCategory($query, ReportCategoryEnum $category)
    {
        return $query->where('category', $category->value);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 3);
    }
}
