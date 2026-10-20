-- Executable checks for the business rules enforced by schema.sql.
-- Run against an empty database, as a superuser, after the schema:
--   psql -v ON_ERROR_STOP=1 -f schema.sql -f checks.sql
-- Every check prints "ok: ..." or the script stops with an error.

\set QUIET on
\o /dev/null
SET client_min_messages = notice;

CREATE FUNCTION pg_temp.expect_error(label text, stmt text, code text) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    EXECUTE stmt;
    RAISE EXCEPTION 'FAILED: % (statement succeeded)', label;
EXCEPTION
    WHEN OTHERS THEN
        IF SQLSTATE = code THEN
            RAISE NOTICE 'ok: %', label;
        ELSE
            RAISE EXCEPTION 'FAILED: % (got % %, expected %)', label, SQLSTATE, SQLERRM, code;
        END IF;
END $$;

CREATE FUNCTION pg_temp.expect(label text, ok boolean) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF NOT ok THEN
        RAISE EXCEPTION 'FAILED: %', label;
    END IF;
    RAISE NOTICE 'ok: %', label;
END $$;

-- Fixtures: two organisations, each with a teacher and a course. Inserted as
-- superuser, which bypasses row-level security.
INSERT INTO organizations (id, name, slug) VALUES
    ('00000000-0000-7000-8000-00000000000a', 'Org A', 'org-a'),
    ('00000000-0000-7000-8000-00000000000b', 'Org B', 'org-b');
INSERT INTO users (id, email, password_hash, display_name) VALUES
    ('00000000-0000-7000-8000-0000000000a1', 'teacher@a.test', 'x', 'Teacher A'),
    ('00000000-0000-7000-8000-0000000000a2', 'student@a.test', 'x', 'Student A'),
    ('00000000-0000-7000-8000-0000000000b1', 'teacher@b.test', 'x', 'Teacher B');
INSERT INTO memberships (org_id, user_id, role) VALUES
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-0000000000a1', 'teacher'),
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-0000000000a2', 'student'),
    ('00000000-0000-7000-8000-00000000000b', '00000000-0000-7000-8000-0000000000b1', 'teacher');
INSERT INTO courses (id, org_id, owner_id, title, slug) VALUES
    ('00000000-0000-7000-8000-000000000ca1', '00000000-0000-7000-8000-00000000000a',
     '00000000-0000-7000-8000-0000000000a1', 'Python basics', 'python-basics'),
    ('00000000-0000-7000-8000-000000000cb1', '00000000-0000-7000-8000-00000000000b',
     '00000000-0000-7000-8000-0000000000b1', 'JS basics', 'js-basics');

-- Rule: a course needs at least one lesson before publication.
SELECT pg_temp.expect_error('publishing a course without lessons is rejected',
    $$UPDATE courses SET status = 'published', published_at = now()
      WHERE id = '00000000-0000-7000-8000-000000000ca1'$$, '23514');

INSERT INTO lessons (id, org_id, course_id, position, title, body_markdown)
SELECT ('00000000-0000-7000-8000-0000000001' || lpad(n::text, 2, '0'))::uuid,
       '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000ca1', n, 'Lesson ' || n, 'text'
FROM generate_series(1, 4) n;

UPDATE courses SET status = 'published', published_at = now() WHERE id = '00000000-0000-7000-8000-000000000ca1';
SELECT pg_temp.expect('a course with lessons can be published',
    (SELECT status = 'published' FROM courses WHERE id = '00000000-0000-7000-8000-000000000ca1'));

-- Rule: lessons can be reordered (deferrable unique position).
BEGIN;
SET CONSTRAINTS ALL DEFERRED;
UPDATE lessons SET position = 2 WHERE id = '00000000-0000-7000-8000-000000000101';
UPDATE lessons SET position = 1 WHERE id = '00000000-0000-7000-8000-000000000102';
COMMIT;
SELECT pg_temp.expect('lessons can swap positions in one transaction', true);

-- Rule: data can never point across organisations.
SELECT pg_temp.expect_error('a lesson of org B cannot belong to a course of org A',
    $$INSERT INTO lessons (org_id, course_id, position, title)
      VALUES ('00000000-0000-7000-8000-00000000000b', '00000000-0000-7000-8000-000000000ca1', 9, 'x')$$, '23503');
