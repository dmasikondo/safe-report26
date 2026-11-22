<?php

namespace App\Console\Commands;

use App\Support\ReportEncrypter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('safereport:key {--show : Display the key instead of writing it to .env}')]
#[Description('Generate the key used to encrypt report content and evidence files')]
class GenerateReportKey extends Command
{
    /**
     * Execute the console command. An existing key is never replaced,
     * because every report encrypted with it would become unreadable.
     */
    public function handle(): int
    {
        $key = 'base64:'.base64_encode(ReportEncrypter::generateKey(ReportEncrypter::CIPHER));

        if ($this->option('show')) {
            $this->line($key);

            return self::SUCCESS;
        }

        if (filled(config('safereport.encryption_key'))) {
            $this->error('A report encryption key is already set. Replacing it would make existing reports unreadable.');

            return self::FAILURE;
        }

        $envPath = $this->laravel->environmentFilePath();

        if (! is_file($envPath)) {
            $this->error('No .env file found.');

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);
        $line = 'SAFEREPORT_ENCRYPTION_KEY='.$key;

        $contents = preg_match('/^SAFEREPORT_ENCRYPTION_KEY=.*$/m', $contents)
            ? preg_replace('/^SAFEREPORT_ENCRYPTION_KEY=.*$/m', $line, $contents)
            : rtrim($contents).PHP_EOL.$line.PHP_EOL;

        file_put_contents($envPath, $contents);

        $this->info('Report encryption key written to .env. Back it up safely: without it, reports cannot be decrypted.');

        return self::SUCCESS;
    }
}
