# ADR-0005: Database: PostgreSQL

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The syllabus requires an SQL data model that we will defend during the oral. Our use case needs French-language full-text search (stemming, stop words, accent-insensitive matching), in particular over video transcripts produced by Whisper (#31), where a search result must jump to the right timecode (#32).

In PostgreSQL, `unaccent()` is not `IMMUTABLE`, so it cannot be used directly in an index expression or in a generated column. We have to decide how to get accent-insensitive search that can still be indexed.

## Options considered

### A. Database engine
1. **PostgreSQL.** Native full-text search with a `french` configuration, `unaccent`, GIN indexes.
2. **MySQL/MariaDB.** Ubiquitous with Laravel, but FULLTEXT has no real French stemming and limited configurability.
3. **SQLite.** Trivial setup, unsuitable for production concurrency and for the search requirement.
4. **MySQL + external search engine (Meilisearch, Elasticsearch).** Good search, but an extra service to deploy and monitor.

### B. Accent-insensitive search on PostgreSQL
1. **Wrapper function** `immutable_unaccent(text)` declared `IMMUTABLE` and used everywhere: `to_tsvector('french', immutable_unaccent(col))`. It works, but the immutability is declared by hand, and every query must repeat the exact same expression to use the index.
2. **Custom text search configuration** `french_unaccent` (the `french` configuration plus the `unaccent` dictionary before the French stemmer). `to_tsvector(config, text)` with an explicit configuration is immutable, so it can be used in a GIN index and in a generated column, and queries only name the configuration.
3. **No unaccent** (use the plain `french` configuration). "élève" and "eleve" would not match.

## Decision

**A. We choose PostgreSQL.** `to_tsvector('french', ...)` with a GIN index meets the French search need inside the database, with no additional service. Eloquent's `whereFullText` supports PostgreSQL with a language option, and we can drop to raw expressions or generated `tsvector` columns when needed.

**B. We choose the custom text search configuration `french_unaccent`**, created in a migration with raw SQL:

```sql
CREATE EXTENSION IF NOT EXISTS unaccent;

CREATE TEXT SEARCH CONFIGURATION french_unaccent (COPY = french);
ALTER TEXT SEARCH CONFIGURATION french_unaccent
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, french_stem;
```

Usage, for example on transcript segments with start/end timecodes:

```sql
ALTER TABLE transcript_segments
    ADD COLUMN search_vector tsvector
    GENERATED ALWAYS AS (to_tsvector('french_unaccent', coalesce(text, ''))) STORED;

CREATE INDEX transcript_segments_search_idx
    ON transcript_segments USING GIN (search_vector);

-- accent- and stem-insensitive query
SELECT id, start_seconds
FROM transcript_segments
WHERE search_vector @@ websearch_to_tsquery('french_unaccent', :q);
```

## Consequences
- **Positive:**
    - One database for relational data, search and queue (see ADR-0006).
    - Rich types (JSONB, arrays, enums) and constraints help us justify the data model.
    - One definition of "French without accents", reused by indexes and queries, covers the transcript search (#31, #32) and any other searchable table.
- **Negative / trade-offs:**
    - `tsvector` columns, indexes and the text search configuration require raw SQL in migrations (the migration needs a `down()` that drops the configuration).
    - Changing the unaccent rules later requires re-indexing.
    - Eloquent's `whereFullText` builds its own `to_tsvector(...)` expression and will not use the generated-column index, so searches use `whereRaw` against `search_vector`.
    - Search relevance tuning is manual, and PostgreSQL is slightly less familiar than MySQL for some members.
- **Impact:**
    - The official `postgres` image is pinned to a major version in `compose.yml`, with a named volume and a healthcheck.
    - The same engine runs in dev, CI and production, and the test database runs the same migrations, so tests exercise the real configuration.
    - Backups must be planned for the deployment phase.
    - Accent-insensitive `LIKE` or autocomplete, if needed later, would use an `immutable_unaccent()` wrapper in its own ADR.