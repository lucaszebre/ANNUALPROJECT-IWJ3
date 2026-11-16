# ADR-0007: File storage: MinIO through Laravel's `s3` disk

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
User-generated files and videos must survive container restarts and redeployments, since all services are containerized and disposable. Local disk storage inside a container does not meet that need and does not scale to several instances.

Videos are transcoded to HLS and streamed to the browser. An HLS playlist (`.m3u8`) references its segments by **relative path**, so a single signed URL for the playlist is useless: each segment would need its own signature. We must still keep course videos private.

## Options considered

### A. Storage backend
1. **MinIO (S3-compatible) through the `s3` filesystem disk.** Self-hosted object storage, same code as a cloud S3.
2. **Laravel `local`/`public` disk on a Docker volume.** Simplest, but ties files to one host and one container layout.
3. **Managed cloud object storage (AWS S3 or equivalent).** No maintenance, but a recurring cost and an external account.

### B. Serving HLS videos
1. **Serve HLS through the application.** A Laravel route authorizes each request and streams the playlist and segments from MinIO.
2. **Rewrite the playlists with signed URLs.** The app serves the authorized playlists and replaces every segment/key/map URI with a pre-signed MinIO URL. Segments are downloaded directly from MinIO.
3. **Make the HLS files public.** No signature needed, but no access control.

## Decision

**A. We choose MinIO through the `s3` disk.** The application only talks to the `s3` disk (Flysystem), so code is identical in dev and production and portable to any S3-compatible service. MinIO runs as its own container with a persistent volume, and buckets are private.

**B. We choose to rewrite the playlists with signed URLs.** Segments make up almost all the bytes: option 1 would hold a PHP-FPM worker for each segment download, so a few slow clients could starve the whole application. Option 3 removes access control on course content (acceptable only for content that is public anyway).
- Master and variant playlists are served by an authorized application route (policy: the user may watch this video). Their URIs (variant playlists, `#EXT-X-MAP`, `#EXT-X-KEY` if used) are rewritten, and segment URIs become pre-signed URLs with a TTL longer than the video plus pauses (for example 6 hours, to be tuned).
- Signed URLs include the host: they must be generated for the **public** MinIO URL the browser will call (`localhost:9000` in dev, an `s3.<domain>` host behind Caddy in production), not the internal `minio:9000`. This needs a dedicated signing disk/endpoint configuration.
- The bucket needs CORS rules for the application's origin, because the player fetches segments cross-origin.
- Documents, thumbnails and other non-HLS files use plain temporary URLs.

## Consequences
- **Positive:** same code in dev and production, easy migration to a managed S3 later, access control kept on videos, video bytes never go through PHP.
- **Negative / trade-offs:**
    - One more stateful service to back up and secure.
    - It needs `league/flysystem-aws-s3-v3` and the path-style endpoint (`AWS_USE_PATH_STYLE_ENDPOINT=true`).
    - Playlist rewriting, a public MinIO endpoint and CORS add moving parts.
    - URLs expire, so a player resuming after a very long pause must reload the playlist.
    - Playlist requests still hit PHP (they are small).
- **To verify before freezing:**
    - Presigned URL host handling with MinIO behind Caddy, and CORS with the chosen player (a short spike).
    - The current maintenance status of the MinIO community edition and the availability of its official images (MinIO's distribution and licensing have changed recently). If it is a problem, an S3-compatible alternative (e.g. Garage, SeaweedFS) can be substituted with no application code change.
    - If the spike fails, serving HLS through the application (option B1) is the fallback for the demo, recorded in a superseding ADR.
- **Impact:** credentials come from environment secrets (never committed), and bucket creation is part of the provisioning/init step.