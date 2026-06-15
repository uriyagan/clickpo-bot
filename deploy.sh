#!/usr/bin/env bash
# Deploy the ClickPo AI Chatbot plugin to the WordPress site over SSH/rsync.
# Connection details live in a git-ignored .deploy.env (see .deploy.env.example).
# Usage: bash deploy.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ -f "$SCRIPT_DIR/.deploy.env" ]; then
  # shellcheck disable=SC1091
  . "$SCRIPT_DIR/.deploy.env"
else
  echo "ERROR: $SCRIPT_DIR/.deploy.env not found. Copy .deploy.env.example to .deploy.env and fill it in." >&2
  exit 1
fi

: "${KEY:?KEY not set in .deploy.env}"
: "${PORT:?PORT not set in .deploy.env}"
: "${USER_HOST:?USER_HOST not set in .deploy.env}"
: "${REMOTE_DIR:?REMOTE_DIR not set in .deploy.env}"

LOCAL_DIR="$SCRIPT_DIR/"

echo "Deploying to $USER_HOST:$REMOTE_DIR ..."

rsync -az --delete \
  -e "ssh -i $KEY -p $PORT -o BatchMode=yes" \
  --exclude '.git' \
  --exclude '.git/**' \
  --exclude '.DS_Store' \
  --exclude 'node_modules' \
  --exclude 'BUILD_PLAN.md' \
  --exclude 'deploy.sh' \
  --exclude '.deploy.env' \
  --exclude '.deploy.env.example' \
  "$LOCAL_DIR" \
  "$USER_HOST:$REMOTE_DIR/"

echo "Done. Purge WP Rocket + SiteGround cache after JS/CSS changes."
