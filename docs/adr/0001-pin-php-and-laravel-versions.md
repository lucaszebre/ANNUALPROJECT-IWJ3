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
We choose **option 1**: the latest stable Laravel major available at project start (`13.38.0`) and PHP `8.5.12`, the most recent version supported by Laravel **and** by all our required packages (Livewire, Larastan, Pest). Versions are checked against the official support table (laravel.com/docs/releases) before freezing.

Pinning is enforced in every layer:
- `composer.json`: `"require"` and `"config": { "platform": { "php": "8.5.12" } }`; `composer.lock` is committed.
- Docker: image tag pinned to the minor version, never `latest`.
- Node (for Vite): LTS version pinned in `.nvmrc` and in the build stage of the Dockerfile.
- CI uses the same versions as the Dockerfile.

## Consequences
- **Positive:** reproducible builds, identical dev/CI/prod behaviour, up-to-date documentation.
- **Negative / trade-offs:** some packages may lag behind a brand-new PHP release.
- **Impact:** only patch/minor updates during the project (via Dependabot/Renovate PRs); a major upgrade requires a new ADR.
