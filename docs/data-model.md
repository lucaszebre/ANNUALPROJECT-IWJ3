# Data model

The database of the code learning platform, drawn before implementation, with the reasons behind each abstraction. This is the document we defend at the final presentation (syllabus › Modélisation). The tables will be implemented later as Laravel migrations and Eloquent models.

Target: **Laravel 13** (ADR-0001) with **PostgreSQL** (ADR-0005, needed for full-text search with French stemming).

## 1. Conventions

- Laravel naming: plural `snake_case` tables, `id` primary key, foreign keys named `<model>_id` (so `foreignUuid('course_id')->constrained()` finds `courses` by itself).
- Every table has `created_at` / `updated_at` (`$table->timestamps()`); they are not repeated in the diagrams.
- Primary keys are **UUIDs** (`HasUuids` trait, which generates ordered UUID v7 since Laravel 12), except for the two high-volume internal tables `transcript_segments` and `playback_events`, which use a normal auto-increment `id()`.
- Status columns are strings backed by **PHP enums** (`VideoStatus::Ready`) and cast in the model.
- Files (videos, HLS playlists, thumbnails) live on the `s3` disk (Garage). The database only stores their **paths**.

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

Every table with a model we query directly has an `organization_id` column, so the global scope of [decision 4.1](#41-organisations-one-database-shared-by-all-organisations) can filter it:

- **With `organization_id`:** `categories`, `courses`, `lessons`, `videos`, `transcript_segments`, `comments`, `comment_reports`, `exercises`, `submissions`, `quizzes`, `quiz_attempts`, `playback_events`.
- **Without it:** child rows only read through their parent (`exercise_tests`, `submission_results`, `quiz_questions`, `quiz_choices`, `quiz_attempt_choice`), and tables only reached from a scoped model (`lesson_prerequisites`, `enrollments`, `lesson_completions`, `video_retention_stats`).

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
        string source_path "original upload on the s3 disk"
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
        text text
        regconfig search_config "fr_unaccent | en_unaccent, from the video language"
        tsvector search_vector "generated from text, GIN index"
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
        uuid organization_id FK "admins list the reports of their organisation"
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
        uuid organization_id FK
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
        uuid organization_id FK "index with created_at, for the quota"
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
        uuid organization_id FK
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
        uuid organization_id FK
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

- The **current organisation** (`App\Support\CurrentOrganization`, one per request or queued job) is set by the `ResolveCurrentOrganization` middleware on every web request, from the session, and only if the user is still a member of it. It runs before route model binding.
- A `BelongsToOrganization` trait, used by every tenant model, adds a **global scope** `where organization_id = <current organisation>` and fills `organization_id` automatically on `creating`. Creating a row for another organisation throws, since `organization_id` is fillable. Developers cannot forget the filter: `Course::find($id)` on a course of another organisation returns `null`, so route model binding gives a 404.
- Routes that read an organisation's data use the `organization.selected` middleware: without a current organisation the user is sent to the organisation picker.
- A signed-in user who has not picked an organisation sees **no** organisation's data, and cannot create any: a new account cannot read other organisations through a route that forgot `organization.selected`.
- Visitors, console commands, queued jobs and seeders have no current organisation and are not filtered. Visitors browse the **public catalogue**: the published courses of every organisation, as a showcase. Lessons, videos and comments need a login and a membership. The catalogue must read courses with `withoutGlobalScope(OrganizationScope::class)`, so this one cross-organisation read is explicit.
- `memberships` is not scoped: the profile and the role checks read all of a user's organisations.
- Validation uses `app(CurrentOrganization::class)->exists('courses')`, a `Rule::exists` limited to the current organisation, so an id from another organisation cannot be submitted in a form.
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

Lessons are ordered by `position`, unique per course. PostgreSQL checks a normal unique constraint row by row, so moving a lesson with `UPDATE lessons SET position = position + 1 ...` can fail halfway on a duplicate. The constraint is therefore created as `DEFERRABLE INITIALLY DEFERRED` (raw SQL in the migration): it is checked once, at the end of the reordering transaction. `exercise_tests` and `quiz_questions` use the same constraint on their `position`.

### 4.4 Videos: a status, and paths instead of files

Rule: *a video is only visible once its transcoding is finished.*

- `status` follows `uploaded → processing → ready | failed`. A local scope `Video::ready()` is used for everything students see, and `hls_path` is only set when the transcode job succeeds.
- The database stores **paths** on the `s3` disk, never URLs or files, so the storage can move without a data migration. Videos are never public. `Storage::temporaryUrl()` signs one file, but an HLS playlist points to its segments by relative path, and each segment needs its own signature. How segments are served (through the app, or by rewriting the playlist with signed URLs) is decided in ADR-0007.
- `size_bytes` feeds the video storage quota: `Video::sum('size_bytes')` for the organisation.

### 4.5 Transcripts: segments, not one big text

Whisper returns timed segments. We keep them as rows (`start_ms`, `end_ms`, `text`) instead of one text per video because that is what makes search "precise to the second": each search hit is a segment, so we can open the video at `start_ms`. The same rows produce the subtitle file (WebVTT).

Search uses PostgreSQL full text instead of a search engine like Meilisearch or Elasticsearch. One less service to run on the VPS. Two details need care:

- **Language.** A video is in French or English (`videos.language`), and stemming depends on the language. Each segment stores the text search configuration of its video (`search_config`), and a generated column computes `to_tsvector(search_config, text)`.
- **Accents.** Stemming alone does not remove accents, so "recursive" would not find "récursive". We create two configurations, `fr_unaccent` and `en_unaccent`, that run `unaccent` before the stemmer. `unaccent` cannot be used directly in a generated column or an index because PostgreSQL does not consider it immutable, but a text search configuration that uses it can.

The schema builder cannot express this, so the migration uses raw SQL:

```sql
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE TEXT SEARCH CONFIGURATION fr_unaccent (COPY = french);
ALTER TEXT SEARCH CONFIGURATION fr_unaccent
    ALTER MAPPING FOR hword, hword_part, word WITH unaccent, french_stem;
-- same for en_unaccent, with english_stem

ALTER TABLE transcript_segments ADD COLUMN search_vector tsvector
    GENERATED ALWAYS AS (to_tsvector(search_config, text)) STORED;
CREATE INDEX transcript_segments_search_vector ON transcript_segments USING gin (search_vector);
```

The query matches the search in both languages, with constant configurations so that PostgreSQL can use the index:

```php
TranscriptSegment::whereRaw(
    "search_vector @@ (websearch_to_tsquery('fr_unaccent', ?) || websearch_to_tsquery('en_unaccent', ?))",
    [$q, $q],
)->get();
```

"fonctions récursives" then finds "une fonction récursive", and also "fonction recursive" typed without accents.

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

This allows a real graph (a lesson can require two others), not only a linear order. Both lessons belong to the same course: the service refuses a prerequisite from another course, and a `CHECK (lesson_id <> prerequisite_id)` constraint refuses a lesson that requires itself. Before saving an edge, a `PrerequisiteGraph` service walks the existing prerequisites and refuses the edge if it would close a loop. It runs in a transaction that locks the course, so two teachers cannot each create half of a cycle at the same time.

A lesson is **unlocked** when all of its prerequisites appear in the student's `lesson_completions`. This is computed, not stored.

### 4.9 Progress: store completions, compute percentages

A row in `lesson_completions` means "this user completed this lesson", with its `source` (`manual` for text/video, `exercise` or `quiz` when validated automatically). Course progress (completed lessons / lessons) is **computed** on the fly. Storing a percentage would go wrong as soon as a teacher adds a lesson.

`enrollments` records which courses a student follows: it drives the "my learning" page and is the denominator for analytics.

### 4.10 Analytics: raw events + pre-computed results

- The player sends `playback_events` (play, pause, seek, a heartbeat every ~10 s). This is by far the largest table, so it has a plain `bigint` id, an index on `(video_id, occurred_at)`, and uses Laravel's **`MassPrunable`** to delete events older than a few months with a scheduled `model:prune`.
- Computing the drop-off curve from raw events on every dashboard load would be slow, so a scheduled job aggregates it into `video_retention_stats` (viewers still watching per 10-second bucket).
- The failure rate per exercise is computed directly from `submissions` and `submission_results`, which are small enough.

### 4.11 Background jobs: one queue table, three kinds of work

The central argument of the project: transcoding, transcription and code execution are three kinds of jobs in **one** queue system, the `jobs` table, each with its own named queue.

We use Laravel's **database queue driver**: one `jobs` table in PostgreSQL, no Redis or RabbitMQ. Workers take jobs with `SELECT ... FOR UPDATE SKIP LOCKED`, so several workers never take the same job.

| Job class | Queue | Updates |
|---|---|---|
| `TranscodeVideo` (ffmpeg → HLS) | `transcode` | `videos.status`, then dispatches `TranscribeVideo` |
| `TranscribeVideo` (Whisper) | `transcribe` | `videos.transcript_status`, `transcript_segments` |
| `RunSubmission` (Docker sandbox) | `sandbox` | `submissions.status`, `submission_results` |
| `AggregateVideoRetention` (scheduled) | `default` | `video_retention_stats` |

Separate queue names let us run dedicated workers (`queue:work --queue=sandbox`): a long Whisper transcription never delays a student waiting for their code result. The queue's `retry_after` is set longer than the slowest job (a transcription can take tens of minutes); otherwise the queue would think the job had died and start it a second time while the first run is still going. The UI never reads the `jobs` table. It shows the status of the domain row (`videos.status`, `submissions.status`), which the job updates.

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
| Prerequisites cannot form a cycle | `PrerequisiteGraph` service, in a locked transaction, plus a unique `(lesson_id, prerequisite_id)` and a `CHECK (lesson_id <> prerequisite_id)` |
| Submitted code only runs in an isolated sandbox | Only the `RunSubmission` job executes code, in an ephemeral Docker container (#28) |
| Comments follow moderation rules | `StoreCommentRequest` validation + content filter, `status`, `comment_reports`, `SoftDeletes` |
| Teachers only edit their own courses, unless admin | `CoursePolicy::update`: `owner_id` or admin role in `memberships` |
| An organisation's data is never reachable from another one | `BelongsToOrganization` global scope, organisation middleware, scoped `exists` validation, feature tests |
| Each organisation has quotas | Quota columns on `organizations`, checked before upload and before dispatching `RunSubmission` |

## 6. Open points for the team

- **Exercise languages:** Python and JavaScript to start?
- **One exercise per lesson** for V1: OK, or do we want several?
- **OAuth (bonus #47):** would add an `oauth_accounts` table, or use Laravel Socialite with `provider` / `provider_id` columns on `users`.
