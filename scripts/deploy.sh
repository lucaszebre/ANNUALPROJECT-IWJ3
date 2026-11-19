#!/bin/sh
# Deploys a branch on the VPS: called by .github/workflows/deploy.yml over SSH.
# The CI key may only run this script (forced command in ~/.ssh/authorized_keys), and the
# branch it asks for (SSH_ORIGINAL_COMMAND) must be main or develop.
# By hand, on the server: ./scripts/deploy.sh [branch]   (default: the branch checked out)
#
# Everything is inside main() so the shell reads the whole file before `git reset` replaces it.
set -eu

main() {
    cd "$(dirname "$0")/.."

    branch="${SSH_ORIGINAL_COMMAND:-${1:-$(git rev-parse --abbrev-ref HEAD)}}"
    if [ -n "${SSH_ORIGINAL_COMMAND:-}" ]; then
        case "$branch" in
            main | develop) ;;
            *) echo "Refusing to deploy '$branch': only main or develop." >&2; exit 1 ;;
        esac
    fi

    echo "==> Deploying $branch"
    git fetch --quiet origin "$branch"
    git checkout --quiet -B "$branch" "origin/$branch" # .env is ignored by git, so it is kept

    docker compose -f compose.prod.yml up -d --build --remove-orphans
    docker image prune -f >/dev/null

    echo "==> Deployed $(git log --oneline -1)"
}

main "$@"
