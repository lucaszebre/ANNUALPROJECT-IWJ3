<?php

use App\Enums\JobQueue;
use App\Enums\VideoStatus;
use App\Jobs\TranscodeVideo;
use App\Models\Video;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\Jobs\FailingVideoJob;

it('gives every long job a timeout shorter than the retry_after of its connection', function () {
    $job = new TranscodeVideo(Video::factory()->make());

    expect($job->connection)->toBe('database_long')
        ->and($job->queue)->toBe(JobQueue::Transcoding->value)
        ->and($job->timeout)->toBeLessThan(config("queue.connections.{$job->connection}.retry_after"));
});

it('puts each named queue on a database connection', function (JobQueue $queue) {
    expect(config("queue.connections.{$queue->connection()}.driver"))->toBe('database');
})->with(JobQueue::cases());

it('only queues a job once its transaction commits', function () {
    $video = Video::factory()->create();

    DB::transaction(function () use ($video) {
        TranscodeVideo::dispatch($video);

        // Still inside the transaction: a worker must not be able to take the job yet.
        expect(DB::table('jobs')->count())->toBe(0);
    });

    expect(DB::table('jobs')->where('queue', 'transcoding')->count())->toBe(1);
});

it('retries a failing job, then stores it in failed_jobs and marks its video as failed', function () {
    FailingVideoJob::$attempts = 0;
    $video = Video::factory()->create(['status' => VideoStatus::Processing]);

    FailingVideoJob::dispatch($video);
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'default', '--stop-when-empty' => true, '--sleep' => 0]);

    expect(FailingVideoJob::$attempts)->toBe(2)
        ->and(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(1)
        ->and($video->fresh()->status)->toBe(VideoStatus::Failed);
});

it('marks the video as failed when transcoding gives up', function () {
    $video = Video::factory()->create(['status' => VideoStatus::Processing]);

    (new TranscodeVideo($video))->failed(new RuntimeException('ffmpeg exited with code 1'));

    expect($video->fresh()->status)->toBe(VideoStatus::Failed);
});

it('leaves an already transcoded video alone when the job runs again', function () {
    $video = Video::factory()->ready()->create();

    (new TranscodeVideo($video))->handle();

    expect($video->fresh()->status)->toBe(VideoStatus::Ready);
});
