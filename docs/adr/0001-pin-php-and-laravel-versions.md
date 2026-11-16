# ADR-0001: Pin the PHP and Laravel versions

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The whole stack (Livewire, Pest, Larastan, Docker images, CI) depends on the PHP and Laravel versions. Letting them float between team members' machines, CI and production causes "works on my machine" bugs. The project runs until December 2026, so we want a stable base with no major upgrade mid-project.

## Options considered
1. **Latest stable Laravel major + latest PHP version it supports.** Longest support window, up-to-date docs.
2. **Previous Laravel major (more battle-tested).** Fewer surprises with third-party packages, but shorter remaining support.

## Decision
We choose **option 1**: the latest stable Laravel major available at project start (13.x) and PHP 8.5, the most recent version supported by Laravel **and** by all our required packages (Livewire, Larastan, Pest). Versions were checked against the official support table (laravel.com/docs/releases) before freezing.

The ADR records the policy, not patch numbers: the exact installed Laravel version is whatever `composer.lock` says (13.39.0 when this ADR was written).

Pinning is enforced in every layer:
- **Laravel:** `composer.json` allows `^13.17`; `composer.lock` is committed.
- **PHP:** `composer.json` requires `~8.5.0` (any 8.5.x), and `config.platform.php` is set to `8.5.12`, the lowest patch we guarantee, so Composer resolves the same dependencies everywhere.
- **Docker:** the PHP image is pinned to the minor version (`php:8.5-fpm-alpine`), never `latest`; patch updates arrive when the image is rebuilt.
- **Node (for Vite):** the LTS major is written in `.nvmrc` and reused in the build stage of the Dockerfile and in CI.
- All version changes go in one commit that updates `composer.json`, `.nvmrc`, the Dockerfile and CI together.

## Consequences
- **Positive:** reproducible builds, identical dev/CI/prod behaviour, up-to-date documentation.
- **Negative / trade-offs:** some packages may lag behind a brand-new PHP release.
- **Impact:** only patch/minor updates during the project (via Dependabot/Renovate PRs); a major upgrade requires a new ADR.
