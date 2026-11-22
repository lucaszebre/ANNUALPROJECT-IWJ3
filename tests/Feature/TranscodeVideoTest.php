<?php

use App\Enums\VideoStatus;
use App\Jobs\TranscodeVideo;
use App\Jobs\TranscribeVideo;
use App\Models\Video;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Fakes ffprobe and ffmpeg. The fake ffmpeg writes small files where the real one would,
 * so the upload, the paths and the sizes are tested for real.
 */
function fakeFfmpeg(int $width = 1920, int $height = 1080, float $duration = 125.4, bool $audio = true, bool $ffmpegFails = false, int $rotation = 0): void
{
    Process::fake(function (PendingProcess $process) use ($width, $height, $duration, $audio, $ffmpegFails, $rotation) {
        $command = $process->command;

        if ($command[0] === 'ffprobe') {
            $streams = [['codec_type' => 'video', 'width' => $width, 'height' => $height, 'side_data_list' => [['rotation' => $rotation]]]];
            if ($audio) {
                $streams[] = ['codec_type' => 'audio'];
            }

            return Process::result(json_encode(['streams' => $streams, 'format' => ['duration' => (string) $duration]]));
        }

        if ($ffmpegFails) {
            return Process::result(errorOutput: 'Invalid data found when processing input', exitCode: 1);
        }

        foreach ($command as $argument) {
            if (str_ends_with($argument, '/index.m3u8')) {
                File::put($argument, "#EXTM3U\n#EXTINF:6.0,\nsegment_000.ts\n#EXT-X-ENDLIST\n");
                File::put(dirname($argument).'/segment_000.ts', str_repeat('s', 1000));
            }
        }

        if (str_ends_with(end($command), '.jpg')) {
            File::put(end($command), str_repeat('j', 100));
        }

        return Process::result();
    });
}

beforeEach(function () {
    Storage::fake('s3');
    Queue::fake([TranscribeVideo::class]);

    $this->video = Video::factory()->create([
        'source_path' => 'organizations/org/videos/abc/source.mp4',
        'size_bytes' => 12,
    ]);
    Storage::disk('s3')->put($this->video->source_path, 'source-bytes');
});

it('transcodes the video to HLS, uploads it and marks the video as ready', function () {
    fakeFfmpeg();

    (new TranscodeVideo($this->video))->handle();

    $video = $this->video->fresh();
    $disk = Storage::disk('s3');

    expect($video->status)->toBe(VideoStatus::Ready)
        ->and($video->hls_path)->toBe('organizations/org/videos/abc/hls/master.m3u8')
        ->and($video->thumbnail_path)->toBe('organizations/org/videos/abc/thumbnail.jpg')
        ->and($video->duration_seconds)->toBe(125)
        ->and($video->ready_at)->not->toBeNull()
        ->and($disk->get($video->hls_path))->toBe(implode("\n", [
            '#EXTM3U',
            '#EXT-X-VERSION:3',
            '#EXT-X-STREAM-INF:BANDWIDTH=928000,RESOLUTION=640x360',
            '360p/index.m3u8',
            '#EXT-X-STREAM-INF:BANDWIDTH=2928000,RESOLUTION=1280x720',
            '720p/index.m3u8',
        ])."\n");

    foreach (['360p', '720p'] as $rendition) {
        $disk->assertExists("organizations/org/videos/abc/hls/{$rendition}/index.m3u8");
        $disk->assertExists("organizations/org/videos/abc/hls/{$rendition}/segment_000.ts");
    }
    $disk->assertExists('organizations/org/videos/abc/thumbnail.jpg');

    expect(File::isDirectory(storage_path("app/transcoding/{$video->id}")))->toBeFalse();
});

it('counts the source, the HLS files and the thumbnail in the size', function () {
    fakeFfmpeg();

    (new TranscodeVideo($this->video))->handle();

    $hls = collect(Storage::disk('s3')->allFiles('organizations/org/videos/abc/hls'))
        ->sum(fn (string $path) => Storage::disk('s3')->size($path));

    expect($this->video->fresh()->size_bytes)->toBe(strlen('source-bytes') + $hls + 100);
});

it('queues the transcription once the video is ready', function () {
    fakeFfmpeg();

    (new TranscodeVideo($this->video))->handle();

    Queue::assertPushedOn('transcription', TranscribeVideo::class, fn (TranscribeVideo $job) => $job->video->is($this->video));
});

it('decodes the source once and keeps the keyframes of every rendition aligned', function () {
    fakeFfmpeg();

    (new TranscodeVideo($this->video))->handle();

    Process::assertRanTimes(fn (PendingProcess $process) => $process->command[0] === 'ffmpeg' && in_array('hls', $process->command), 1);
    Process::assertRan(fn (PendingProcess $process) => in_array('[0:v]split=2[s0][s1];[s0]scale=-2:360[v0];[s1]scale=-2:720[v1]', $process->command)
        && in_array('expr:gte(t,n_forced*6)', $process->command)
        && $process->timeout === config('videos.ffmpeg_timeout_seconds'));
});

it('does not upscale: a 480p source only gets the 360p rendition', function () {
    fakeFfmpeg(width: 854, height: 480);

    (new TranscodeVideo($this->video))->handle();

    expect(Storage::disk('s3')->get('organizations/org/videos/abc/hls/master.m3u8'))
        ->toContain('360p/index.m3u8')
        ->not->toContain('720p');
    Storage::disk('s3')->assertMissing('organizations/org/videos/abc/hls/720p/index.m3u8');
});

it('still makes the smallest rendition from a source smaller than it', function () {
    fakeFfmpeg(width: 426, height: 240);

    (new TranscodeVideo($this->video))->handle();

    expect($this->video->fresh()->status)->toBe(VideoStatus::Ready);
    Storage::disk('s3')->assertExists('organizations/org/videos/abc/hls/360p/index.m3u8');
});

it('transcodes a video without sound', function () {
    fakeFfmpeg(audio: false);

    (new TranscodeVideo($this->video))->handle();

    Process::assertDidntRun(fn (PendingProcess $process) => in_array('0:a:0', $process->command));
    expect(Storage::disk('s3')->get('organizations/org/videos/abc/hls/master.m3u8'))->toContain('BANDWIDTH=800000,');
});

it('leaves the video in processing and throws when ffmpeg fails, so the job is retried', function () {
    fakeFfmpeg(ffmpegFails: true);

    expect(fn () => (new TranscodeVideo($this->video))->handle())->toThrow(ProcessFailedException::class);

    $video = $this->video->fresh();
    expect($video->status)->toBe(VideoStatus::Processing)
        ->and($video->hls_path)->toBeNull()
        ->and(File::isDirectory(storage_path("app/transcoding/{$video->id}")))->toBeFalse();
    Queue::assertNotPushed(TranscribeVideo::class);
});

it('replaces the files a failed attempt left behind', function () {
    Storage::disk('s3')->put('organizations/org/videos/abc/hls/1080p/index.m3u8', 'stale');
    fakeFfmpeg();

    (new TranscodeVideo($this->video))->handle();

    Storage::disk('s3')->assertMissing('organizations/org/videos/abc/hls/1080p/index.m3u8');
});

it('swaps the width and height of a rotated phone video', function () {
    fakeFfmpeg(width: 1920, height: 1080, rotation: -90);

    (new TranscodeVideo($this->video))->handle();

    expect(Storage::disk('s3')->get('organizations/org/videos/abc/hls/master.m3u8'))
        ->toContain('RESOLUTION=202x360')
        ->toContain('RESOLUTION=406x720');
});
