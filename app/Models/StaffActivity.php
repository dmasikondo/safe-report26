<?php

namespace App\Models;

use Database\Factories\StaffActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded change to a staff account: which field, its old and new
 * value (as people read them), and who made the change.
 */
#[Fillable(['user_id', 'actor_id', 'field', 'from_value', 'to_value'])]
class StaffActivity extends Model
{
    /** @use HasFactory<StaffActivityFactory> */
    use HasFactory;

    /**
     * Readable names for the fields that are logged.
     *
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'email' => 'Email address',
        'role' => 'Role',
        'department' => 'Department',
        'supervisor' => 'Supervisor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function fieldLabel(): string
    {
        return __(self::FIELD_LABELS[$this->field] ?? $this->field);
    }
}
