<?php

namespace App\Jobs;

use App\Enums\JobQueue;
use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

/**
 * Turns an uploaded video into HLS, next to its source on the s3 disk:
 *
 *     organizations/{org}/videos/{uuid}/source.mp4
 *     organizations/{org}/videos/{uuid}/thumbnail.jpg
 *     organizations/{org}/videos/{uuid}/hls/master.m3u8
 *     organizations/{org}/videos/{uuid}/hls/360p/index.m3u8 + segment_000.ts ...
 *
 * The video only becomes ready (visible to students) once every file is uploaded.
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

        $this->video->update(['status' => VideoStatus::Processing]);

        $workDir = storage_path("app/transcoding/{$this->video->id}");
        File::deleteDirectory($workDir);
        File::ensureDirectoryExists("{$workDir}/hls");

        try {
            $source = $this->download($workDir);
            $probe = $this->probe($source);
            $renditions = $this->renditionsFor($probe);

            $this->transcode($source, "{$workDir}/hls", $renditions, $probe['has_audio']);
            File::put("{$workDir}/hls/master.m3u8", $this->masterPlaylist($renditions, $probe));
            $this->thumbnail($source, "{$workDir}/thumbnail.jpg", $probe['duration']);

            $directory = dirname($this->video->source_path);
            $hlsBytes = $this->upload("{$workDir}/hls", "{$directory}/hls");
            $this->putFile("{$workDir}/thumbnail.jpg", "{$directory}/thumbnail.jpg");

            $this->video->update([
                'status' => VideoStatus::Ready,
                'hls_path' => "{$directory}/hls/master.m3u8",
                'thumbnail_path' => "{$directory}/thumbnail.jpg",
                'duration_seconds' => (int) round($probe['duration']),
                // Everything the video takes on the storage, for the organisation quota.
                'size_bytes' => File::size($source) + $hlsBytes + File::size("{$workDir}/thumbnail.jpg"),
                'ready_at' => now(),
            ]);
        } finally {
            File::deleteDirectory($workDir);
        }

        TranscribeVideo::dispatch($this->video);
    }

    /**
     * Called once the retries are used up: the teacher sees that the video failed.
     */
    public function failed(?Throwable $exception): void
    {
        $this->video->update(['status' => VideoStatus::Failed]);
    }

    /**
     * Copies the source to the worker's disk as a stream: it can be several GB.
     */
    private function download(string $workDir): string
    {
        $local = "{$workDir}/source.".pathinfo($this->video->source_path, PATHINFO_EXTENSION);
        $input = Storage::disk('s3')->readStream($this->video->source_path);

        if (! is_resource($input)) {
            throw new RuntimeException("Cannot read the source of video {$this->video->id}.");
        }

        $output = fopen($local, 'wb');

        try {
            stream_copy_to_stream($input, $output);
        } finally {
            fclose($input);
            fclose($output);
        }

        return $local;
    }

    /**
     * @return array{width: int, height: int, duration: float, has_audio: bool}
     */
    private function probe(string $source): array
    {
        $result = Process::timeout(120)->run([
            'ffprobe', '-v', 'error', '-print_format', 'json', '-show_format', '-show_streams', $source,
        ])->throw();

        $data = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
        $streams = collect($data['streams'] ?? []);
        $video = $streams->firstWhere('codec_type', 'video');

        if ($video === null) {
            throw new RuntimeException("The source of video {$this->video->id} has no video stream.");
        }

        $width = (int) $video['width'];
        $height = (int) $video['height'];

        // Phone videos are often stored landscape with a rotation; ffmpeg applies it.
        $rotation = $video['tags']['rotate'] ?? collect($video['side_data_list'] ?? [])->whereNotNull('rotation')->value('rotation') ?? 0;
        if (abs((int) $rotation) % 180 === 90) {
            [$width, $height] = [$height, $width];
        }

        return [
            'width' => $width,
            'height' => $height,
            'duration' => (float) ($data['format']['duration'] ?? $video['duration'] ?? 0),
            'has_audio' => $streams->contains('codec_type', 'audio'),
        ];
    }

    /**
     * The renditions not taller than the source, always keeping the smallest one.
     *
     * @param  array{width: int, height: int, duration: float, has_audio: bool}  $probe
     * @return array<string, array{height: int, width: int, video_bitrate: int}>
     */
    private function renditionsFor(array $probe): array
    {
        /** @var array<string, array{height: int, video_bitrate: int}> $all */
        $all = config('videos.renditions');

        $kept = array_filter($all, fn (array $rendition) => $rendition['height'] <= $probe['height']) ?: array_slice($all, 0, 1, true);

        return array_map(fn (array $rendition) => $rendition + [
            // Same width as ffmpeg's scale=-2:height: keeps the ratio, rounded to an even number.
            'width' => (int) (2 * round($probe['width'] * $rendition['height'] / $probe['height'] / 2)),
        ], $kept);
    }

    /**
     * One ffmpeg run decodes the source once and writes every rendition.
     * Keyframes are forced on the same times in every rendition so the player can switch.
     *
     * @param  array<string, array{height: int, width: int, video_bitrate: int}>  $renditions
     */
    private function transcode(string $source, string $outputDir, array $renditions, bool $hasAudio): void
    {
        $segmentSeconds = (int) config('videos.segment_seconds');
        $names = array_keys($renditions);

        $filter = '[0:v]split='.count($names).implode('', array_map(fn ($i) => "[s{$i}]", array_keys($names)));
        foreach ($names as $i => $name) {
            $filter .= ";[s{$i}]scale=-2:{$renditions[$name]['height']}[v{$i}]";
        }

        $command = ['ffmpeg', '-y', '-v', 'error', '-i', $source, '-filter_complex', $filter];

        foreach ($names as $i => $name) {
            $bitrate = $renditions[$name]['video_bitrate'];
            File::ensureDirectoryExists("{$outputDir}/{$name}");

            array_push($command,
                '-map', "[v{$i}]",
                '-c:v', 'libx264', '-preset', 'veryfast', '-profile:v', 'main', '-pix_fmt', 'yuv420p',
                '-b:v', "{$bitrate}k", '-maxrate', (int) ($bitrate * 1.07).'k', '-bufsize', ($bitrate * 2).'k',
                '-force_key_frames', "expr:gte(t,n_forced*{$segmentSeconds})", '-sc_threshold', '0',
            );

            if ($hasAudio) {
                array_push($command, '-map', '0:a:0', '-c:a', 'aac', '-b:a', config('videos.audio_bitrate').'k', '-ac', '2');
            }

            array_push($command,
                '-f', 'hls', '-hls_time', (string) $segmentSeconds, '-hls_playlist_type', 'vod',
                '-hls_segment_filename', "{$outputDir}/{$name}/segment_%03d.ts",
                "{$outputDir}/{$name}/index.m3u8",
            );
        }

        Process::timeout((int) config('videos.ffmpeg_timeout_seconds'))->run($command)->throw();
    }

    /**
     * Relative URIs: the playlist controller serves the master and the renditions side by side.
     *
     * @param  array<string, array{height: int, width: int, video_bitrate: int}>  $renditions
     * @param  array{width: int, height: int, duration: float, has_audio: bool}  $probe
     */
    private function masterPlaylist(array $renditions, array $probe): string
    {
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];
        $audioBitrate = $probe['has_audio'] ? (int) config('videos.audio_bitrate') : 0;

        foreach ($renditions as $name => $rendition) {
            $bandwidth = ($rendition['video_bitrate'] + $audioBitrate) * 1000;
            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$rendition['width']}x{$rendition['height']}";
            $lines[] = "{$name}/index.m3u8";
        }

        return implode("\n", $lines)."\n";
    }

    private function thumbnail(string $source, string $output, float $duration): void
    {
        // One second in, to skip a black first frame, unless the video is shorter than that.
        $at = $duration > 2 ? '1' : '0';

        Process::timeout(120)->run([
            'ffmpeg', '-y', '-v', 'error', '-ss', $at, '-i', $source,
            '-frames:v', '1', '-vf', 'scale=-2:360', '-q:v', '3', $output,
        ])->throw();
    }

    /**
     * Replaces the HLS directory on the s3 disk (a retry may have left part of one).
     *
     * @return int the bytes uploaded
     */
    private function upload(string $localDir, string $remoteDir): int
    {
        Storage::disk('s3')->deleteDirectory($remoteDir);

        $bytes = 0;

        /** @var SplFileInfo $file */
        foreach (File::allFiles($localDir) as $file) {
            $this->putFile($file->getPathname(), "{$remoteDir}/{$file->getRelativePathname()}");
            $bytes += $file->getSize();
        }

        return $bytes;
    }

    private function putFile(string $local, string $remote): void
    {
        $contentType = match (pathinfo($local, PATHINFO_EXTENSION)) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'jpg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        $stream = fopen($local, 'rb');

        try {
            if (! Storage::disk('s3')->writeStream($remote, $stream, ['ContentType' => $contentType])) {
                throw new RuntimeException("Cannot upload {$remote}.");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
