<?php

namespace Tests\Support\Jobs;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Follows the job conventions of ADR-0006 and always throws.
 */
class FailingVideoJob implements ShouldQueue
{
    use Queueable;

    public static int $attempts = 0;

    public int $tries = 2;

    public int $backoff = 0;

    public function __construct(public Video $video)
    {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(): void
    {
        self::$attempts++;

        throw new RuntimeException('ffmpeg exited with code 1');
    }

    public function failed(?Throwable $exception): void
    {
        $this->video->update(['status' => VideoStatus::Failed]);
    }
}
