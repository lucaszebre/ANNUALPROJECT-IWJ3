<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Http\Requests\CreateVideoUploadRequest;
use App\Http\Requests\StoreVideoRequest;
use App\Jobs\TranscodeVideo;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Video upload in two steps, so the file goes straight from the browser to MinIO:
 * 1. create() returns a signed URL the browser sends the file to with a PUT;
 * 2. store() checks the uploaded file, creates the video and queues the transcoding.
 */
class LessonVideoUploadController extends Controller
{
    public function create(CreateVideoUploadRequest $request, Lesson $lesson): JsonResponse
    {
        abort_if($lesson->video()->exists(), 409, 'This lesson already has a video.');

        $mimeType = $request->validated('mime_type');
        $path = sprintf(
            'organizations/%s/videos/%s/source.%s',
            $lesson->organization_id,
            Str::uuid7(),
            config("videos.mime_types.{$mimeType}"),
        );

        // The path stays on the server: store() only accepts the file this user was given.
        $request->session()->put($this->sessionKey($lesson), $path);

        $upload = Storage::disk('s3_signing')->temporaryUploadUrl(
            $path,
            now()->addMinutes(config('videos.upload_url_ttl_minutes')),
            ['ContentType' => $mimeType],
        );

        return response()->json([
            'url' => $upload['url'],
            'method' => 'PUT',
            'headers' => ['Content-Type' => $mimeType],
        ]);
    }

    public function store(StoreVideoRequest $request, Lesson $lesson): JsonResponse
    {
        abort_if($lesson->video()->exists(), 409, 'This lesson already has a video.');

        $path = $request->session()->get($this->sessionKey($lesson));
        $disk = Storage::disk('s3');

        if (! is_string($path) || ! $disk->exists($path)) {
            throw ValidationException::withMessages(['video' => 'The video has not been uploaded.']);
        }

        $size = $disk->size($path);

        // The size given in step 1 is the browser's word: check the real file.
        if ($size > config('videos.max_upload_bytes')) {
            $disk->delete($path);
            $request->session()->forget($this->sessionKey($lesson));

            throw ValidationException::withMessages([
                'video' => 'The video is larger than '.(int) (config('videos.max_upload_bytes') / 1024 / 1024).' MB.',
            ]);
        }

        $video = DB::transaction(function () use ($lesson, $path, $size, $request) {
            $video = Video::create([
                'organization_id' => $lesson->organization_id,
                'lesson_id' => $lesson->id,
                'status' => VideoStatus::Uploaded,
                'source_path' => $path,
                'size_bytes' => $size,
                'language' => $request->validated('language'),
            ]);

            // Queued once the transaction commits (after_commit), so the row exists.
            TranscodeVideo::dispatch($video);

            return $video;
        });

        $request->session()->forget($this->sessionKey($lesson));

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
        ], 201);
    }

    private function sessionKey(Lesson $lesson): string
    {
        return "video_uploads.{$lesson->id}";
    }
}
