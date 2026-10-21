# ADR-0006: Queue driver: `database` on PostgreSQL

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
Some work should not block an HTTP request (emails, notifications, image processing, indexing). Laravel supports several queue drivers. We want to limit the number of services to deploy and operate within the project timeframe.

## Options considered
1. **`database` driver (PostgreSQL).** Uses the existing database, no extra service.
2. **Redis.** Faster, richer tooling (Horizon), one more container to deploy, secure and monitor.
3. **Synchronous driver.** No infrastructure, but no real asynchronous processing.

## Decision
We choose **option 1**. Our expected load is low, the database is already deployed, backed up and monitored, and jobs benefit from transactional guarantees. A dedicated `queue` container runs `php artisan queue:work` from the same image as the application.

## Consequences
- **Positive:** fewer moving parts, simple deployment, jobs visible and queryable in SQL, `failed_jobs` table for diagnostics.
- **Negative / trade-offs:** polling-based, lower throughput than Redis, adds write load on the main database; no Horizon dashboard.
- **Impact:** `jobs`, `job_batches`, `failed_jobs` migrations; the worker container needs a restart policy and a graceful restart on deploy (`queue:restart`). Moving to Redis later only requires changing `QUEUE_CONNECTION` and adding a service (new ADR if done).
