<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\RoleEnum;

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
