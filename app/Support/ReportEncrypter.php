<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Encrypts report content and evidence files with a key kept separate
 * from APP_KEY, so rotating the application key can never make reports
 * unreadable. Uses Laravel's AES-256-CBC with an HMAC signature.
 */
class ReportEncrypter extends Encrypter
{
    public const CIPHER = 'AES-256-CBC';

    public static function fromConfig(?string $configuredKey): self
    {
        if (blank($configuredKey)) {
            throw new RuntimeException(
                'SAFEREPORT_ENCRYPTION_KEY is not set. Generate one with: php artisan safereport:key'
            );
        }

        $key = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), strict: true)
            : $configuredKey;

        if ($key === false || ! static::supported($key, self::CIPHER)) {
            throw new RuntimeException('SAFEREPORT_ENCRYPTION_KEY must be a 32-byte key (base64: prefixed).');
        }

        return new self($key, self::CIPHER);
    }

    /**
     * Whether the value is ciphertext this encrypter can open.
     */
    public function canDecrypt(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        try {
            $this->decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
