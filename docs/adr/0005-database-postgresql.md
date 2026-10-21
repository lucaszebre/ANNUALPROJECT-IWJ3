# ADR-0005: Database: PostgreSQL

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The syllabus requires an SQL data model that we will defend during the oral. Our use case needs French-language full-text search (stemming, stop words, accent-insensitive matching).

## Options considered
1. **PostgreSQL.** Native full-text search with a `french` configuration, `unaccent`, GIN indexes.
2. **MySQL/MariaDB.** Ubiquitous with Laravel, but FULLTEXT has no real French stemming and limited configurability.
3. **SQLite.** Trivial setup, unsuitable for production concurrency and for the search requirement.
4. **MySQL + external search engine (Meilisearch, Elasticsearch).** Good search, extra service to deploy and monitor.

## Decision
We choose **option 1**. `to_tsvector('french', ...)` with a GIN index (and `unaccent`) meets the French search need inside the database, with no additional service. Eloquent's `whereFullText` supports PostgreSQL with a language option, and we can drop to raw expressions or generated `tsvector` columns when needed.

## Consequences
- **Positive:** one database for relational data, search, and queue (see ADR-0006); rich types (JSONB, arrays, enums) and constraints to justify the data model.
- **Negative / trade-offs:** `tsvector` columns/indexes require raw SQL in migrations; search relevance tuning is manual; slightly less familiar than MySQL for some members.
- **Impact:** official `postgres` image pinned to a major version in `compose.yml` with a named volume and a healthcheck; same engine in dev, CI and production; backups to plan for the deployment phase.
