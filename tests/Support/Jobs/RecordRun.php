<?php

namespace Tests\Support\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Writes down which worker process ran it.
 */
class RecordRun implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $number) {}

    public function handle(): void
    {
        usleep(20_000);

        DB::table('test_job_runs')->insert(['number' => $this->number, 'pid' => getmypid()]);
    }
}
