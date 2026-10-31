# ADR-0009: Quality tooling: Pest, Laravel Pint, Larastan

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline et Yohan

## Context
With several contributors and a Git-flow based workflow, we need automated checks on every pull request: tests, consistent code style, and static analysis. Security and code quality are part of the grading.

## Options considered
**Tests:** (1) Pest, (2) PHPUnit.
**Code style:** (1) Laravel Pint, (2) PHP-CS-Fixer configured by hand.
**Static analysis:** (1) Larastan (PHPStan for Laravel), (2) Psalm, (3) none.

## Decision
- **Pest** for tests: it runs on top of PHPUnit (so nothing is lost), has a concise syntax and first-class Laravel and Livewire testing support.
- **Laravel Pint** for style: ships with Laravel, zero-config with the Laravel preset, based on PHP-CS-Fixer.
- **Larastan** for static analysis: understands Eloquent magic (models, relations, facades), starting at level 5 and raised progressively.

All three run in the CI pipeline and block merging on failure; Pint also runs locally (pre-commit or editor on save).

## Consequences
- **Positive:** early bug detection, uniform style without review debates, executable documentation of behavior through tests.
- **Negative / trade-offs:** Larastan can be noisy at first (baseline file may be needed); Pest syntax is another thing to learn; CI time increases.
- **Impact:** `composer` scripts (`test`, `lint`, `analyse`) shared by local dev and CI; test database is PostgreSQL (same engine as production, see ADR-0005), not SQLite.
