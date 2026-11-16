<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Jobs\RecordRun;

beforeEach(function () {
    Schema::dropIfExists('test_job_runs');
    Schema::create('test_job_runs', function (Blueprint $table) {
        $table->unsignedInteger('number');
        $table->unsignedInteger('pid');
    });
});

afterEach(function () {
    Schema::dropIfExists('test_job_runs');
});

it('runs each job exactly once with two workers on the same queue', function () {
    foreach (range(1, 100) as $number) {
        RecordRun::dispatch($number)->onConnection('database')->onQueue('default');
    }

    $worker = Process::path(base_path())
        ->env([
            'APP_ENV' => 'testing',
            'DB_DATABASE' => config('database.connections.pgsql.database'),
            'CACHE_STORE' => 'array',
        ])
        ->timeout(120);

    $workers = [
        $worker->start('php artisan queue:work database --queue=default --stop-when-empty --sleep=0'),
        $worker->start('php artisan queue:work database --queue=default --stop-when-empty --sleep=0'),
    ];

    foreach ($workers as $process) {
        expect($process->wait()->successful())->toBeTrue();
    }

    $runs = DB::table('test_job_runs')->get();

    expect($runs)->toHaveCount(100)
        ->and($runs->pluck('number')->unique())->toHaveCount(100)
        ->and($runs->pluck('pid')->unique())->toHaveCount(2)
        ->and(DB::table('jobs')->count())->toBe(0);
});
