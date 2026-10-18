#!/usr/bin/env bash
#
# deploy.sh — sync a Trongate app to the server over SSH via rsync.
#
# TRONGATE_DEPLOY_V2
# Lives in the project root, next to .deploy.env. Both are excluded from sync.
# .deploy.env is a trusted shell file; never source an untrusted configuration.
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

MODE="dry"
if [[ "$#" -eq 1 && "$1" == "--live" ]]; then
  MODE="live"
elif [[ "$#" -ne 0 ]]; then
  echo "✗  Use no arguments for a dry run, or --live to deploy." >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "✗  $ENV_FILE not found. Create it with DEPLOY_USER, DEPLOY_HOST, DEPLOY_PORT and DEPLOY_REMOTE_PATH." >&2
  exit 1
fi

# shellcheck source=/dev/null
source "$ENV_FILE"

DEPLOY_USER="${DEPLOY_USER:-}"
DEPLOY_HOST="${DEPLOY_HOST:-}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_REMOTE_PATH="${DEPLOY_REMOTE_PATH:-}"

# ─── Guards: refuse to run on missing or placeholder settings ────────────────
if [[ -n "${DEPLOY_SOURCE:-}" || -n "${DEPLOY_PATH:-}" ]]; then
  echo "✗  Legacy deployment settings detected. Move this helper into the project root, remove DEPLOY_SOURCE and rename DEPLOY_PATH to DEPLOY_REMOTE_PATH after reviewing the destination." >&2
  exit 1
fi
if [[ -z "$DEPLOY_USER" || -z "$DEPLOY_HOST" || -z "$DEPLOY_REMOTE_PATH" \
   || "$DEPLOY_USER" == "YOUR_SSH_USER" || "$DEPLOY_HOST" == "YOUR_SERVER_HOST" \
   || "$DEPLOY_REMOTE_PATH" == *YOUR_SSH_USER* ]]; then
  echo "✗  Fill in DEPLOY_USER, DEPLOY_HOST and DEPLOY_REMOTE_PATH in .deploy.env first." >&2
  exit 1
fi

# Restrict values passed through SSH/rsync's remote shell; refuse broad roots.
if [[ ! "$DEPLOY_USER" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$ \
   || ! "$DEPLOY_HOST" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ \
   || ! "$DEPLOY_PORT" =~ ^[0-9]{1,5}$ \
   || ! "$DEPLOY_REMOTE_PATH" =~ ^/[a-zA-Z0-9_./-]+$ ]]; then
  echo "✗  Invalid deployment user, host, port or absolute remote path." >&2
  exit 1
fi
if (( 10#$DEPLOY_PORT < 1 || 10#$DEPLOY_PORT > 65535 )) \
   || [[ "$DEPLOY_REMOTE_PATH" != *[a-zA-Z0-9_-]* \
      || "$DEPLOY_REMOTE_PATH/" == *'/../'* || "$DEPLOY_REMOTE_PATH/" == *'/./'* ]]; then
  echo "✗  Unsafe deployment port or remote path." >&2
  exit 1
fi

LOCAL_PATH="$HERE/"
[[ "$DEPLOY_REMOTE_PATH" == */ ]] || DEPLOY_REMOTE_PATH="$DEPLOY_REMOTE_PATH/"

# --delete makes a wrong source folder destructive on the server, so prove
# it really is a Trongate project before syncing anything.
if [[ ! -d "$LOCAL_PATH/engine" || ! -d "$LOCAL_PATH/modules" ]]; then
  echo "✗  $LOCAL_PATH is not a Trongate project (no engine/ and modules/)." >&2
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
  --exclude '.codex/'
  --exclude '.agents/'
  --exclude '/.trongate-init-backup/'
  --exclude '/.trongate-init-state*'
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

RSYNC_FLAGS=(-avz --delete --human-readable)
[[ "$MODE" == "dry" ]] && RSYNC_FLAGS+=(--dry-run)

echo "──────────────────────────────────────────────"
echo "  From : $LOCAL_PATH"
echo "  To   : ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_REMOTE_PATH}"
echo "  Port : $DEPLOY_PORT"
echo "  Mode : $([[ "$MODE" == "dry" ]] && echo 'DRY RUN (no changes)' || echo 'LIVE DEPLOY')"
echo "──────────────────────────────────────────────"

# Confirm before a real, destructive (--delete) sync.
if [[ "$MODE" == "live" ]]; then
  read -r -p "Proceed with LIVE deploy? [y/N] " reply || reply=''
  [[ "$reply" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }
fi

rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" \
  -e "ssh -p ${DEPLOY_PORT}" \
  "$LOCAL_PATH" "${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_REMOTE_PATH}"

echo
if [[ "$MODE" == "dry" ]]; then
  echo "✓  Dry run complete. Nothing was transferred."
  echo "   Re-run with:  ./deploy.sh --live"
else
  echo "✓  Deploy complete."
fi
