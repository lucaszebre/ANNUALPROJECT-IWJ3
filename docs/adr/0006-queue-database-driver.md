# ADR-0006: Queue driver: `database` on PostgreSQL, one queue and one worker per job type

- **Status:** Accepted
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
Our real background jobs are long-running and CPU-heavy: **video transcoding** (ffmpeg), **automatic transcription** (Whisper on CPU) and the **code sandbox** runs, plus small default jobs (notifications, emails). Their durations differ by orders of magnitude (seconds to possibly an hour or more). We want to avoid adding a broker (Redis) and keep every service containerized.
The `database` driver gives at-least-once delivery: a reserved job is made available again once `retry_after` seconds have passed, even if the first worker is still running it.

## Options considered
1. **`database` driver, one connection, one `retry_after` for everything.** Simple, but `retry_after` must exceed the slowest job; short jobs that crash would then wait that long before being retried.
2. **`database` driver, one named queue per job type, several connections (same driver and table) with different `retry_after`; one worker container per queue.**
3. **Redis (+ Horizon).** Better tooling, but one more stateful service to deploy and secure.

## Decision
We choose **option 2**. `retry_after` is a property of the queue *connection*, not of the queue name, so we declare several `database` connections in `config/queue.php` and give each job type its own queue, connection and worker container:

| Job type                      | Queue           | Connection      | Worker container      | Worker `--timeout` | Connection `retry_after` |
|-------------------------------|-----------------|-----------------|-----------------------|--------------------|--------------------------|
| Default (mail, notifications) | `default`       | `database`      | `queue-default`       | 60                 | 90                       |
| Transcoding (ffmpeg)          | `transcoding`   | `database_long` | `queue-transcoding`   | [T1]               | [T1 + margin]            |
| Transcription (Whisper)       | `transcription` | `database_long` | `queue-transcription` | [T2]               | [T2 + margin]            |
| Sandbox                       | `sandbox`       | `database`      | `queue-sandbox`       | [T3]               | 90                       |

Rules:
- `retry_after` **must be longer than the slowest job of that connection**, and the worker `--timeout` (or the job's `$timeout`) must be a few seconds shorter than `retry_after`. Otherwise a long ffmpeg/Whisper job is started a second time while the first is still running. Measure [T1], [T2] on representative videos and add a margin (for example 10-20%).
- Each worker container runs one worker for one queue (`php artisan queue:work database_long --queue=transcoding --timeout=[T1] --tries=2`), so CPU-heavy jobs never compete inside a container and each can be restarted, limited (CPU/memory) and scaled independently.
- Jobs declare their own `$connection`, `$queue`, `$timeout` and `$tries`, and must be **idempotent** (at-least-once delivery), with a `failed()` handler that marks the video/transcript as failed.
- The `pcntl` PHP extension is required for timeouts (it is in the image).

## Consequences
- **Positive:** no extra service, correct retry behaviour per job type, isolation of heavy jobs, simple to reason about in a defense.
- **Negative / trade-offs:** more containers (4 workers) and more configuration; the `database` driver is polling-based and adds write load on PostgreSQL; no Horizon dashboard.
- **Impact:** `config/queue.php` gets the `database_long` connection; `compose.yml` defines one worker service per queue (YAML anchor for the shared part); transcoding and transcription depend on CPU limits to be set at deployment. Moving to Redis later means changing the connections and adding a service (new ADR).
