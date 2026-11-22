<?php

namespace App\Actions\Reports;

use App\Support\ReportEncrypter;
use Illuminate\Support\Facades\DB;

/**
 * Converts report content stored as plain text into ciphertext (and back).
 *
 * Works on raw rows so it is unaffected by model casts, and skips values
 * that are already in the target state, so it is safe to run repeatedly.
 */
class EncryptExistingReportData
{
    /**
     * Encrypted columns, per table.
     *
     * @var array<string, list<string>>
     */
    public const COLUMNS = [
        'reports' => [
            'subject',
            'body',
            'incident_date',
            'incident_location',
            'organisation_involved',
            'contact_hint',
            'internal_notes',
        ],
        'report_activities' => [
            'body',
        ],
    ];

    public function __construct(public ReportEncrypter $encrypter) {}

    /**
     * Encrypt every plain-text value. Returns the number of rows changed.
     */
    public function encrypt(): int
    {
        return $this->convert(fn (string $value): ?string => $this->encrypter->canDecrypt($value)
            ? null
            : $this->encrypter->encryptString($value));
    }

    /**
     * Decrypt every encrypted value. Returns the number of rows changed.
     */
    public function decrypt(): int
    {
        return $this->convert(fn (string $value): ?string => $this->encrypter->canDecrypt($value)
            ? $this->encrypter->decryptString($value)
            : null);
    }

    /**
     * @param  callable(string): ?string  $transform  Returns null to leave a value unchanged.
     */
    private function convert(callable $transform): int
    {
        $changedRows = 0;

        foreach (self::COLUMNS as $table => $columns) {
            DB::table($table)
                ->select(['id', ...$columns])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $columns, $transform, &$changedRows): void {
                    foreach ($rows as $row) {
                        $changes = [];

                        foreach ($columns as $column) {
                            if ($row->{$column} === null || $row->{$column} === '') {
                                continue;
                            }

                            $converted = $transform($row->{$column});

                            if ($converted !== null) {
                                $changes[$column] = $converted;
                            }
                        }

                        if ($changes !== []) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                            $changedRows++;
                        }
                    }
                });
        }

        return $changedRows;
    }
}
