<?php

namespace App\Actions\Reports;

use App\Models\ReportAttachment;
use App\Support\ReportEncrypter;
use Illuminate\Support\Facades\Storage;

/**
 * Returns the decrypted contents of an evidence file.
 */
class ReadReportAttachment
{
    public function __construct(public ReportEncrypter $encrypter) {}

    public function handle(ReportAttachment $attachment): string
    {
        return $this->encrypter->decryptString(
            Storage::disk(config('safereport.attachments.disk'))->get($attachment->path),
        );
    }
}
