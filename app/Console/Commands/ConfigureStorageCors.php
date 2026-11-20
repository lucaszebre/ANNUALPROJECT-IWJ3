<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lets the browser talk to the bucket from the application's address: teachers upload videos
 * straight to the S3 storage (#21) and the player fetches the HLS segments from it (ADR-0007).
 */
class ConfigureStorageCors extends Command
{
    protected $signature = 'storage:cors {--origin=* : Allowed origins (default: APP_URL)}';

    protected $description = 'Set the CORS rules of the S3 bucket for the application origin';

    public function handle(): int
    {
        $origins = $this->option('origin') ?: [rtrim((string) config('app.url'), '/')];

        $disk = Storage::disk('s3');

        if (! $disk instanceof AwsS3V3Adapter) {
            $this->error('The s3 disk is not an S3 disk.');

            return self::FAILURE;
        }

        try {
            $disk->getClient()->putBucketCors([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedOrigins' => $origins,
                        'AllowedMethods' => ['GET', 'HEAD', 'PUT'],
                        'AllowedHeaders' => ['*'],
                        'ExposeHeaders' => ['ETag'],
                        'MaxAgeSeconds' => 3600,
                    ]],
                ],
            ]);
        } catch (Throwable $exception) {
            $this->error('Could not set the CORS rules: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('CORS rules set for '.implode(', ', $origins).'.');

        return self::SUCCESS;
    }
}
