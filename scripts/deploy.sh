#!/bin/sh
# Deploys a branch on the VPS with no downtime: called by .github/workflows/deploy.yml over SSH.
# The CI key may only run `deploy.sh --ci` (forced command in ~/.ssh/authorized_keys); the
# branch it asks for (SSH_ORIGINAL_COMMAND) is required and must be main or develop.
# By hand, on the server: ./scripts/deploy.sh [branch]   (default: the branch checked out)
#
# Everything is inside main() so the shell reads the whole file before `git checkout` replaces it.
set -eu

# docker rollout (https://github.com/wowu/docker-rollout): starts the new app container next to the
# old one, switches once it is healthy, then stops the old one. Pinned and checked by hash.
ROLLOUT_VERSION=v0.14
ROLLOUT_SHA256=cdeaba6ae9eee3b0b606286e585bbda6787283d801a6ad6d9b9d2bc347fda05b

install_rollout() {
    plugin="$HOME/.docker/cli-plugins/docker-rollout"
    if [ -x "$plugin" ] && echo "$ROLLOUT_SHA256  $plugin" | sha256sum -c --status; then
        return
    fi
    echo "==> Installing docker rollout $ROLLOUT_VERSION"
    mkdir -p "$(dirname "$plugin")"
    curl -fsSL "https://raw.githubusercontent.com/wowu/docker-rollout/$ROLLOUT_VERSION/docker-rollout" -o "$plugin.tmp"
    echo "$ROLLOUT_SHA256  $plugin.tmp" | sha256sum -c --status
    chmod +x "$plugin.tmp"
    mv "$plugin.tmp" "$plugin"
}

main() {
    cd "$(dirname "$0")/.."

    if [ "${1:-}" = "--ci" ]; then
        branch="${SSH_ORIGINAL_COMMAND:-}"
        case "$branch" in
            main | develop) ;;
            *) echo "Refusing to deploy '$branch': only main or develop." >&2; exit 1 ;;
        esac
    else
        branch="${1:-$(git rev-parse --abbrev-ref HEAD)}"
    fi

    install_rollout
    compose="docker compose -f compose.prod.yml"

    echo "==> Deploying $branch"
    git fetch --quiet origin "$branch"
    git checkout --quiet -B "$branch" "origin/$branch" # .env is ignored by git, so it is kept

    # 1. Build while the current version keeps serving.
    $compose build

    # 2. Migrations run before the new code: they must stay compatible with the running version.
    $compose up -d --wait postgres garage
    $compose run --rm migrate
    $compose run --rm --no-deps assets

    # 3. The web app, with no downtime.
    docker rollout -f compose.prod.yml app

    # 4. Workers finish their current job, then are replaced. Forced: compose does not always see
    #    that the app-prod tag now points to a new image, and would leave them on the old code.
    $compose up -d --no-deps --force-recreate queue queue-transcoding queue-transcription scheduler

    # 5. Caddy is only started if missing, then reloaded: never restarted.
    $compose up -d --no-deps --remove-orphans web
    $compose exec -T web caddy reload --config /etc/caddy/Caddyfile.prod --adapter caddyfile

    docker image prune -f >/dev/null
    echo "==> Deployed $(git log --oneline -1)"
}

main "$@"