SELECT pg_temp.expect_error('a course owner must be a member of the course organisation',
    $$INSERT INTO courses (org_id, owner_id, title, slug)
      VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-0000000000b1', 'x', 'x')$$, '23503');

-- Rule: a video is only visible (ready) once transcoding produced its output.
INSERT INTO videos (id, org_id, lesson_id, source_object_key)
VALUES ('00000000-0000-7000-8000-000000000f01', '00000000-0000-7000-8000-00000000000a',
        '00000000-0000-7000-8000-000000000101', 'org-a/uploads/v1.mp4');
SELECT pg_temp.expect_error('a video cannot be ready without its HLS output',
    $$UPDATE videos SET status = 'ready' WHERE id = '00000000-0000-7000-8000-000000000f01'$$, '23514');
UPDATE videos SET status = 'ready', hls_master_key = 'org-a/hls/v1/master.m3u8', duration_ms = 600000, ready_at = now()
WHERE id = '00000000-0000-7000-8000-000000000f01';
SELECT pg_temp.expect('a transcoded video can be ready', true);

-- Rule: a lesson has at most one video.
SELECT pg_temp.expect_error('a lesson cannot have two videos',
    $$INSERT INTO videos (org_id, lesson_id, source_object_key)
      VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000101', 'k')$$, '23505');

-- Full-text search on transcripts.
INSERT INTO transcript_segments (org_id, video_id, start_ms, end_ms, language, text) VALUES
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000f01', 0, 4000, 'french',
     'Bonjour, aujourd''hui nous allons voir les boucles.'),
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000f01', 63000, 68000, 'french',
     'Une fonction récursive s''appelle elle-même.');
SELECT pg_temp.expect('searching "fonctions récursives" finds the segment at 63 s (French stemming)',
    (SELECT start_ms FROM transcript_segments
     WHERE tsv @@ websearch_to_tsquery('french', 'fonctions récursives')) = 63000);

-- Rule: prerequisites form a DAG.
INSERT INTO lesson_prerequisites (org_id, lesson_id, prerequisite_id) VALUES
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000102', '00000000-0000-7000-8000-000000000101'),
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000103', '00000000-0000-7000-8000-000000000102');
SELECT pg_temp.expect_error('a lesson cannot be its own prerequisite',
    $$INSERT INTO lesson_prerequisites VALUES ('00000000-0000-7000-8000-00000000000a',
      '00000000-0000-7000-8000-000000000101', '00000000-0000-7000-8000-000000000101')$$, '23514');
SELECT pg_temp.expect_error('a prerequisite cycle 1 -> 3 -> 2 -> 1 is rejected',
    $$INSERT INTO lesson_prerequisites VALUES ('00000000-0000-7000-8000-00000000000a',
      '00000000-0000-7000-8000-000000000101', '00000000-0000-7000-8000-000000000103')$$, '23514');
INSERT INTO lesson_prerequisites VALUES ('00000000-0000-7000-8000-00000000000a',
    '00000000-0000-7000-8000-000000000104', '00000000-0000-7000-8000-000000000101');
SELECT pg_temp.expect('a diamond (4 and 2 both require 1) is allowed', true);

-- Rule: a lesson is unlocked when all its prerequisites are completed.
INSERT INTO lesson_progress (org_id, user_id, lesson_id, source) VALUES
    ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-0000000000a2', '00000000-0000-7000-8000-000000000101', 'manual');
SELECT pg_temp.expect('after completing lesson 1, lesson 2 is unlocked and lesson 3 is still locked',
    (SELECT array_agg(l.id ORDER BY l.id) FROM lessons l
     WHERE l.course_id = '00000000-0000-7000-8000-000000000ca1'
       AND NOT EXISTS (
           SELECT 1 FROM lesson_prerequisites p
           WHERE p.lesson_id = l.id
             AND NOT EXISTS (SELECT 1 FROM lesson_progress lp
                             WHERE lp.lesson_id = p.prerequisite_id
                               AND lp.user_id = '00000000-0000-7000-8000-0000000000a2')))
    = ARRAY['00000000-0000-7000-8000-000000000101', '00000000-0000-7000-8000-000000000102',
            '00000000-0000-7000-8000-000000000104']::uuid[]);

