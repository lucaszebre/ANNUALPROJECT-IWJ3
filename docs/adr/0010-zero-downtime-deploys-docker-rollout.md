# ADR-0010: Zero-downtime deploys with docker rollout (not Swarm or Kubernetes)

- **Status:** Accepted
- **Date:** 2026-11-19
- **Deciders:** Lucas, Sorya and Yohan

## Context
The platform runs on a single VPS (Hetzner CAX21, 4 ARM vCPU, 8 GB) with `compose.prod.yml` (#26). Every merge into `develop` is deployed by GitHub Actions over SSH (`scripts/deploy.sh`). With a plain `docker compose up -d --build`, the new images are built while the old containers serve, but the `app` and `web` containers are then stopped and recreated: the site is unreachable for about 5–15 seconds per deploy, and a request in flight at that moment fails.
We want deploys that users do not notice, so that we can merge several times a day, including during demos, without growing the infrastructure beyond one server and one compose file.

## Options considered
1. **Keep `docker compose up -d`.** Simplest, but a short outage on every deploy.
2. **docker rollout** (a Docker CLI plugin, one bash script). It scales the `app` service to two containers, waits until the new one passes its healthcheck, then stops the old one, and rolls back if the new one never becomes healthy. It works on the existing compose file.
3. **Docker Swarm** (`docker stack deploy`, `update_config: order: start-first`). Native rolling updates, but Swarm cannot build images: they must be built in CI for ARM and pushed to a registry (GHCR). `depends_on` is ignored, so the migration step must be redesigned, and Caddy behind the ingress mesh loses the client IP unless its ports use `mode: host`.
4. **Kubernetes (k3s).** Rolling updates, probes and rollbacks built in, but a control plane, manifests and an ingress controller to run and learn, for a single node.

## Decision
We choose **option 2**, docker rollout, pinned to v0.14 and checked by SHA-256 in `scripts/deploy.sh`. A deploy:
1. builds the images while the current version keeps serving;
2. runs the migrations (`migrate`), then copies the new static files (`public/`) into the `public-assets` volume (`assets`);
3. rolls out `app` with `docker rollout`;
4. replaces the queue workers and the scheduler, and reloads Caddy (`caddy reload`) without restarting it.

Three details make the switch invisible:
- **Caddy never restarts.** It runs the stock image and serves `public/` from a volume instead of baking it into a `web` image (which changes on every frontend change). Vite gives assets hashed names, so pages of the old version still find their files during the switch.
- **Caddy retries.** `php_fastcgi app:9000 { lb_try_duration 5s }`: while the old container stops, a failed connection is retried, and Docker DNS then only returns the new container.
- **The Caddyfile directory is mounted, not the file**, so a `git checkout` is seen by `caddy reload`.

Measured on the VPS: 44 requests sent every 0.2 s during a deploy, 44 answered 200.

Options 3 and 4 solve the same problem with a registry, a CI image build for ARM and a redesigned migration step. At our scale (one server, three developers, ten days to the MVP), that is more to run and explain than it is worth.

## Consequences
- **Positive:** no visible outage on deploy, automatic rollback if the new `app` never becomes healthy, no new service or registry, and the same `compose.prod.yml` for the first install and for deploys.
- **Migrations must be backward compatible:** they run while the old version still serves. A breaking change (renaming or dropping a column) is split over two deploys (expand, then contract).
- **Queue workers:** on deploy they get SIGTERM and finish their current job within `stop_grace_period` (60 s for `default`, 5 min for transcoding and transcription). A longer job is killed and only retried after `retry_after` (2 h, ADR-0006). This is acceptable for now; we avoid deploying while a long video is being processed.
- **Caddy is still a single container:** changing its image or ports (not its Caddyfile) restarts it, with a one-second outage.
- **This updates ADR-0008:** the `web` image with `public/` baked in is no longer used in production; Caddy reads `public/` from the `public-assets` volume.
- **Migration path:** if the platform outgrows one server, option 3 is the next step: images built in CI and pushed to GHCR, then `docker stack deploy` on several nodes.
