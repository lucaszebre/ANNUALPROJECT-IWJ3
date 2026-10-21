# ADR-0008: Docker runtime: nginx + PHP-FPM

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The syllabus requires every service to be containerized, with `Dockerfile`s and a `compose.yml` for development and production, and the app must be served over HTTPS behind a public domain. We need a PHP runtime that is stable, well documented, and compatible with Livewire.

## Options considered
1. **nginx + PHP-FPM** (two containers, or one image with both).
2. **FrankenPHP** (Caddy-based, single container, classic mode or Octane worker mode, automatic HTTPS).
3. **Apache + mod_php** (single container).

## Decision
We choose **option 1**, with two separate containers (`web` = nginx, `app` = php-fpm) built from a multi-stage Dockerfile sharing the same application code. It is the most documented and conservative setup for Laravel, request-isolated by design (no shared state between requests, unlike worker mode), and the split follows the "one service per container" idea expected by the syllabus. TLS termination is handled by a reverse proxy in front (e.g. Traefik or Caddy with Let's Encrypt).

## Consequences
- **Positive:** predictable behavior, abundant documentation, nginx and FPM tunable independently, no memory-leak or state-sharing concerns with Livewire.
- **Negative / trade-offs:** more configuration (nginx vhost, FPM pool, shared volume or copied assets) than FrankenPHP; lower raw performance than worker mode.
- **Impact:** production images are immutable (no bind mounts, `composer install --no-dev --optimize-autoloader`, cached config/routes/views); `queue` and `scheduler` containers reuse the `app` image.
