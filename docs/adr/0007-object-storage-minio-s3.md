# ADR-0007: File storage: MinIO through Laravel's `s3` disk

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
User-generated files (uploads, images, documents) must survive container restarts and redeployments, since all services are containerized and disposable. Local disk storage inside a container does not meet that need and does not scale to several instances.

## Options considered
1. **MinIO (S3-compatible) through the `s3` filesystem disk.** Self-hosted object storage, same code as a cloud S3.
2. **Laravel `local`/`public` disk on a Docker volume.** Simplest, but ties files to one host and one container layout.
3. **Managed cloud object storage (AWS S3 or equivalent).** No maintenance, recurring cost and external account.

## Decision
We choose **option 1**. The application only talks to the `s3` disk (Flysystem), so code is identical in dev and production and portable to any S3-compatible service. MinIO runs as its own container with a persistent volume; buckets are private by default and files are served through temporary signed URLs.

## Consequences
- **Positive:** realistic production-like storage locally, demonstrates service decomposition, easy migration to a managed S3 later.
- **Negative / trade-offs:** one more stateful service to back up and secure; needs `league/flysystem-aws-s3-v3`, path-style endpoint (`AWS_USE_PATH_STYLE_ENDPOINT=true`) and distinct internal vs public endpoint URLs for signed links.
- **Impact:** credentials come from environment secrets (never committed); bucket creation is part of the provisioning/init step.
