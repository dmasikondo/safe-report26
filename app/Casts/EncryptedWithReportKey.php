<?php

namespace App\Casts;

use App\Support\ReportEncrypter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a string attribute encrypted with the dedicated report key.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedWithReportKey implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : app(ReportEncrypter::class)->decryptString($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : app(ReportEncrypter::class)->encryptString((string) $value);
    }
}
