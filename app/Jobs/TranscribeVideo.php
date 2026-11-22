<?php

namespace App\Jobs;

use App\Enums\JobQueue;
use App\Enums\TranscriptStatus;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Transcribes a ready video with Whisper. Queued by TranscodeVideo; the Whisper part is added with #31.
 */
class TranscribeVideo implements ShouldQueue
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
        $this->onConnection(JobQueue::Transcription->connection());
        $this->onQueue(JobQueue::Transcription->value);
    }

    public function handle(): void
    {
        // At-least-once delivery: a video that is already transcribed is left alone.
        if ($this->video->transcript_status === TranscriptStatus::Ready) {
            return;
        }

        // Whisper → transcript_segments, then transcript_status ready: #31.
    }

    public function failed(?Throwable $exception): void
    {
        $this->video->update(['transcript_status' => TranscriptStatus::Failed]);
    }
}
