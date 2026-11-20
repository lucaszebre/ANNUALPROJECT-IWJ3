# syntax=docker/dockerfile:1

# The official Garage image only contains the static `garage` binary (no shell), so it cannot
# run an init script. We take the binary from it (version pinned here) and add a shell.
ARG GARAGE_VERSION=v2.5.0

FROM dxflrs/garage:${GARAGE_VERSION} AS garage

FROM alpine:3.22
COPY --from=garage /garage /usr/local/bin/garage
COPY --chmod=755 entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
