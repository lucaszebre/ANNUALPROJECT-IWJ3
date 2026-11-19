<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Serves the HLS playlists of a video after checking the user may watch its lesson (ADR-0007, B2).
 * The playlists go through PHP (they are small), the segments do not: their URIs are replaced
 * with signed URLs of the S3 storage, which the player downloads directly.
 */
class VideoPlaylistController extends Controller
{
    public function master(Video $video): Response
    {
        $this->authorizeWatching($video);

        // Its rendition URIs are relative (360p/index.m3u8), so they resolve to rendition() below.
        return $this->playlist($this->read($video->hls_path));
    }

    public function rendition(Video $video, string $rendition): Response
    {
        $this->authorizeWatching($video);
        abort_unless(array_key_exists($rendition, config('videos.renditions')), 404);

        $directory = dirname($video->hls_path)."/{$rendition}";
        $expiresAt = now()->addMinutes(config('videos.playback_url_ttl_minutes'));
        $signing = Storage::disk('s3_signing');

        $lines = array_map(function (string $line) use ($directory, $expiresAt, $signing) {
            // Segment lines are bare file names written by TranscodeVideo; tags and blank lines stay as they are.
            if (! preg_match('/^[\w-]+\.ts$/', $line)) {
                return $line;
            }

            return $signing->temporaryUrl("{$directory}/{$line}", $expiresAt);
        }, preg_split('/\R/', $this->read("{$directory}/index.m3u8")));

        return $this->playlist(implode("\n", $lines));
    }

    private function authorizeWatching(Video $video): void
    {
        Gate::authorize('view', $video->lesson);

        // A video is only visible once its transcoding is finished.
        abort_unless($video->status === VideoStatus::Ready, 404);
    }

    private function read(string $path): string
    {
        $content = Storage::disk('s3')->get($path);
        abort_if($content === null, 404);

        return $content;
    }

    private function playlist(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            // The segment URLs expire: the playlist must not be cached.
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
