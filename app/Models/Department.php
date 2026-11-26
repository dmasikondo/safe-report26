<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Supervisors and investigators who work in this department.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Alphabetical order, as shown in the report form and staff filters.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
