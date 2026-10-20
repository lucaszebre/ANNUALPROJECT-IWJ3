# Data model

The database of the code learning platform, drawn before implementation, with the reasons behind each abstraction. This is the document we defend at the final presentation (syllabus › Modélisation). The tables will be implemented later as Laravel migrations and Eloquent models.

Target: **Laravel 12+** with **PostgreSQL** (needed for full-text search with French stemming).

## 1. Conventions

- Laravel naming: plural `snake_case` tables, `id` primary key, foreign keys named `<model>_id` (so `foreignUuid('course_id')->constrained()` finds `courses` by itself).
- Every table has `created_at` / `updated_at` (`$table->timestamps()`); they are not repeated in the diagrams.
- Primary keys are **UUIDs** (`HasUuids` trait, which generates ordered UUID v7 since Laravel 12), except for the two high-volume internal tables `transcript_segments` and `playback_events`, which use a normal auto-increment `id()`.
- Status columns are strings backed by **PHP enums** (`VideoStatus::Ready`) and cast in the model.
- Files (videos, HLS playlists, thumbnails) live on the `s3` disk (MinIO). The database only stores their **paths**.

## 2. Overview (conceptual model)

What exists and how it is related, without technical columns.

```mermaid
erDiagram
    ORGANIZATION ||--o{ MEMBERSHIP : has
    USER ||--o{ MEMBERSHIP : "belongs through"
    ORGANIZATION ||--o{ CATEGORY : defines
    CATEGORY |o--o{ COURSE : classifies
    USER ||--o{ COURSE : "owns (teacher)"
    COURSE ||--|{ LESSON : contains
    LESSON |o--o| VIDEO : "may have"
    LESSON |o--o| EXERCISE : "may have"
    LESSON |o--o| QUIZ : "may have"
    LESSON }o--o{ LESSON : "requires (prerequisite)"
    VIDEO ||--o{ TRANSCRIPT_SEGMENT : "is transcribed into"
    LESSON ||--o{ COMMENT : "is discussed in"
    COMMENT |o--o{ COMMENT : "is answered by"
    EXERCISE ||--|{ EXERCISE_TEST : "is graded by"
    EXERCISE ||--o{ SUBMISSION : receives
    SUBMISSION ||--|{ SUBMISSION_RESULT : "one per test"
    QUIZ ||--|{ QUESTION : contains
    QUESTION ||--|{ CHOICE : offers
    QUIZ ||--o{ QUIZ_ATTEMPT : receives
    USER }o--o{ COURSE : "enrolls in"
    USER }o--o{ LESSON : completes
    VIDEO ||--o{ PLAYBACK_EVENT : "is watched"
```

A draft course can be empty, but a course needs at least one lesson to be **published**.

## 3. Detailed diagrams

