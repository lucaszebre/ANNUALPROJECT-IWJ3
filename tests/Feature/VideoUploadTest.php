<?php

use App\Enums\VideoLanguage;
use App\Enums\VideoStatus;
use App\Jobs\TranscodeVideo;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('s3_signing')->buildTemporaryUploadUrlsUsing(
        fn (string $path) => ['url' => "https://s3.test/app/{$path}?X-Amz-Signature=fake", 'headers' => []],
    );

    $this->organization = Organization::factory()->create();
    $this->teacher = User::factory()->create();
    Membership::factory()->teacher()->for($this->organization)->for($this->teacher)->create();

    $course = Course::factory()->for($this->organization)->create(['owner_id' => $this->teacher->id]);
    $this->lesson = Lesson::factory()->for($course)->create();
});

function requestUploadUrl(Lesson $lesson, array $data = []): TestResponse
{
    return test()->postJson(route('lessons.video.upload-url', $lesson), $data + [
        'mime_type' => 'video/mp4',
        'size' => 500 * 1024 * 1024,
    ]);
}

function pendingUploadPath(Lesson $lesson): ?string
{
    return session("video_uploads.{$lesson->id}");
}

it('gives the course owner a signed URL to upload the video straight to the S3 storage', function () {
    $this->actingAs($this->teacher);
    $response = requestUploadUrl($this->lesson);

    $path = pendingUploadPath($this->lesson);

    $response->assertOk()->assertJson([
        'url' => "https://s3.test/app/{$path}?X-Amz-Signature=fake",
        'method' => 'PUT',
        'headers' => ['Content-Type' => 'video/mp4'],
    ]);
    expect($path)->toStartWith("organizations/{$this->organization->id}/videos/")
        ->toEndWith('/source.mp4');
});

it('lets an admin of the organisation upload to any course', function () {
    $admin = User::factory()->create();
    Membership::factory()->admin()->for($this->organization)->for($admin)->create();

    $this->actingAs($admin);
    requestUploadUrl($this->lesson)->assertOk();
});

it('refuses other teachers and other organisations', function () {
    $colleague = User::factory()->create();
    Membership::factory()->teacher()->for($this->organization)->for($colleague)->create();
    $outsider = User::factory()->create();
    Membership::factory()->admin()->for(Organization::factory())->for($outsider)->create();

    $this->actingAs($colleague);
    requestUploadUrl($this->lesson)->assertForbidden();
    $this->actingAs($outsider);
    requestUploadUrl($this->lesson)->assertForbidden();
});

it('refuses guests', function () {
    requestUploadUrl($this->lesson)->assertUnauthorized();
});

it('only accepts video formats up to the size limit', function () {
    $this->actingAs($this->teacher);

    requestUploadUrl($this->lesson, ['mime_type' => 'application/pdf'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mime_type' => 'Upload an MP4, WebM, MOV or MKV video.']);

    requestUploadUrl($this->lesson, ['size' => config('videos.max_upload_bytes') + 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('size');
});

it('refuses a second video on the same lesson', function () {
    Video::factory()->for($this->lesson)->create();

    $this->actingAs($this->teacher);
    requestUploadUrl($this->lesson)->assertConflict();
});

it('creates the video and queues its transcoding once the file is on the S3 storage', function () {
    Queue::fake();
    $this->actingAs($this->teacher);
    requestUploadUrl($this->lesson)->assertOk();
    $path = pendingUploadPath($this->lesson);
    Storage::disk('s3')->put($path, str_repeat('x', 2048));

    $response = $this->postJson(route('lessons.video.store', $this->lesson), ['language' => 'en']);

    $video = $this->lesson->video()->firstOrFail();
    $response->assertCreated()->assertJson(['id' => $video->id, 'status' => 'uploaded']);
    expect($video)
        ->status->toBe(VideoStatus::Uploaded)
        ->source_path->toBe($path)
        ->size_bytes->toBe(2048)
        ->language->toBe(VideoLanguage::English)
        ->organization_id->toBe($this->organization->id)
        ->and(pendingUploadPath($this->lesson))->toBeNull();
    Queue::assertPushedOn('transcoding', TranscodeVideo::class, fn (TranscodeVideo $job) => $job->video->is($video));
});

it('refuses to create the video before the file is uploaded', function () {
    $this->actingAs($this->teacher);

    $this->postJson(route('lessons.video.store', $this->lesson), ['language' => 'fr'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['video' => 'The video has not been uploaded.']);

    requestUploadUrl($this->lesson)->assertOk();

    $this->postJson(route('lessons.video.store', $this->lesson), ['language' => 'fr'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('video');
    expect(Video::count())->toBe(0);
});

it('deletes an uploaded file that is larger than announced', function () {
    $this->actingAs($this->teacher);
    requestUploadUrl($this->lesson, ['size' => 100])->assertOk();
    $path = pendingUploadPath($this->lesson);
    Storage::disk('s3')->put($path, str_repeat('x', 4096));
    config(['videos.max_upload_bytes' => 1024]);

    $this->postJson(route('lessons.video.store', $this->lesson), ['language' => 'fr'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('video');

    Storage::disk('s3')->assertMissing($path);
    expect(Video::count())->toBe(0);
});
