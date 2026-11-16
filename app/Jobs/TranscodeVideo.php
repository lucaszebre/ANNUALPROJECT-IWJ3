<?php

namespace App\Jobs;

use App\Enums\JobQueue;
use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Turns an uploaded video into HLS. The ffmpeg pipeline is added with #22.
 */
class TranscodeVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** Seconds to wait before the retry. */
    public int $backoff = 60;

    /** Must stay below the retry_after of the database_long connection. */
    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public function __construct(public Video $video)
    {
        $this->onConnection(JobQueue::Transcoding->connection());
        $this->onQueue(JobQueue::Transcoding->value);
    }

    public function handle(): void
    {
        // At-least-once delivery: a video that is already transcoded is left alone.
        if ($this->video->status === VideoStatus::Ready) {
            return;
        }

        // ffmpeg → HLS, then status ready: #22.
    }

    /**
     * Called once the retries are used up: the teacher sees that the video failed.
     */
    public function failed(?Throwable $exception): void
    {
        $this->video->update(['status' => VideoStatus::Failed]);
    }
}
