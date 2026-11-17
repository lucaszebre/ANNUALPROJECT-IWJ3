# ADR-0008: Docker runtime: Caddy + PHP-FPM (drop nginx)

- **Status:** Accepted
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The syllabus requires every service to be containerized (Dockerfiles + `compose.yml`), and the application must be served over HTTPS on a public domain. The README already places Caddy in front of the application for TLS. We need a PHP runtime that is stable, documented and compatible with Livewire. Users will upload and stream video, so the web server must handle large bodies and static files efficiently.

## Options considered
1. **Caddy + PHP-FPM.** Caddy terminates TLS (automatic Let's Encrypt), serves static files and talks FastCGI to PHP-FPM through `php_fastcgi`.
2. **Caddy → nginx → PHP-FPM.** Caddy only does TLS, nginx does the PHP routing. Two web servers in a row, for no function that Caddy cannot do alone.
3. **FrankenPHP** (Caddy + PHP in one process, classic or Octane worker mode). Fewer containers, but worker mode shares state between requests (a risk with Livewire) and it is less documented for our use.
4. **nginx + PHP-FPM, with TLS handled elsewhere.** Contradicts the README (Caddy in front).

## Decision
We choose **option 1**: two containers, `web` (Caddy) and `app` (PHP-FPM), and nginx is dropped. `php_fastcgi` already implements the Laravel front-controller routing (`try_files` → `index.php`), Caddy gives us HTTPS with no extra tooling (domain + valid certificate are graded), and one fewer component means one fewer thing to configure, patch and explain.
PHP-FPM keeps request isolation (no shared state between requests), which is why we do not pick FrankenPHP worker mode.

## Consequences
- **Positive:** a simpler chain (browser → Caddy → PHP-FPM), automatic certificate issuance and renewal, a short Caddyfile.
- **Negative / trade-offs:** `php_fastcgi` checks that files exist under its `root`, so the `web` image must contain `public/` (built assets + `index.php`), not just proxy blindly; in development `./public` is bind-mounted. Caddy data (certificates) needs a persistent volume (`caddy_data`).
- **Upload limits:** Caddy sets no request body limit by default; we set one explicitly (`request_body { max_size ... }`), together with PHP's `upload_max_filesize` / `post_max_size`. Large video uploads may bypass PHP entirely (direct-to-storage uploads (ADR-0007)): to be decided in the upload feature, not here.
- **Impact:** the issue #9 compose/Dockerfile uses a `caddy` web service instead of nginx. Same PHP-FPM image for `app`, queue workers and scheduler.
