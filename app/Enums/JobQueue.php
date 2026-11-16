<?php

namespace App\Enums;

/**
 * The named queues of the single `jobs` table, one worker per queue (ADR-0006).
 */
enum JobQueue: string
{
    case Default = 'default';
    case Transcoding = 'transcoding';
    case Transcription = 'transcription';
    case Sandbox = 'sandbox';

    /**
     * The queue connection, which sets retry_after: long jobs need a longer one.
     */
    public function connection(): string
    {
        return match ($this) {
            self::Transcoding, self::Transcription => 'database_long',
            self::Default, self::Sandbox => 'database',
        };
    }
}
