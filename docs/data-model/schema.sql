-- Reference schema for the code learning platform (PostgreSQL 18).
-- Justification: docs/data-model.md. Migrations (#11) are generated from this file.
--
-- Conventions
--   * uuidv7() primary keys: not guessable in URLs, time-ordered for index locality.
--   * Every tenant table carries org_id and is protected by row-level security.
--   * Child tables reference parents with (org_id, id) so a row can never point
--     to a parent from another organisation, even if the application has a bug.

BEGIN;

-- ---------------------------------------------------------------------------
-- Enums
-- ---------------------------------------------------------------------------

CREATE TYPE member_role       AS ENUM ('student', 'teacher', 'admin');
CREATE TYPE course_status     AS ENUM ('draft', 'published', 'archived');
CREATE TYPE video_status      AS ENUM ('uploaded', 'processing', 'ready', 'failed');
CREATE TYPE transcript_status AS ENUM ('pending', 'processing', 'ready', 'failed');
CREATE TYPE comment_status    AS ENUM ('visible', 'hidden', 'deleted');
CREATE TYPE code_language     AS ENUM ('python', 'javascript');
CREATE TYPE submission_status AS ENUM ('queued', 'running', 'passed', 'failed', 'error');
CREATE TYPE question_kind     AS ENUM ('single', 'multiple');
CREATE TYPE progress_source   AS ENUM ('manual', 'exercise', 'quiz');
CREATE TYPE playback_event    AS ENUM ('play', 'pause', 'seek', 'heartbeat', 'ended');
CREATE TYPE job_type          AS ENUM ('transcode', 'transcribe', 'run_code', 'aggregate_analytics');
CREATE TYPE job_status        AS ENUM ('queued', 'running', 'succeeded', 'failed', 'dead');

-- ---------------------------------------------------------------------------
-- Tenancy and identity
-- ---------------------------------------------------------------------------

CREATE TABLE organizations (
    id         uuid PRIMARY KEY DEFAULT uuidv7(),
    name       text NOT NULL CHECK (length(name) BETWEEN 1 AND 120),
    slug       text NOT NULL UNIQUE CHECK (slug ~ '^[a-z0-9][a-z0-9-]{1,62}$'),
    created_at timestamptz NOT NULL DEFAULT now()
);

-- Users are global: one account can belong to several organisations.
CREATE TABLE users (
    id            uuid PRIMARY KEY DEFAULT uuidv7(),
    email         text NOT NULL CHECK (email ~ '^[^@\s]+@[^@\s]+$'),
    password_hash text,            -- NULL for accounts that only use OAuth (bonus #47)
    display_name  text NOT NULL CHECK (length(display_name) BETWEEN 1 AND 80),
    is_active     boolean NOT NULL DEFAULT true,
    created_at    timestamptz NOT NULL DEFAULT now(),
    last_login_at timestamptz
);
CREATE UNIQUE INDEX users_email_key ON users (lower(email));

-- The role belongs to the membership, not the user: the same person can be a
-- teacher in one organisation and a student in another.
CREATE TABLE memberships (
    org_id    uuid NOT NULL REFERENCES organizations ON DELETE CASCADE,
    user_id   uuid NOT NULL REFERENCES users ON DELETE CASCADE,
    role      member_role NOT NULL DEFAULT 'student',
    joined_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (org_id, user_id)
);
CREATE INDEX ON memberships (user_id);

-- Server-side sessions. Only a hash of the cookie token is stored.
CREATE TABLE sessions (
    token_hash   bytea PRIMARY KEY,
    user_id      uuid NOT NULL REFERENCES users ON DELETE CASCADE,
    org_id       uuid REFERENCES organizations ON DELETE SET NULL,  -- active organisation
    created_at   timestamptz NOT NULL DEFAULT now(),
    expires_at   timestamptz NOT NULL,
    user_agent   text,
    CHECK (expires_at > created_at)
);
CREATE INDEX ON sessions (user_id);
CREATE INDEX ON sessions (expires_at);

CREATE TABLE org_quotas (
    org_id                  uuid PRIMARY KEY REFERENCES organizations ON DELETE CASCADE,
    max_video_storage_bytes bigint NOT NULL DEFAULT 10737418240 CHECK (max_video_storage_bytes >= 0), -- 10 GiB
    max_code_runs_per_month integer NOT NULL DEFAULT 5000 CHECK (max_code_runs_per_month >= 0)
);

-- ---------------------------------------------------------------------------
-- Catalogue and content
-- ---------------------------------------------------------------------------

CREATE TABLE categories (
    id     uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id uuid NOT NULL REFERENCES organizations ON DELETE CASCADE,
    name   text NOT NULL CHECK (length(name) BETWEEN 1 AND 80),
    slug   text NOT NULL CHECK (slug ~ '^[a-z0-9][a-z0-9-]*$'),
    UNIQUE (org_id, slug),
    UNIQUE (org_id, id)
);

CREATE TABLE courses (
    id           uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id       uuid NOT NULL REFERENCES organizations ON DELETE CASCADE,
    owner_id     uuid NOT NULL,
    category_id  uuid,
    title        text NOT NULL CHECK (length(title) BETWEEN 1 AND 200),
    slug         text NOT NULL CHECK (slug ~ '^[a-z0-9][a-z0-9-]*$'),
    description  text NOT NULL DEFAULT '',
    status       course_status NOT NULL DEFAULT 'draft',
    published_at timestamptz,
    created_at   timestamptz NOT NULL DEFAULT now(),
    updated_at   timestamptz NOT NULL DEFAULT now(),
    UNIQUE (org_id, slug),
    UNIQUE (org_id, id),
    -- The owner must be a member of the same organisation.
    FOREIGN KEY (org_id, owner_id) REFERENCES memberships (org_id, user_id),
    FOREIGN KEY (org_id, category_id) REFERENCES categories (org_id, id) ON DELETE SET NULL (category_id),
    CHECK ((status = 'published') = (published_at IS NOT NULL) OR status = 'archived')
);
CREATE INDEX ON courses (org_id, status, category_id);
CREATE INDEX ON courses (org_id, owner_id);

CREATE TABLE lessons (
    id            uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id        uuid NOT NULL,
    course_id     uuid NOT NULL,
    position      integer NOT NULL CHECK (position >= 0),
    title         text NOT NULL CHECK (length(title) BETWEEN 1 AND 200),
    body_markdown text,            -- the text part of the lesson; sanitised when rendered
    created_at    timestamptz NOT NULL DEFAULT now(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, course_id) REFERENCES courses (org_id, id) ON DELETE CASCADE,
    -- Deferrable so lessons can be reordered by swapping positions in one transaction.
    UNIQUE (course_id, position) DEFERRABLE INITIALLY IMMEDIATE
);

-- A lesson has at most one video, one exercise and one quiz (lesson_id is UNIQUE
-- in each table). Any combination is allowed, as the business rules require.
CREATE TABLE videos (
    id                  uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id              uuid NOT NULL,
    lesson_id           uuid NOT NULL UNIQUE,
    status              video_status NOT NULL DEFAULT 'uploaded',
    source_object_key   text NOT NULL,   -- original upload in MinIO
    hls_master_key      text,            -- master.m3u8 once transcoded
    thumbnail_key       text,
    duration_ms         integer CHECK (duration_ms > 0),
    storage_bytes       bigint NOT NULL DEFAULT 0 CHECK (storage_bytes >= 0), -- source + renditions, for quotas
    language            regconfig NOT NULL DEFAULT 'french',  -- Whisper language and full-text config
    transcript_status   transcript_status NOT NULL DEFAULT 'pending',
    error               text,
    created_at          timestamptz NOT NULL DEFAULT now(),
    ready_at            timestamptz,
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE,
    -- A video can only be "ready" (visible to students) once its HLS output exists.
    CHECK (status <> 'ready' OR (hls_master_key IS NOT NULL AND duration_ms IS NOT NULL AND ready_at IS NOT NULL)),
    CHECK (language IN ('french'::regconfig, 'english'::regconfig, 'simple'::regconfig))
);
CREATE INDEX ON videos (org_id, status);

CREATE TABLE transcript_segments (
    id       bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    org_id   uuid NOT NULL,
    video_id uuid NOT NULL,
    start_ms integer NOT NULL CHECK (start_ms >= 0),
    end_ms   integer NOT NULL,
    language regconfig NOT NULL,
    text     text NOT NULL,
    tsv      tsvector GENERATED ALWAYS AS (to_tsvector(language, text)) STORED,
    FOREIGN KEY (org_id, video_id) REFERENCES videos (org_id, id) ON DELETE CASCADE,
    CHECK (end_ms > start_ms)
);
CREATE INDEX ON transcript_segments USING gin (tsv);
CREATE INDEX ON transcript_segments (video_id, start_ms);

CREATE TABLE comments (
    id         uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id     uuid NOT NULL,
    lesson_id  uuid NOT NULL,
    author_id  uuid NOT NULL,
    parent_id  uuid,                     -- set for replies (one level only)
    timecode_ms integer CHECK (timecode_ms >= 0),  -- moment of the video the comment is about
    body       text NOT NULL CHECK (length(body) BETWEEN 1 AND 5000),
    status     comment_status NOT NULL DEFAULT 'visible',
    created_at timestamptz NOT NULL DEFAULT now(),
    edited_at  timestamptz,
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, author_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, parent_id) REFERENCES comments (org_id, id) ON DELETE CASCADE,
    CHECK (parent_id IS NULL OR parent_id <> id)
);
CREATE INDEX ON comments (lesson_id, created_at);
CREATE INDEX ON comments (lesson_id, timecode_ms) WHERE timecode_ms IS NOT NULL;
CREATE INDEX ON comments (parent_id);

CREATE TABLE comment_reports (
    org_id      uuid NOT NULL,
    comment_id  uuid NOT NULL,
    reporter_id uuid NOT NULL,
    reason      text NOT NULL CHECK (length(reason) BETWEEN 1 AND 500),
    created_at  timestamptz NOT NULL DEFAULT now(),
    resolved_at timestamptz,
    PRIMARY KEY (comment_id, reporter_id),   -- one report per user and comment
    FOREIGN KEY (org_id, comment_id) REFERENCES comments (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, reporter_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE
);
CREATE INDEX ON comment_reports (org_id) WHERE resolved_at IS NULL;

-- ---------------------------------------------------------------------------
-- Code exercises
-- ---------------------------------------------------------------------------

CREATE TABLE exercises (
    id                 uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id             uuid NOT NULL,
    lesson_id          uuid NOT NULL UNIQUE,
    language           code_language NOT NULL,
    statement_markdown text NOT NULL,
    starter_code       text NOT NULL DEFAULT '',
    solution_code      text NOT NULL,  -- reference solution, never sent to students
    time_limit_ms      integer NOT NULL DEFAULT 2000 CHECK (time_limit_ms BETWEEN 100 AND 10000),
    memory_limit_mb    integer NOT NULL DEFAULT 128  CHECK (memory_limit_mb BETWEEN 16 AND 512),
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE
);

-- Input/output tests: the sandbox feeds stdin and compares stdout. This works the
-- same for every language and keeps hidden tests on the server.
CREATE TABLE exercise_tests (
    id              uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id          uuid NOT NULL,
    exercise_id     uuid NOT NULL,
    position        integer NOT NULL CHECK (position >= 0),
    name            text NOT NULL CHECK (length(name) BETWEEN 1 AND 120),
    stdin           text NOT NULL DEFAULT '',
    expected_stdout text NOT NULL,
    is_hidden       boolean NOT NULL DEFAULT false,
    UNIQUE (org_id, id),
    UNIQUE (exercise_id, position),
    FOREIGN KEY (org_id, exercise_id) REFERENCES exercises (org_id, id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    id           uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id       uuid NOT NULL,
    exercise_id  uuid NOT NULL,
    user_id      uuid NOT NULL,
    code         text NOT NULL CHECK (octet_length(code) <= 65536),
    status       submission_status NOT NULL DEFAULT 'queued',
    tests_passed integer CHECK (tests_passed >= 0),
    tests_total  integer CHECK (tests_total >= 0),
    created_at   timestamptz NOT NULL DEFAULT now(),
    finished_at  timestamptz,
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, exercise_id) REFERENCES exercises (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, user_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE,
    CHECK (tests_passed <= tests_total),
    CHECK ((status IN ('queued', 'running')) = (finished_at IS NULL))
);
CREATE INDEX ON submissions (exercise_id, user_id, created_at DESC);
CREATE INDEX ON submissions (org_id, created_at);  -- monthly code-run quota

CREATE TABLE submission_results (
    org_id        uuid NOT NULL,
    submission_id uuid NOT NULL,
    test_id       uuid NOT NULL,
    passed        boolean NOT NULL,
    actual_stdout text CHECK (octet_length(actual_stdout) <= 16384),  -- truncated by the runner
    stderr        text CHECK (octet_length(stderr) <= 16384),
    exit_code     integer,
    duration_ms   integer CHECK (duration_ms >= 0),
    timed_out     boolean NOT NULL DEFAULT false,
    PRIMARY KEY (submission_id, test_id),
    FOREIGN KEY (org_id, submission_id) REFERENCES submissions (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, test_id) REFERENCES exercise_tests (org_id, id) ON DELETE CASCADE
);
CREATE INDEX ON submission_results (test_id) WHERE NOT passed;  -- failure rate per test

-- ---------------------------------------------------------------------------
-- Quizzes
-- ---------------------------------------------------------------------------

CREATE TABLE quizzes (
    id                 uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id             uuid NOT NULL,
    lesson_id          uuid NOT NULL UNIQUE,
    pass_threshold_pct smallint NOT NULL DEFAULT 70 CHECK (pass_threshold_pct BETWEEN 0 AND 100),
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE
);

CREATE TABLE quiz_questions (
    id       uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id   uuid NOT NULL,
    quiz_id  uuid NOT NULL,
    position integer NOT NULL CHECK (position >= 0),
    prompt   text NOT NULL CHECK (length(prompt) BETWEEN 1 AND 2000),
    kind     question_kind NOT NULL DEFAULT 'single',
    UNIQUE (org_id, id),
    UNIQUE (quiz_id, position),
    FOREIGN KEY (org_id, quiz_id) REFERENCES quizzes (org_id, id) ON DELETE CASCADE
);

CREATE TABLE quiz_choices (
    id          uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id      uuid NOT NULL,
    question_id uuid NOT NULL,
    position    integer NOT NULL CHECK (position >= 0),
    label       text NOT NULL CHECK (length(label) BETWEEN 1 AND 500),
    is_correct  boolean NOT NULL DEFAULT false,  -- never sent to students before they answer
    UNIQUE (org_id, id),
    UNIQUE (question_id, position),
    UNIQUE (question_id, id),
    FOREIGN KEY (org_id, question_id) REFERENCES quiz_questions (org_id, id) ON DELETE CASCADE
);

CREATE TABLE quiz_attempts (
    id         uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id     uuid NOT NULL,
    quiz_id    uuid NOT NULL,
    user_id    uuid NOT NULL,
    score_pct  smallint NOT NULL CHECK (score_pct BETWEEN 0 AND 100),
    passed     boolean NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (org_id, id),
    FOREIGN KEY (org_id, quiz_id) REFERENCES quizzes (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, user_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE
);
CREATE INDEX ON quiz_attempts (quiz_id, user_id);

CREATE TABLE quiz_attempt_answers (
    org_id      uuid NOT NULL,
    attempt_id  uuid NOT NULL,
    question_id uuid NOT NULL,
    choice_id   uuid NOT NULL,
    PRIMARY KEY (attempt_id, choice_id),
    FOREIGN KEY (org_id, attempt_id) REFERENCES quiz_attempts (org_id, id) ON DELETE CASCADE,
    -- The chosen answer must belong to the question it answers.
    FOREIGN KEY (question_id, choice_id) REFERENCES quiz_choices (question_id, id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------------
-- Learning path and progress
-- ---------------------------------------------------------------------------

-- Edge "lesson_id requires prerequisite_id". The graph must stay acyclic (DAG):
-- enforced by the trigger below.
CREATE TABLE lesson_prerequisites (
    org_id          uuid NOT NULL,
    lesson_id       uuid NOT NULL,
    prerequisite_id uuid NOT NULL,
    PRIMARY KEY (lesson_id, prerequisite_id),
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, prerequisite_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE,
    CHECK (lesson_id <> prerequisite_id)
);
CREATE INDEX ON lesson_prerequisites (prerequisite_id);

CREATE TABLE enrollments (
    org_id      uuid NOT NULL,
    user_id     uuid NOT NULL,
    course_id   uuid NOT NULL,
    enrolled_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, course_id),
    FOREIGN KEY (org_id, user_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, course_id) REFERENCES courses (org_id, id) ON DELETE CASCADE
);
CREATE INDEX ON enrollments (course_id);

-- One row = the lesson is completed by the user. Course progress is computed
-- (completed lessons / lessons), never stored, so it cannot drift.
CREATE TABLE lesson_progress (
    org_id       uuid NOT NULL,
    user_id      uuid NOT NULL,
    lesson_id    uuid NOT NULL,
    source       progress_source NOT NULL,
    completed_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, lesson_id),
    FOREIGN KEY (org_id, user_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, lesson_id) REFERENCES lessons (org_id, id) ON DELETE CASCADE
);
CREATE INDEX ON lesson_progress (lesson_id);

-- ---------------------------------------------------------------------------
-- Analytics
-- ---------------------------------------------------------------------------

-- High-volume, append-only. Partitioned by month so old data is dropped with
-- DROP TABLE on a partition instead of a slow DELETE.
CREATE TABLE playback_events (
    id                  bigint GENERATED ALWAYS AS IDENTITY,
    org_id              uuid NOT NULL,
    video_id            uuid NOT NULL,
    user_id             uuid NOT NULL,
    playback_session_id uuid NOT NULL,  -- one per page load of the player
    event               playback_event NOT NULL,
    position_ms         integer NOT NULL CHECK (position_ms >= 0),
    occurred_at         timestamptz NOT NULL,
    received_at         timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (id, occurred_at),
    FOREIGN KEY (org_id, video_id) REFERENCES videos (org_id, id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, user_id) REFERENCES memberships (org_id, user_id) ON DELETE CASCADE
) PARTITION BY RANGE (occurred_at);
CREATE INDEX ON playback_events (video_id, occurred_at);
CREATE TABLE playback_events_2026_10 PARTITION OF playback_events FOR VALUES FROM ('2026-10-01') TO ('2026-11-01');
CREATE TABLE playback_events_2026_11 PARTITION OF playback_events FOR VALUES FROM ('2026-11-01') TO ('2026-12-01');
CREATE TABLE playback_events_2026_12 PARTITION OF playback_events FOR VALUES FROM ('2026-12-01') TO ('2027-01-01');
CREATE TABLE playback_events_default PARTITION OF playback_events DEFAULT;

-- Drop-off curve, filled by the aggregate_analytics job. A table rather than a
-- materialized view because materialized views do not support row-level security.
CREATE TABLE video_retention (
    org_id         uuid NOT NULL,
    video_id       uuid NOT NULL,
    bucket_start_s integer NOT NULL CHECK (bucket_start_s >= 0),  -- 10-second buckets
    viewers        integer NOT NULL CHECK (viewers >= 0),
    computed_at    timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (video_id, bucket_start_s),
    FOREIGN KEY (org_id, video_id) REFERENCES videos (org_id, id) ON DELETE CASCADE
);

-- Failure rate per exercise, computed live (submissions are low-volume).
-- security_invoker makes the view respect the caller's row-level security.
CREATE VIEW exercise_stats WITH (security_invoker = true) AS
SELECT e.org_id,
       e.id                                                    AS exercise_id,
       count(s.id)                                             AS submissions,
       count(DISTINCT s.user_id)                               AS students,
       count(s.id) FILTER (WHERE s.status = 'failed')          AS failed,
       round(100.0 * count(s.id) FILTER (WHERE s.status = 'failed')
             / nullif(count(s.id) FILTER (WHERE s.status IN ('passed', 'failed')), 0), 1) AS failure_rate_pct
FROM exercises e
LEFT JOIN submissions s ON s.exercise_id = e.id
GROUP BY e.org_id, e.id;

-- ---------------------------------------------------------------------------
-- Job queue (one queue, several job types)
-- ---------------------------------------------------------------------------

-- Not exposed through the API: the UI reads the status of the domain row
-- (videos.status, submissions.status). Workers claim jobs with
-- SELECT ... FOR UPDATE SKIP LOCKED.
CREATE TABLE jobs (
    id           uuid PRIMARY KEY DEFAULT uuidv7(),
    org_id       uuid REFERENCES organizations ON DELETE CASCADE,  -- NULL for system jobs
    type         job_type NOT NULL,
    payload      jsonb NOT NULL DEFAULT '{}',
    status       job_status NOT NULL DEFAULT 'queued',
    priority     smallint NOT NULL DEFAULT 0,
    attempts     smallint NOT NULL DEFAULT 0 CHECK (attempts >= 0),
    max_attempts smallint NOT NULL DEFAULT 3 CHECK (max_attempts > 0),
    run_at       timestamptz NOT NULL DEFAULT now(),
    locked_at    timestamptz,
    locked_by    text,
    last_error   text,
    created_at   timestamptz NOT NULL DEFAULT now(),
    finished_at  timestamptz,
    CHECK (attempts <= max_attempts),
    CHECK ((status = 'running') = (locked_at IS NOT NULL))
);
-- Small partial index: only waiting jobs, in the order workers pick them.
CREATE INDEX jobs_pick_idx ON jobs (type, priority DESC, run_at) WHERE status = 'queued';
CREATE INDEX ON jobs (status, locked_at) WHERE status = 'running';  -- detect stuck jobs

-- ---------------------------------------------------------------------------
-- Business rules enforced in the database
-- ---------------------------------------------------------------------------

-- Rule: a course must contain at least one lesson before it is published.
CREATE FUNCTION check_course_publishable() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF NEW.status = 'published' AND OLD.status IS DISTINCT FROM 'published'
       AND NOT EXISTS (SELECT 1 FROM lessons WHERE course_id = NEW.id) THEN
        RAISE EXCEPTION 'course % has no lesson and cannot be published', NEW.id
            USING ERRCODE = 'check_violation';
    END IF;
    RETURN NEW;
END $$;
CREATE TRIGGER courses_publishable BEFORE UPDATE OF status ON courses
    FOR EACH ROW EXECUTE FUNCTION check_course_publishable();

-- Rule: prerequisites cannot form a cycle.
-- Adding "A requires B" creates a cycle if A is already a (transitive)
-- prerequisite of B. The advisory lock serialises concurrent edge inserts
-- within an organisation so two inserts cannot each create half of a cycle.
CREATE FUNCTION check_prerequisite_acyclic() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    PERFORM pg_advisory_xact_lock(hashtextextended(NEW.org_id::text, 0));
    IF EXISTS (
        WITH RECURSIVE ancestors(id) AS (
            SELECT prerequisite_id FROM lesson_prerequisites WHERE lesson_id = NEW.prerequisite_id
            UNION
            SELECT p.prerequisite_id FROM lesson_prerequisites p JOIN ancestors a ON p.lesson_id = a.id
        )
        SELECT 1 FROM ancestors WHERE id = NEW.lesson_id
    ) THEN
        RAISE EXCEPTION 'prerequisite % -> % would create a cycle', NEW.lesson_id, NEW.prerequisite_id
            USING ERRCODE = 'check_violation';
    END IF;
    RETURN NEW;
END $$;
CREATE TRIGGER lesson_prerequisites_acyclic BEFORE INSERT OR UPDATE ON lesson_prerequisites
    FOR EACH ROW EXECUTE FUNCTION check_prerequisite_acyclic();

-- Rule: replies are one level deep and stay on the same lesson as their parent.
CREATE FUNCTION check_comment_reply() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
    parent comments%ROWTYPE;
BEGIN
    IF NEW.parent_id IS NULL THEN
        RETURN NEW;
    END IF;
    SELECT * INTO parent FROM comments WHERE id = NEW.parent_id;
    IF parent.parent_id IS NOT NULL OR parent.lesson_id <> NEW.lesson_id THEN
        RAISE EXCEPTION 'invalid reply target %', NEW.parent_id USING ERRCODE = 'check_violation';
    END IF;
    RETURN NEW;
END $$;
CREATE TRIGGER comments_reply BEFORE INSERT OR UPDATE OF parent_id, lesson_id ON comments
    FOR EACH ROW EXECUTE FUNCTION check_comment_reply();

CREATE FUNCTION touch_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END $$;
CREATE TRIGGER courses_touch BEFORE UPDATE ON courses FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER lessons_touch BEFORE UPDATE ON lessons FOR EACH ROW EXECUTE FUNCTION touch_updated_at();

-- ---------------------------------------------------------------------------
-- Tenant isolation (row-level security)
-- ---------------------------------------------------------------------------

-- The API connects as app_api and runs, at the start of each transaction:
--   SET LOCAL app.org_id  = '<active organisation of the session>';
--   SET LOCAL app.user_id = '<logged-in user>';
-- If a setting is missing, the function returns NULL and no row matches:
-- the policies fail closed.
CREATE FUNCTION current_org_id() RETURNS uuid LANGUAGE sql STABLE AS $$
    SELECT nullif(current_setting('app.org_id', true), '')::uuid
$$;
CREATE FUNCTION current_user_id() RETURNS uuid LANGUAGE sql STABLE AS $$
    SELECT nullif(current_setting('app.user_id', true), '')::uuid
$$;

-- A user can see their own memberships in every organisation (to switch
-- organisation), and the organisations they belong to. Nothing else crosses
-- the tenant boundary.
ALTER TABLE organizations ENABLE ROW LEVEL SECURITY;
ALTER TABLE organizations FORCE ROW LEVEL SECURITY;
CREATE POLICY org_visible ON organizations FOR SELECT USING (
    id = current_org_id()
    OR EXISTS (SELECT 1 FROM memberships m WHERE m.org_id = organizations.id AND m.user_id = current_user_id())
);
CREATE POLICY org_create ON organizations FOR INSERT WITH CHECK (true);
CREATE POLICY org_update ON organizations FOR UPDATE USING (id = current_org_id());

DO $$
DECLARE
    t text;
BEGIN
    FOREACH t IN ARRAY ARRAY[
        'memberships', 'org_quotas', 'categories', 'courses', 'lessons', 'videos',
        'transcript_segments', 'comments', 'comment_reports', 'exercises', 'exercise_tests',
        'submissions', 'submission_results', 'quizzes', 'quiz_questions', 'quiz_choices',
        'quiz_attempts', 'quiz_attempt_answers', 'lesson_prerequisites', 'enrollments',
        'lesson_progress', 'playback_events', 'video_retention'
    ] LOOP
        EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY', t);
        EXECUTE format('ALTER TABLE %I FORCE ROW LEVEL SECURITY', t);
        EXECUTE format(
            'CREATE POLICY tenant_isolation ON %I USING (org_id = current_org_id()) WITH CHECK (org_id = current_org_id())', t);
    END LOOP;
END $$;
CREATE POLICY own_memberships ON memberships FOR SELECT USING (user_id = current_user_id());

-- Database roles. Passwords are set at deploy time from secrets, never here.
--   app_api    : used by the API, subject to row-level security.
--   app_worker : used by the worker, which processes jobs of every organisation.
--                It sets app.org_id from jobs.org_id before touching tenant data.
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'app_api') THEN
        CREATE ROLE app_api NOLOGIN;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'app_worker') THEN
        CREATE ROLE app_worker NOLOGIN;
    END IF;
END $$;
GRANT USAGE ON SCHEMA public TO app_api, app_worker;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO app_api, app_worker;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO app_api, app_worker;
REVOKE ALL ON jobs FROM app_api;
GRANT INSERT ON jobs TO app_api;  -- the API only enqueues

-- Row-level security applies when querying the partitioned parent, not the
-- partitions themselves, so the application roles get no direct access to them.
DO $$
DECLARE
    p regclass;
BEGIN
    FOR p IN SELECT inhrelid::regclass FROM pg_inherits WHERE inhparent = 'playback_events'::regclass LOOP
        EXECUTE format('REVOKE ALL ON %s FROM app_api, app_worker', p);
    END LOOP;
END $$;

COMMIT;
