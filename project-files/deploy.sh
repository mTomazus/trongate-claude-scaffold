#!/usr/bin/env bash
#
# deploy.sh — sync a Trongate app to the server over SSH via rsync.
#
# Lives one level ABOVE the project, next to .deploy.env, so neither file is
# ever inside the folder that gets synced. All settings come from .deploy.env.
#
# Usage:
#   ./deploy.sh              Preview only (dry run — shows what WOULD change, transfers nothing)
#   ./deploy.sh --live       Actually deploy (asks for confirmation first)
#
# First-time setup on the server (do ONCE, by hand — this script never touches config/):
#   1. Create the MySQL database + user in hPanel, import your .sql via phpMyAdmin.
#   2. Upload a production config/ folder with the real BASE_URL, ENV='production',
#      and the server's DB credentials. After that it's left alone on every deploy.
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$HERE/.deploy.env"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "✗  $ENV_FILE not found. Create it with DEPLOY_SOURCE, DEPLOY_USER, DEPLOY_HOST, DEPLOY_PORT and DEPLOY_PATH." >&2
  exit 1
fi

# shellcheck source=/dev/null
source "$ENV_FILE"

DEPLOY_SOURCE="${DEPLOY_SOURCE:-}"
DEPLOY_USER="${DEPLOY_USER:-}"
DEPLOY_HOST="${DEPLOY_HOST:-}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_PATH="${DEPLOY_PATH:-}"

# ─── Guards: refuse to run on missing or placeholder settings ────────────────
if [[ -z "$DEPLOY_SOURCE" ]]; then
  echo "✗  DEPLOY_SOURCE is not set in .deploy.env — set it to the project folder name, e.g. DEPLOY_SOURCE=my_site" >&2
  exit 1
fi
if [[ -z "$DEPLOY_USER" || -z "$DEPLOY_HOST" || -z "$DEPLOY_PATH" \
   || "$DEPLOY_USER" == "YOUR_SSH_USER" || "$DEPLOY_HOST" == "YOUR_SERVER_HOST" \
   || "$DEPLOY_PATH" == *YOUR_SSH_USER* ]]; then
  echo "✗  Fill in DEPLOY_USER, DEPLOY_HOST and DEPLOY_PATH in .deploy.env first." >&2
  exit 1
fi

# Trailing slash means "sync the CONTENTS of the project into DEPLOY_PATH".
LOCAL_PATH="$HERE/$DEPLOY_SOURCE/"
[[ "$DEPLOY_PATH" == */ ]] || DEPLOY_PATH="$DEPLOY_PATH/"

# --delete makes a wrong source folder destructive on the server, so prove
# it really is a Trongate project before syncing anything.
if [[ ! -d "$LOCAL_PATH/engine" || ! -d "$LOCAL_PATH/modules" ]]; then
  echo "✗  $LOCAL_PATH is not a Trongate project (no engine/ and modules/). Check DEPLOY_SOURCE in .deploy.env." >&2
  exit 1
fi

# Files/folders that must never go to the server. A leading slash anchors the
# pattern to the project root, so a module's own tests/ or tools/ still ships.
EXCLUDES=(
  --exclude 'config/'          # environment-specific — set once on the server, never overwrite
  --exclude '.git/'
  --exclude 'node_modules/'    # local tooling only, never deployed
  --exclude '_reference/'      # docs + specs, not needed live
  --exclude 'logs/'
  --exclude '.claude/'
  --exclude 'scripts/'
  --exclude '.DS_Store'
  --exclude 'deploy.sh'        # don't ship the deploy script itself
  --exclude '.deploy.env'
  --exclude 'CLAUDE.md'
  --exclude 'AGENTS.md'
  --exclude '.hintrc'
  --exclude '/tests/'          # HTTP test suite — dev only
  --exclude '/tools/'          # phpstan.phar and its wrappers — dev only
  --exclude '/phpstan.neon'
  --exclude '/phpstan-baseline.neon'
  --exclude '/.gitignore'
)

# ─── Mode: dry run (default) vs live ─────────────────────────────────────────
MODE="dry"
if [[ "${1:-}" == "--live" ]]; then
  MODE="live"
elif [[ -n "${1:-}" ]]; then
  echo "✗  Unknown argument '$1'. Use no arguments for a dry run, or --live to deploy." >&2
  exit 1
fi

RSYNC_FLAGS=(-avz --delete --human-readable)
[[ "$MODE" == "dry" ]] && RSYNC_FLAGS+=(--dry-run)

echo "──────────────────────────────────────────────"
echo "  From : $LOCAL_PATH"
echo "  To   : ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"
echo "  Port : $DEPLOY_PORT"
echo "  Mode : $([[ "$MODE" == "dry" ]] && echo 'DRY RUN (no changes)' || echo 'LIVE DEPLOY')"
echo "──────────────────────────────────────────────"

# Confirm before a real, destructive (--delete) sync.
if [[ "$MODE" == "live" ]]; then
  read -r -p "Proceed with LIVE deploy? [y/N] " reply
  [[ "$reply" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }
fi

rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" \
  -e "ssh -p ${DEPLOY_PORT}" \
  "$LOCAL_PATH" "${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

echo
if [[ "$MODE" == "dry" ]]; then
  echo "✓  Dry run complete. Nothing was transferred."
  echo "   Re-run with:  ./deploy.sh --live"
else
  echo "✓  Deploy complete."
fi
