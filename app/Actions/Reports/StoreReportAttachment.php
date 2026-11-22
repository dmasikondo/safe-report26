<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Support\ReportEncrypter;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cleans, fingerprints, encrypts and stores one evidence file.
 *
 * Images are re-encoded, which drops EXIF/XMP metadata such as GPS
 * location, camera model and capture time. PDFs are stored as uploaded.
 */
class StoreReportAttachment
{
    public function __construct(public ReportEncrypter $encrypter) {}

    public function handle(Report $report, UploadedFile $file): ReportAttachment
    {
        $mimeType = $file->getMimeType();

        if (! array_key_exists($mimeType, ReportAttachment::MIME_EXTENSIONS)) {
            throw new RuntimeException("Unsupported attachment type [{$mimeType}].");
        }

        $contents = str_starts_with($mimeType, 'image/')
            ? $this->stripImageMetadata($file->getRealPath(), $mimeType)
            : file_get_contents($file->getRealPath());

        $path = "reports/{$report->id}/".Str::uuid().'.bin';

        Storage::disk(config('safereport.attachments.disk'))
            ->put($path, $this->encrypter->encryptString($contents));

        return $report->attachments()->create([
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ]);
    }

    /**
     * Re-encode the image from its pixels only, applying the EXIF
     * orientation first so photos are not left sideways.
     */
    private function stripImageMetadata(string $path, string $mimeType): string
    {
        $image = imagecreatefromstring(file_get_contents($path));

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The image could not be read.');
        }

        if ($mimeType === 'image/jpeg') {
            $image = $this->applyOrientation($image, $path);
        }

        if ($mimeType !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        ob_start();

        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, null, 90),
            'image/png' => imagepng($image),
            'image/webp' => imagewebp($image, null, 90),
        };

        return (string) ob_get_clean();
    }

    private function applyOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = function_exists('exif_read_data')
            ? (@exif_read_data($path)['Orientation'] ?? 1)
            : 1;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
