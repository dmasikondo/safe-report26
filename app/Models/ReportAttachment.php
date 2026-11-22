<?php

namespace App\Models;

use Database\Factories\ReportAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * An evidence file attached to a report, stored encrypted on the private disk.
 */
class ReportAttachment extends Model
{
    /** @use HasFactory<ReportAttachmentFactory> */
    use HasFactory;

    /**
     * Accepted upload types and the extension each one is saved under.
     *
     * @var array<string, string>
     */
    public const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    protected $fillable = [
        'report_id',
        'path',
        'mime_type',
        'size',
        'sha256',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function extension(): string
    {
        return self::MIME_EXTENSIONS[$this->mime_type] ?? 'bin';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * A neutral name such as "evidence-1.pdf", used for downloads.
     */
    public function downloadName(int $position): string
    {
        return "evidence-{$position}.{$this->extension()}";
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
