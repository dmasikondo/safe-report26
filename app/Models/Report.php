<?php

namespace App\Models;

use App\Casts\EncryptedWithReportKey;
use App\Enums\ReportActivityTypeEnum;
use App\Enums\ReportCategoryEnum;
use App\Enums\ReportStatusEnum;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /** @var array<int, int> */
    public const PRIORITIES = [1, 2, 3];

    /**
     * Characters used in case references: no 0/O or 1/I, so a reference
     * can be read out over the phone without confusion.
     */
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Mirror the column defaults so a freshly created model is usable
     * without reloading it from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'submitted',
        'priority' => 2,
    ];

    protected $fillable = [
        'tracking_token',
        'category',
        'department_id',
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
            'department_id' => 'integer',
            'subject' => EncryptedWithReportKey::class,
            'body' => EncryptedWithReportKey::class,
            'incident_date' => EncryptedWithReportKey::class,
            'incident_location' => EncryptedWithReportKey::class,
            'organisation_involved' => EncryptedWithReportKey::class,
            'contact_hint' => EncryptedWithReportKey::class,
            'internal_notes' => EncryptedWithReportKey::class,
            'status' => ReportStatusEnum::class,
            'priority' => 'integer',
            'assigned_to' => 'integer',
        ];
    }

    // ── Route key: random uuid instead of the numeric id ─────────────────

    /**
     * The numeric id stays the primary key; the uuid column is filled automatically
     * and used in URLs instead, so case links cannot be guessed or enumerated.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Fully random (version 4) so the identifier does not reveal when the report was filed.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Boot: auto-generate tracking token and case reference ────────────

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            if (empty($report->tracking_token)) {
                $report->tracking_token = strtoupper(Str::random(8).'-'.Str::random(8));
            }

            if (empty($report->reference)) {
                $report->reference = self::generateUniqueReference();
            }
        });
    }

    /**
     * A short random reference such as WB-7K3QXM. Staff use it to refer to a
     * case; it reveals nothing about case volume and, unlike the tracking
     * token, gives no access to the public tracking page.
     */
    public static function generateUniqueReference(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }

            $reference = 'WB-'.$code;
        } while (self::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class)->orderBy('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ReportActivity::class)->latest()->latest('id');
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

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to !== null && $this->assigned_to === $user->id;
    }

    public function advance(): void
    {
        $timeline = ReportStatusEnum::timeline();
        $current = array_search($this->status, $timeline, strict: true);

        if ($current !== false && isset($timeline[$current + 1])) {
            $this->update(['status' => $timeline[$current + 1]]);
        }
    }

    // ── Case workflow actions (each one is recorded in the activity log) ──

    /**
     * Move the case to a new status. Authorization is the caller's job;
     * this only enforces that the transition itself is valid.
     */
    public function transitionTo(ReportStatusEnum $target, User $actor, ?string $reason = null): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "A report cannot move from {$this->status->label()} to {$target->label()}."
            );
        }

        $this->recordChange(
            type: ReportActivityTypeEnum::STATUS_CHANGED,
            actor: $actor,
            attributes: ['status' => $target],
            fromValue: $this->status->value,
            toValue: $target->value,
            body: $reason,
        );
    }

    /**
     * Assign the case to a staff member, or pass null to unassign it.
     * An optional reason is kept in the case log, e.g. why someone from the
     * case's own department was chosen.
     */
    public function assignTo(?User $assignee, ?User $actor, ?string $reason = null): void
    {
        if ($assignee?->id === $this->assigned_to) {
            return;
        }

        $this->recordChange(
            type: ReportActivityTypeEnum::ASSIGNED,
            actor: $actor,
            attributes: ['assigned_to' => $assignee?->id],
            fromValue: $this->assigned_to,
            toValue: $assignee?->id,
            body: $reason,
        );
    }

    public function changePriority(int $priority, User $actor): void
    {
        if (! in_array($priority, self::PRIORITIES, strict: true)) {
            throw new InvalidArgumentException("Invalid priority [{$priority}].");
        }

        if ($priority === $this->priority) {
            return;
        }

        $this->recordChange(
            type: ReportActivityTypeEnum::PRIORITY_CHANGED,
            actor: $actor,
            attributes: ['priority' => $priority],
            fromValue: $this->priority,
            toValue: $priority,
        );
    }

    public function addNote(string $body, User $actor): ReportActivity
    {
        return $this->activities()->create([
            'user_id' => $actor->id,
            'type' => ReportActivityTypeEnum::NOTE_ADDED,
            'body' => $body,
        ]);
    }

    /**
     * Update the report and write the matching activity entry atomically.
     * A null actor means the system made the change.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function recordChange(
        ReportActivityTypeEnum $type,
        ?User $actor,
        array $attributes,
        string|int|null $fromValue,
        string|int|null $toValue,
        ?string $body = null,
    ): void {
        DB::transaction(function () use ($type, $actor, $attributes, $fromValue, $toValue, $body) {
            $this->update($attributes);

            $this->activities()->create([
                'user_id' => $actor?->id,
                'is_automatic' => $actor === null,
                'type' => $type,
                'from_value' => $fromValue,
                'to_value' => $toValue,
                'body' => $body,
            ]);
        });
    }

    // ── Priority helpers ──────────────────────────────────────────────────

    public static function labelForPriority(int $priority): string
    {
        return match ($priority) {
            1 => 'Low',
            3 => 'High',
            default => 'Medium',
        };
    }

    public function priorityLabel(): string
    {
        return self::labelForPriority($this->priority);
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

    /**
     * Limit the query to reports the given staff member may see:
     * supervisors and above see every case, investigators only their own.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSupervisor()) {
            return $query;
        }

        return $query->where('assigned_to', $user->id);
    }
}
