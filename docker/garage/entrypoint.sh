#!/bin/sh
# Starts the Garage server and creates, if they do not exist yet, the cluster layout, the bucket
# and the access key, so that `docker compose up` gives a working S3 endpoint with no manual step.
# The container is reported healthy (see compose.yml) once this has finished.
set -eu

CONFIG="${GARAGE_CONFIG:-/etc/garage.toml}"
READY_FILE="${GARAGE_READY_FILE:-/tmp/garage-ready}"
ZONE="${GARAGE_ZONE:-dc1}"
CAPACITY="${GARAGE_CAPACITY:-1G}"
KEY_NAME="app-key"

# --- Fail early on the mistakes that are easy to make ---------------------------------------
if ! printf '%s' "$AWS_ACCESS_KEY_ID" | grep -Eq '^GK[0-9a-f]{24}$'; then
    echo "ERROR: AWS_ACCESS_KEY_ID must be 'GK' followed by 24 hex characters (Garage key ID format)." >&2
    exit 1
fi
if ! printf '%s' "$AWS_SECRET_ACCESS_KEY" | grep -Eq '^[0-9a-f]{64}$'; then
    echo "ERROR: AWS_SECRET_ACCESS_KEY must be 64 hex characters (Garage secret key format)." >&2
    exit 1
fi
TOML_REGION="$(sed -n 's/^s3_region *= *"\(.*\)".*/\1/p' "$CONFIG")"
if [ "$TOML_REGION" != "$AWS_DEFAULT_REGION" ]; then
    echo "ERROR: AWS_DEFAULT_REGION ('$AWS_DEFAULT_REGION') must equal s3_region ('$TOML_REGION') in garage.toml." >&2
    exit 1
fi

# --- Start the server ---------------------------------------------------------------------------
rm -f "$READY_FILE"
garage server &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true' TERM INT

tries=0
until garage status >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -gt 60 ]; then
        echo "ERROR: Garage did not answer after 60 seconds." >&2
        exit 1
    fi
    sleep 1
done

# --- Cluster layout (single node) ---------------------------------------------------------------
NODE_ID="$(garage node id -q | cut -d@ -f1)"
ASSIGN_OUT="$(garage layout assign -z "$ZONE" -c "$CAPACITY" "$NODE_ID" 2>&1)" || true
echo "$ASSIGN_OUT"
VERSION="$(printf '%s\n' "$ASSIGN_OUT" | sed -n 's/.*layout apply --version \([0-9][0-9]*\).*/\1/p' | tail -n 1)"
# The first layout is version 1. If a layout already exists the next call fails, which is fine.
garage layout apply --version "${VERSION:-1}" || echo "Layout already applied."

# --- Bucket and access key (created only once) -----------------------------------------------
garage bucket info "$AWS_BUCKET" >/dev/null 2>&1 || garage bucket create "$AWS_BUCKET"
garage key info "$AWS_ACCESS_KEY_ID" >/dev/null 2>&1 \
    || garage key import --yes -n "$KEY_NAME" "$AWS_ACCESS_KEY_ID" "$AWS_SECRET_ACCESS_KEY"
garage bucket allow --read --write --owner "$AWS_BUCKET" --key "$AWS_ACCESS_KEY_ID"

touch "$READY_FILE"
echo "Garage ready: bucket '$AWS_BUCKET', key '$AWS_ACCESS_KEY_ID', region '$AWS_DEFAULT_REGION'."

wait "$SERVER_PID"
