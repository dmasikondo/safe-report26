<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value application settings that administrators change at runtime.
 */
class Setting extends Model
{
    public const AUTO_ASSIGN_CASES = 'auto_assign_cases';

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(self::cacheKey($key), fn () => static::query()->where('key', $key)->first()?->value);

        return $value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::cacheKey($key));
    }

    /**
     * Whether new reports are assigned to an investigator automatically. On by default.
     */
    public static function autoAssignEnabled(): bool
    {
        return (bool) static::get(self::AUTO_ASSIGN_CASES, true);
    }

    private static function cacheKey(string $key): string
    {
        return "settings.{$key}";
    }
}