-- Rule: comments, replies one level deep.
INSERT INTO comments (id, org_id, lesson_id, author_id, timecode_ms, body) VALUES
    ('00000000-0000-7000-8000-00000000c001', '00000000-0000-7000-8000-00000000000a',
     '00000000-0000-7000-8000-000000000101', '00000000-0000-7000-8000-0000000000a2', 63000, 'Why recursion here?');
INSERT INTO comments (id, org_id, lesson_id, author_id, parent_id, body) VALUES
    ('00000000-0000-7000-8000-00000000c002', '00000000-0000-7000-8000-00000000000a',
     '00000000-0000-7000-8000-000000000101', '00000000-0000-7000-8000-0000000000a1',
     '00000000-0000-7000-8000-00000000c001', 'Because...');
SELECT pg_temp.expect_error('a reply to a reply is rejected',
    $$INSERT INTO comments (org_id, lesson_id, author_id, parent_id, body)
      VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000101',
              '00000000-0000-7000-8000-0000000000a2', '00000000-0000-7000-8000-00000000c002', 'x')$$, '23514');
SELECT pg_temp.expect_error('an empty comment is rejected',
    $$INSERT INTO comments (org_id, lesson_id, author_id, body)
      VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000101',
              '00000000-0000-7000-8000-0000000000a2', '')$$, '23514');

