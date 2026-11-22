<?php

use App\Enums\VideoStatus;
use App\Jobs\TranscodeVideo;
use App\Jobs\TranscribeVideo;
use App\Models\Video;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Runs the real ffmpeg, which is installed in the Docker images. The feature tests fake it,
 * so this is what checks the command line.
 */
beforeEach(function () {
    $this->input = storage_path('framework/testing/input.mp4');

    if ((new ExecutableFinder)->find('ffmpeg') === null) {
        $this->markTestSkipped('ffmpeg is not installed.');
    }

    Storage::fake('s3');
    Queue::fake([TranscribeVideo::class]);
    File::ensureDirectoryExists(dirname($this->input));
});

afterEach(function () {
    File::delete($this->input);
});

/**
 * A short generated video, so the test needs no fixture file.
 */
function makeTestVideo(string $path, int $width, int $height, bool $audio): void
{
    $command = ['ffmpeg', '-y', '-v', 'error', '-f', 'lavfi', '-i', "testsrc=duration=8:size={$width}x{$height}:rate=25"];

    if ($audio) {
        array_push($command, '-f', 'lavfi', '-i', 'sine=frequency=440:duration=8', '-c:a', 'aac');
    }

    array_push($command, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-shortest', $path);

    Process::run($command)->throw();
}

it('transcodes a real video to playable HLS', function (bool $audio) {
    makeTestVideo($this->input, 1280, 720, $audio);

    $video = Video::factory()->create(['source_path' => 'organizations/org/videos/abc/source.mp4']);
    Storage::disk('s3')->put($video->source_path, File::get($this->input));

    (new TranscodeVideo($video))->handle();

    $video->refresh();
    $disk = Storage::disk('s3');

    expect($video->status)->toBe(VideoStatus::Ready)
        ->and($video->duration_seconds)->toBe(8)
        ->and($disk->get($video->hls_path))->toContain('RESOLUTION=640x360', 'RESOLUTION=1280x720');

    foreach (['360p', '720p'] as $rendition) {
        $playlist = $disk->get("organizations/org/videos/abc/hls/{$rendition}/index.m3u8");

        // 8 s cut every 6 s: two segments, and the player knows the video ends.
        expect($playlist)->toContain('segment_000.ts', 'segment_001.ts', '#EXT-X-ENDLIST')
            ->not->toContain('/');
        $disk->assertExists("organizations/org/videos/abc/hls/{$rendition}/segment_001.ts");
    }

    // The thumbnail is a JPEG.
    expect(substr($disk->get($video->thumbnail_path), 0, 2))->toBe("\xFF\xD8");

    // ffprobe reads back what the player will get.
    $segment = storage_path('framework/testing/segment.ts');
    File::put($segment, $disk->get('organizations/org/videos/abc/hls/360p/segment_000.ts'));
    $streams = json_decode(Process::run(['ffprobe', '-v', 'error', '-print_format', 'json', '-show_streams', $segment])->throw()->output(), true)['streams'];
    File::delete($segment);

    expect(collect($streams)->firstWhere('codec_type', 'video'))->toMatchArray(['codec_name' => 'h264', 'height' => 360])
        ->and(collect($streams)->contains('codec_type', 'audio'))->toBe($audio);
})->with(['with sound' => true, 'without sound' => false]);
