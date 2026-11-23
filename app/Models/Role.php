<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected function casts(): array
    {
        return [
            'name' => RoleEnum::class,
        ];
    }

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    // ── Relationship ──────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