Every tenant table has an `organization_id` column (see [decision 4.1](#41-organisations-one-database-shared-by-all-organisations)); it is shown only where it matters.

### 3.1 Organisations and users

```mermaid
erDiagram
    organizations ||--o{ memberships : ""
    users ||--o{ memberships : ""
    users ||--o{ sessions : ""
    organizations {
        uuid id PK
        string name
        string slug UK
        bigint video_storage_quota_bytes
        int code_runs_quota_monthly
    }
    users {
        uuid id PK
        string name
        string email UK
        string password "hashed, nullable for OAuth-only accounts"
        timestamp email_verified_at
        string remember_token
    }
    memberships {
        uuid id PK
        uuid organization_id FK
        uuid user_id FK
        string role "MemberRole enum: student | teacher | admin"
    }
    sessions {
        string id PK "Laravel database sessions"
        uuid user_id FK
        text payload "includes the current organisation id"
        int last_activity
    }
```

`memberships` has a unique index on `(organization_id, user_id)`.

### 3.2 Courses, lessons, videos and comments

```mermaid
erDiagram
    organizations ||--o{ categories : ""
    categories |o--o{ courses : ""
    users ||--o{ courses : "owner_id"
    courses ||--o{ lessons : ""
    lessons |o--o| videos : ""
    videos ||--o{ transcript_segments : ""
    lessons ||--o{ comments : ""
    comments |o--o{ comments : "parent_id"
    comments ||--o{ comment_reports : ""
    categories {
        uuid id PK
        uuid organization_id FK
        string name
        string slug "unique per organisation"
    }
    courses {
        uuid id PK
        uuid organization_id FK
        uuid owner_id FK "users"
        uuid category_id FK "nullable"
        string title
        string slug "unique per organisation"
        text description
        string status "CourseStatus: draft | published | archived"
        timestamp published_at
        timestamp deleted_at "soft delete"
    }
    lessons {
        uuid id PK
        uuid organization_id FK
        uuid course_id FK
        int position "unique per course"
        string title
        text content "Markdown text, nullable"
    }
    videos {
        uuid id PK
        uuid organization_id FK
        uuid lesson_id FK "unique: 0 or 1 video per lesson"
        string status "VideoStatus: uploaded | processing | ready | failed"
        string source_path "original upload on MinIO"
        string hls_path "master.m3u8, set when ready"
        string thumbnail_path
        int duration_seconds
        bigint size_bytes "source + HLS, for the quota"
        string language "fr | en, for Whisper"
        string transcript_status "pending | processing | ready | failed"
        timestamp ready_at
    }
    transcript_segments {
        bigint id PK
        uuid organization_id FK
        uuid video_id FK
        int start_ms
        int end_ms
        text text "fullText index (french)"
    }
    comments {
        uuid id PK
        uuid organization_id FK
        uuid lesson_id FK
        uuid user_id FK
        uuid parent_id FK "nullable: replies, one level"
        int timecode_seconds "nullable: moment in the video"
        text body
        string status "CommentStatus: visible | hidden"
        timestamp deleted_at "soft delete"
    }
    comment_reports {
        uuid id PK
        uuid comment_id FK
        uuid user_id FK "unique with comment_id"
        string reason
        timestamp resolved_at
    }
```

### 3.3 Exercises and quizzes

```mermaid
erDiagram
    lessons |o--o| exercises : ""
    exercises ||--o{ exercise_tests : ""
    exercises ||--o{ submissions : ""
    users ||--o{ submissions : ""
    submissions ||--o{ submission_results : ""
    exercise_tests ||--o{ submission_results : ""
    lessons |o--o| quizzes : ""
    quizzes ||--o{ quiz_questions : ""
    quiz_questions ||--o{ quiz_choices : ""
    quizzes ||--o{ quiz_attempts : ""
    users ||--o{ quiz_attempts : ""
    quiz_attempts }o--o{ quiz_choices : "quiz_attempt_choice"
    exercises {
        uuid id PK
        uuid lesson_id FK "unique"
        string language "CodeLanguage: python | javascript"
        text statement "Markdown"
        text starter_code
        text solution_code "hidden from students"
        int time_limit_ms
        int memory_limit_mb
    }
    exercise_tests {
        uuid id PK
        uuid exercise_id FK
        int position
        string name
        text stdin
        text expected_stdout
        bool is_hidden
    }
    submissions {
        uuid id PK
        uuid exercise_id FK
        uuid user_id FK
        text code
        string status "SubmissionStatus: queued | running | passed | failed | error"
        int tests_passed
        int tests_total
        timestamp finished_at
    }
    submission_results {
        uuid id PK
        uuid submission_id FK
        uuid exercise_test_id FK
        bool passed
        text actual_stdout "truncated"
        text stderr "truncated"
        int duration_ms
        bool timed_out
    }
    quizzes {
        uuid id PK
        uuid lesson_id FK "unique"
        int pass_threshold_percent
    }
    quiz_questions {
        uuid id PK
        uuid quiz_id FK
        int position
        text prompt
        bool multiple "several correct choices"
    }
    quiz_choices {
        uuid id PK
        uuid quiz_question_id FK
        string label
        bool is_correct "hidden from students"
    }
    quiz_attempts {
        uuid id PK
        uuid quiz_id FK
        uuid user_id FK
        int score_percent
        bool passed
    }
```

### 3.4 Learning path and progress

```mermaid
erDiagram
    lessons ||--o{ lesson_prerequisites : "lesson_id"
    lessons ||--o{ lesson_prerequisites : "prerequisite_id"
    users ||--o{ enrollments : ""
    courses ||--o{ enrollments : ""
    users ||--o{ lesson_completions : ""
    lessons ||--o{ lesson_completions : ""
    lesson_prerequisites {
        uuid lesson_id PK, FK "this lesson..."
        uuid prerequisite_id PK, FK "...requires this one"
    }
    enrollments {
        uuid user_id PK, FK
        uuid course_id PK, FK
        timestamp created_at "enrolled at"
    }
    lesson_completions {
        uuid user_id PK, FK
        uuid lesson_id PK, FK
        string source "manual | exercise | quiz"
        timestamp created_at "completed at"
    }
```

### 3.5 Analytics and background jobs

```mermaid
erDiagram
    videos ||--o{ playback_events : ""
    users ||--o{ playback_events : ""
    videos ||--o{ video_retention_stats : ""
    playback_events {
        bigint id PK
        uuid organization_id FK
        uuid video_id FK
        uuid user_id FK
        uuid playback_session "one per player load"
        string event "play | pause | seek | heartbeat | ended"
        int position_seconds
        timestamp occurred_at
    }
    video_retention_stats {
        uuid video_id PK, FK
        int bucket_start_seconds PK "10 s buckets"
        int viewers
    }
    jobs {
        bigint id PK "Laravel database queue"
        string queue "transcode | transcribe | sandbox | default"
        text payload "serialized Job class"
        int attempts
        int reserved_at
        int available_at
    }
    failed_jobs {
        bigint id PK
        string uuid UK
        text payload
        text exception
        timestamp failed_at
    }
```

`jobs` and `failed_jobs` are Laravel's own tables (`php artisan make:queue-table`); they have no relation to the domain tables because a job only carries the ids it needs in its payload.

## 4. Abstraction decisions

Each choice below lists what we considered and why we picked one.

### 4.1 Organisations: one database shared by all organisations

Business rule: *the data of an organisation is never accessible from another organisation.*

| Option | Verdict |
|---|---|
| One database per organisation (e.g. `stancl/tenancy`) | Strongest isolation, but N databases to migrate and back up on a small VPS. Too heavy. |
| One schema per organisation | Same problem, and harder to explain. |
| **One shared database, `organization_id` on every tenant table** | **Chosen.** One migration, one backup, and Laravel makes the filtering automatic. |

How it works in Laravel:

- A `BelongsToOrganization` trait, used by every tenant model, adds a **global scope** `where organization_id = <current organisation>` and fills `organization_id` automatically on `creating`. Developers cannot forget the filter: `Course::find($id)` on a course of another organisation returns `null`, so route model binding gives a 404.
- A middleware resolves the current organisation from the session and refuses the request if the user is not a member of it.
- Validation uses `Rule::exists('courses', 'id')->where('organization_id', ...)`, so an id from another organisation cannot be submitted in a form.
- Feature tests check, for every resource, that a user of organisation A gets a 404 on organisation B's data.

Why `organization_id` is repeated on child tables (`lessons`, `videos`, `comments`...) when it could be reached through `course_id`: the global scope works on one table without joins. It is a small, deliberate redundancy that makes every query safe by default.

`users` is global (not scoped) because login happens before we know the organisation.

### 4.2 Roles: a `memberships` table, not a column on `users`

The same person can be a teacher in one organisation and a student in another, so the role belongs to the link between a user and an organisation:

```php
// User.php
public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class, 'memberships')
        ->using(Membership::class)
        ->withPivot('role');
}
```

We considered `spatie/laravel-permission`. It is well suited to many fine-grained permissions, but we only have three fixed roles, and its tables would add complexity for no gain. A `MemberRole` PHP enum on the pivot plus **Policies** (`CoursePolicy::update`: owner or admin) covers the rules and is easy to explain.

### 4.3 Lessons: one container with optional typed content

Rule: *a lesson can contain a video, text, an exercise, or a combination of the three.*

| Option | Verdict |
|---|---|
| A JSON `content` column | No foreign keys, no constraints, hard to query. |
| Polymorphic content blocks (`lesson_items` with `morphTo`) | Very flexible (any number of blocks, any order), but Eloquent polymorphic relations cannot have real foreign keys, and every screen must branch on the block type. Flexibility we do not need. |
| **A `lessons` table with `content` (text) + optional `videos`, `exercises`, `quizzes` linked 1-to-1** | **Chosen.** Each content type has its own columns (`duration_seconds`, `time_limit_ms`...), real foreign keys, and simple relations: `$lesson->video`, `$lesson->exercise`. |

The 1-to-1 is enforced with a unique index on `lesson_id`. If we later want several exercises per lesson, we only remove that unique index and change `hasOne` to `hasMany`.

### 4.4 Videos: a status, and paths instead of files

Rule: *a video is only visible once its transcoding is finished.*

- `status` follows `uploaded → processing → ready | failed`. A local scope `Video::ready()` is used for everything students see, and `hls_path` is only set when the transcode job succeeds.
- The database stores **paths** on the MinIO disk, never URLs or files. The application generates temporary signed URLs (`Storage::temporaryUrl()`), so videos are never public and the storage can move without a data migration.
- `size_bytes` feeds the video storage quota: `Video::sum('size_bytes')` for the organisation.

### 4.5 Transcripts: segments, not one big text

Whisper returns timed segments. We keep them as rows (`start_ms`, `end_ms`, `text`) instead of one text per video because that is what makes search "precise to the second": each search hit is a segment, so we can open the video at `start_ms`. The same rows produce the subtitle file (WebVTT).

Search uses PostgreSQL full text, natively supported by Laravel, instead of a search engine like Meilisearch or Elasticsearch:

```php
$table->fullText('text')->language('french');                    // migration: GIN index
TranscriptSegment::whereFullText('text', $q, ['language' => 'french'])->get(); // query
```

French stemming means "fonctions récursives" also finds "une fonction récursive". One less service to run on the VPS.

### 4.6 Comments: one table for questions, answers and timecoded comments

- Replies use `parent_id` (self-relation) with **one level** only: a student asks, the teacher answers. Deeper threads would complicate the UI for no real need. The rule is checked in the Form Request.
- `timecode_seconds` is nullable: a comment is either about the whole lesson or about a moment of the video. No separate table for "timecoded comments": same data, same moderation.
- We did not make comments polymorphic (`commentable`): they only ever target lessons, and a normal foreign key keeps integrity.
- Moderation: `status` (`visible` / `hidden`) for moderators, `SoftDeletes` for deletion (so a deleted question does not break the thread of answers), and `comment_reports` (unique per user and comment) for reports.
- Comments are stored as typed and escaped on output by Blade (`{{ }}`), which protects against XSS.

### 4.7 Code exercises: input/output tests

- A test is a row with `stdin` and `expected_stdout`: the sandbox runs the student's program, sends `stdin`, compares the output. It works the same for every language, and the sandbox image does not need a test framework.
- `is_hidden` tests and `solution_code` never leave the server (excluded with `$hidden` on the model and in API resources). Students see whether a hidden test passed, not its content.
- One `submission_results` row per test gives the teacher the failure rate **per test**, not only per exercise.

### 4.8 Learning path: prerequisites as a graph

Rule: *a lesson stays locked until its prerequisites are validated; prerequisites cannot form a cycle.*

`lesson_prerequisites` is a self-referencing many-to-many on `lessons`:

```php
public function prerequisites(): BelongsToMany
{
    return $this->belongsToMany(Lesson::class, 'lesson_prerequisites', 'lesson_id', 'prerequisite_id');
}
```

This allows a real graph (a lesson can require two others), not only a linear order. Before saving an edge, a `PrerequisiteGraph` service walks the existing prerequisites and refuses the edge if it would close a loop. It runs in a transaction that locks the course, so two teachers cannot each create half of a cycle at the same time.

A lesson is **unlocked** when all of its prerequisites appear in the student's `lesson_completions`. This is computed, not stored.

### 4.9 Progress: store completions, compute percentages

A row in `lesson_completions` means "this user completed this lesson", with its `source` (`manual` for text/video, `exercise` or `quiz` when validated automatically). Course progress (completed lessons / lessons) is **computed** on the fly. Storing a percentage would go wrong as soon as a teacher adds a lesson.

`enrollments` records which courses a student follows: it drives the "my learning" page and is the denominator for analytics.

### 4.10 Analytics: raw events + pre-computed results

- The player sends `playback_events` (play, pause, seek, a heartbeat every ~10 s). This is by far the largest table, so it has a plain `bigint` id, an index on `(video_id, occurred_at)`, and uses Laravel's **`MassPrunable`** to delete events older than a few months with a scheduled `model:prune`.
- Computing the drop-off curve from raw events on every dashboard load would be slow, so a scheduled job aggregates it into `video_retention_stats` (viewers still watching per 10-second bucket).
- The failure rate per exercise is computed directly from `submissions` and `submission_results`, which are small enough.

### 4.11 Background jobs: one queue, three kinds of work

The central argument of the project: transcoding, transcription and code execution are three kinds of jobs on **one** queue.

We use Laravel's **database queue driver**: one `jobs` table in PostgreSQL, no Redis or RabbitMQ. Workers take jobs with `SELECT ... FOR UPDATE SKIP LOCKED`, so several workers never take the same job.

| Job class | Queue | Updates |
|---|---|---|
| `TranscodeVideo` (ffmpeg → HLS) | `transcode` | `videos.status`, then dispatches `TranscribeVideo` |
| `TranscribeVideo` (Whisper) | `transcribe` | `videos.transcript_status`, `transcript_segments` |
| `RunSubmission` (Docker sandbox) | `sandbox` | `submissions.status`, `submission_results` |
| `AggregateVideoRetention` (scheduled) | `default` | `video_retention_stats` |

Separate queue names let us run dedicated workers (`queue:work --queue=sandbox`): a long Whisper transcription never delays a student waiting for their code result. The UI never reads the `jobs` table. It shows the status of the domain row (`videos.status`, `submissions.status`), which the job updates.

### 4.12 Quotas: two columns on `organizations`

Rule: *each organisation has quotas (video storage, code executions).*

Quotas are two columns on `organizations` rather than a separate 1-to-1 table: there are only two numbers and they are always read with the organisation. Usage is computed from existing data (sum of `videos.size_bytes`, count of this month's `submissions`), so there is no counter to keep in sync.

### 4.13 UUIDs instead of auto-increment ids

- `/courses/42` invites trying `/courses/43`. A UUID cannot be guessed. Authorisation is still checked by Policies; this is an extra layer.
- Laravel's `HasUuids` generates **ordered** UUIDs (v7), which start with a timestamp, so new rows are inserted at the end of the index like an auto-increment.
- Internal high-volume tables (`transcript_segments`, `playback_events`) keep `bigint` ids: they never appear in URLs and are smaller to index.

## 5. Business rules → where they are enforced

| Business rule (project brief) | How, in Laravel |
|---|---|
| Must be logged in to comment, submit code or access private content | `auth` middleware on the routes |
| A course needs ≥ 1 lesson before publication | `PublishCourse` action checks `$course->lessons()->exists()` |
| A lesson can contain a video, text, an exercise, or a combination | Nullable `lessons.content` + optional `hasOne` video / exercise / quiz (unique `lesson_id`) |
| A video is only visible once transcoding is finished | `VideoStatus` enum, `Video::ready()` scope, `hls_path` set only by `TranscodeVideo` |
| A lesson is locked until its prerequisites are validated | `LessonPolicy::view` checks prerequisites against `lesson_completions` |
| Prerequisites cannot form a cycle | `PrerequisiteGraph` service, in a locked transaction, plus a unique `(lesson_id, prerequisite_id)` |
| Submitted code only runs in an isolated sandbox | Only the `RunSubmission` job executes code, in an ephemeral Docker container (#28) |
| Comments follow moderation rules | `StoreCommentRequest` validation + content filter, `status`, `comment_reports`, `SoftDeletes` |
| Teachers only edit their own courses, unless admin | `CoursePolicy::update`: `owner_id` or admin role in `memberships` |
| An organisation's data is never reachable from another one | `BelongsToOrganization` global scope, organisation middleware, scoped `exists` validation, feature tests |
| Each organisation has quotas | Quota columns on `organizations`, checked before upload and before dispatching `RunSubmission` |

## 6. Open points for the team

- **PostgreSQL** rather than MySQL: needed for `fullText` with French stemming. OK for everyone?
- **Exercise languages:** Python and JavaScript to start?
- **One exercise per lesson** for V1: OK, or do we want several?
- **OAuth (bonus #47):** would add an `oauth_accounts` table, or use Laravel Socialite with `provider` / `provider_id` columns on `users`.
