<?php

namespace App\Models;

use App\Casts\EncryptedWithReportKey;
use App\Enums\ReportActivityTypeEnum;
use Database\Factories\ReportActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportActivity extends Model
{
    /** @use HasFactory<ReportActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'type',
        'from_value',
        'to_value',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReportActivityTypeEnum::class,
            'body' => EncryptedWithReportKey::class,
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