-- Rule: a quiz answer must belong to the question it answers.
INSERT INTO quizzes (id, org_id, lesson_id) VALUES
    ('00000000-0000-7000-8000-00000000d001', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000102');
INSERT INTO quiz_questions (id, org_id, quiz_id, position, prompt) VALUES
    ('00000000-0000-7000-8000-00000000d101', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000d001', 0, 'Q1'),
    ('00000000-0000-7000-8000-00000000d102', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000d001', 1, 'Q2');
INSERT INTO quiz_choices (id, org_id, question_id, position, label, is_correct) VALUES
    ('00000000-0000-7000-8000-00000000d201', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000d101', 0, 'A', true),
    ('00000000-0000-7000-8000-00000000d202', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000d102', 0, 'B', true);
INSERT INTO quiz_attempts (id, org_id, quiz_id, user_id, score_pct, passed) VALUES
    ('00000000-0000-7000-8000-00000000d301', '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000d001',
     '00000000-0000-7000-8000-0000000000a2', 100, true);
SELECT pg_temp.expect_error('a choice of Q2 cannot be recorded as the answer to Q1',
    $$INSERT INTO quiz_attempt_answers VALUES ('00000000-0000-7000-8000-00000000000a',
      '00000000-0000-7000-8000-00000000d301', '00000000-0000-7000-8000-00000000d101', '00000000-0000-7000-8000-00000000d202')$$, '23503');

-- Exercises, submissions and the failure-rate view.
INSERT INTO exercises (id, org_id, lesson_id, language, statement_markdown, solution_code) VALUES
    ('00000000-0000-7000-8000-00000000e001', '00000000-0000-7000-8000-00000000000a',
     '00000000-0000-7000-8000-000000000103', 'python', 'Print the sum', 'print(sum(map(int, input().split())))');
INSERT INTO submissions (org_id, exercise_id, user_id, code, status, tests_passed, tests_total, finished_at)
SELECT '00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000e001',
       '00000000-0000-7000-8000-0000000000a2', 'print(1)', s::submission_status, 0, 2, now()
FROM unnest(ARRAY['failed', 'failed', 'failed', 'passed']) s;
SELECT pg_temp.expect('exercise_stats reports a 75% failure rate',
    (SELECT failure_rate_pct FROM exercise_stats WHERE exercise_id = '00000000-0000-7000-8000-00000000e001') = 75.0);
SELECT pg_temp.expect_error('a finished submission must have finished_at',
    $$INSERT INTO submissions (org_id, exercise_id, user_id, code, status)
      VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-00000000e001',
              '00000000-0000-7000-8000-0000000000a2', 'x', 'passed')$$, '23514');

-- Playback events land in the right monthly partition.
INSERT INTO playback_events (org_id, video_id, user_id, playback_session_id, event, position_ms, occurred_at)
VALUES ('00000000-0000-7000-8000-00000000000a', '00000000-0000-7000-8000-000000000f01',
        '00000000-0000-7000-8000-0000000000a2', uuidv7(), 'heartbeat', 10000, '2026-11-15 10:00+00');
SELECT pg_temp.expect('a November event is stored in playback_events_2026_11',
    (SELECT count(*) FROM playback_events_2026_11) = 1);

-- Job queue: the worker claim query takes the highest-priority waiting job of
-- the types it handles. (Concurrency between workers is tested in #20.)
INSERT INTO jobs (org_id, type, payload, priority) VALUES
    ('00000000-0000-7000-8000-00000000000a', 'transcode', '{"video_id": "f01"}', 0),
    ('00000000-0000-7000-8000-00000000000a', 'run_code', '{"submission_id": "s1"}', 10),
    ('00000000-0000-7000-8000-00000000000a', 'run_code', '{"submission_id": "s2"}', 0);
DO $$
DECLARE
    claimed jsonb;
BEGIN
    UPDATE jobs SET status = 'running', locked_at = now(), locked_by = 'worker-1', attempts = attempts + 1
    WHERE id = (
        SELECT id FROM jobs
        WHERE status = 'queued' AND type = ANY ('{run_code}') AND run_at <= now()
        ORDER BY priority DESC, run_at
        FOR UPDATE SKIP LOCKED
        LIMIT 1
    )
    RETURNING payload INTO claimed;
    PERFORM pg_temp.expect('the claim query picks the priority run_code job', claimed = '{"submission_id": "s1"}');
END $$;

-- ---------------------------------------------------------------------------
-- Tenant isolation, as the API role
-- ---------------------------------------------------------------------------

SET ROLE app_api;

SELECT pg_temp.expect('without app.org_id the API sees no course (fails closed)',
    (SELECT count(*) FROM courses) = 0);

SELECT set_config('app.org_id', '00000000-0000-7000-8000-00000000000a', false);
SELECT set_config('app.user_id', '00000000-0000-7000-8000-0000000000a1', false);

SELECT pg_temp.expect('org A sees only its own course',
    (SELECT array_agg(slug) FROM courses) = ARRAY['python-basics']);
SELECT pg_temp.expect('org A sees only its own organisation',
    (SELECT array_agg(slug) FROM organizations) = ARRAY['org-a']);
SELECT pg_temp.expect('fetching the org B course by id returns nothing',
    NOT EXISTS (SELECT 1 FROM courses WHERE id = '00000000-0000-7000-8000-000000000cb1'));
DO $$
DECLARE
    n integer;
BEGIN
    UPDATE courses SET title = 'hacked' WHERE id = '00000000-0000-7000-8000-000000000cb1';
    GET DIAGNOSTICS n = ROW_COUNT;
    PERFORM pg_temp.expect('updating the org B course affects no row', n = 0);
END $$;
SELECT pg_temp.expect_error('inserting a row for org B is rejected',
    $$INSERT INTO categories (org_id, name, slug) VALUES ('00000000-0000-7000-8000-00000000000b', 'x', 'x')$$, '42501');
SELECT pg_temp.expect_error('reading a playback_events partition directly is denied',
    $$SELECT * FROM playback_events_2026_11$$, '42501');
SELECT pg_temp.expect_error('the API cannot read the job queue',
    $$SELECT * FROM jobs$$, '42501');
SELECT pg_temp.expect('search results never include other organisations',
    (SELECT count(*) FROM transcript_segments WHERE tsv @@ websearch_to_tsquery('french', 'boucles')) = 1);

-- Teacher B sees their own memberships, but not org A data.
SELECT set_config('app.org_id', '00000000-0000-7000-8000-00000000000b', false);
SELECT set_config('app.user_id', '00000000-0000-7000-8000-0000000000b1', false);
SELECT pg_temp.expect('org B search does not see org A transcripts',
    (SELECT count(*) FROM transcript_segments) = 0);
SELECT pg_temp.expect('org B exercise_stats view hides org A exercises',
    (SELECT count(*) FROM exercise_stats) = 0);

RESET ROLE;

-- Deleting an organisation removes all its data.
DELETE FROM organizations WHERE id = '00000000-0000-7000-8000-00000000000a';
SELECT pg_temp.expect('deleting org A cascades to its courses, lessons, videos and events',
    NOT EXISTS (SELECT 1 FROM courses WHERE org_id = '00000000-0000-7000-8000-00000000000a')
    AND NOT EXISTS (SELECT 1 FROM videos) AND NOT EXISTS (SELECT 1 FROM playback_events));

\echo 'All data model checks passed.'
