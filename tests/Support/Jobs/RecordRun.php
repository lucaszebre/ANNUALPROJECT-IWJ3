<?php

namespace Tests\Support\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Writes down that it ran. The short sleep keeps both workers busy at the
 * same time, so a job taken by both of them would show up twice.
 */
class RecordRun implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $number) {}

    public function handle(): void
    {
        usleep(20_000);

        DB::table('test_job_runs')->insert(['number' => $this->number]);
    }
}
