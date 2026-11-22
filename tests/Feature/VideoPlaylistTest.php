<?php

use App\Enums\VideoStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('s3_signing')->buildTemporaryUrlsUsing(
        fn (string $path) => "https://s3.test/app/{$path}?X-Amz-Signature=fake",
    );

    $this->organization = Organization::factory()->create();
    $this->withSession(['current_organization_id' => $this->organization->id]);
    $this->teacher = User::factory()->create();
    Membership::factory()->teacher()->for($this->organization)->for($this->teacher)->create();

    $this->course = Course::factory()->published()->for($this->organization)->create(['owner_id' => $this->teacher->id]);
    $lesson = Lesson::factory()->for($this->course)->create();

    $this->student = User::factory()->create();
    Membership::factory()->for($this->organization)->for($this->student)->create();
    $this->course->students()->attach($this->student);

    $this->video = Video::factory()->ready()->for($lesson)->create([
        'organization_id' => $this->organization->id,
        'source_path' => 'organizations/org/videos/abc/source.mp4',
        'hls_path' => 'organizations/org/videos/abc/hls/master.m3u8',
        'thumbnail_path' => 'organizations/org/videos/abc/thumbnail.jpg',
    ]);

    Storage::disk('s3')->put('organizations/org/videos/abc/hls/master.m3u8', implode("\n", [
        '#EXTM3U',
        '#EXT-X-STREAM-INF:BANDWIDTH=928000,RESOLUTION=640x360',
        '360p/index.m3u8',
    ]));
    Storage::disk('s3')->put('organizations/org/videos/abc/hls/360p/index.m3u8', implode("\n", [
        '#EXTM3U',
        '#EXT-X-PLAYLIST-TYPE:VOD',
        '#EXTINF:6.000000,',
        'segment_000.ts',
        '#EXTINF:2.500000,',
        'segment_001.ts',
        '#EXT-X-ENDLIST',
    ]));
});

it('serves the master playlist to an enrolled student', function () {
    $this->actingAs($this->student)
        ->get(route('videos.hls.master', $this->video))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('360p/index.m3u8');
});

it('resolves the rendition URIs of the master playlist to the rendition route', function () {
    $master = route('videos.hls.master', $this->video);

    // How the player resolves a relative URI.
    $resolved = preg_replace('#[^/]+$#', '360p/index.m3u8', $master);

    expect($resolved)->toBe(route('videos.hls.rendition', [$this->video, '360p']));
});

it('replaces the segments of a rendition with signed URLs of the S3 storage', function () {
    $this->actingAs($this->student)
        ->get(route('videos.hls.rendition', [$this->video, '360p']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
        ->assertContent(implode("\n", [
            '#EXTM3U',
            '#EXT-X-PLAYLIST-TYPE:VOD',
            '#EXTINF:6.000000,',
            'https://s3.test/app/organizations/org/videos/abc/hls/360p/segment_000.ts?X-Amz-Signature=fake',
            '#EXTINF:2.500000,',
            'https://s3.test/app/organizations/org/videos/abc/hls/360p/segment_001.ts?X-Amz-Signature=fake',
            '#EXT-X-ENDLIST',
        ]));
});

it('lets the course owner watch the video', function () {
    $this->actingAs($this->teacher)
        ->get(route('videos.hls.master', $this->video))
        ->assertOk();
});

it('lets an admin of the organisation watch the video', function () {
    $admin = User::factory()->create();
    Membership::factory()->admin()->for($this->organization)->for($admin)->create();

    $this->actingAs($admin)
        ->get(route('videos.hls.master', $this->video))
        ->assertOk();
});

it('refuses a student who is not enrolled in the course', function () {
    $other = User::factory()->create();
    Membership::factory()->for($this->organization)->for($other)->create();

    $this->actingAs($other)->get(route('videos.hls.master', $this->video))->assertForbidden();
    $this->actingAs($other)->get(route('videos.hls.rendition', [$this->video, '360p']))->assertForbidden();
});

it('refuses an enrolled student while the course is not published', function () {
    $this->course->update(['status' => 'draft']);

    $this->actingAs($this->student)
        ->get(route('videos.hls.master', $this->video))
        ->assertForbidden();
});

it('refuses a student enrolled in the same course from another organization', function () {
    $foreignOrganization = Organization::factory()->create();
    Membership::factory()->for($foreignOrganization)->for($this->student)->create();

    $foreignCourse = Course::factory()->published()->for($foreignOrganization)->create();
    $foreignLesson = Lesson::factory()->for($foreignCourse)->create();
    $foreignCourse->students()->attach($this->student);
    $foreignVideo = Video::factory()->ready()->for($foreignLesson)->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    $this->actingAs($this->student)
        ->withSession(['current_organization_id' => $this->organization->id])
        ->get(route('videos.hls.master', $foreignVideo))
        ->assertForbidden();
});

it('refuses guests', function () {
    $this->getJson(route('videos.hls.master', $this->video))->assertUnauthorized();
});

it('never serves a video whose transcoding is not finished', function (VideoStatus $status) {
    $this->video->update(['status' => $status]);

    $this->actingAs($this->student)->get(route('videos.hls.master', $this->video))->assertNotFound();
    $this->actingAs($this->student)->get(route('videos.hls.rendition', [$this->video, '360p']))->assertNotFound();
})->with([VideoStatus::Uploaded, VideoStatus::Processing, VideoStatus::Failed]);

it('only serves the configured renditions', function (string $rendition) {
    $this->actingAs($this->student)
        ->get("/videos/{$this->video->id}/hls/{$rendition}/index.m3u8")
        ->assertNotFound();
})->with(['1080p', '..', 'source.mp4']);

it('gives a signed URL for the thumbnail', function () {
    expect($this->video->thumbnailUrl())
        ->toBe('https://s3.test/app/organizations/org/videos/abc/thumbnail.jpg?X-Amz-Signature=fake');
});
