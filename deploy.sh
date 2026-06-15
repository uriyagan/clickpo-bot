#!/usr/bin/env bash
# Deploy the ClickPo AI Chatbot plugin to the SiteGround WordPress site over SSH/rsync.
# Usage: bash deploy.sh
set -euo pipefail

KEY="$HOME/.ssh/clickpo_bot_np"
PORT=18765
USER_HOST="u14-zs3qpwevxqov@c1134371.sgvps.net"
REMOTE_DIR="~/www/clickpo.io/public_html/wp-content/plugins/clickpo-ai-chatbot"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)/"

echo "Deploying to $USER_HOST:$REMOTE_DIR ..."

rsync -az --delete \
  -e "ssh -i $KEY -p $PORT -o BatchMode=yes" \
  --exclude '.git' \
  --exclude '.git/**' \
  --exclude '.DS_Store' \
  --exclude 'node_modules' \
  --exclude 'BUILD_PLAN.md' \
  --exclude 'deploy.sh' \
  "$LOCAL_DIR" \
  "$USER_HOST:$REMOTE_DIR/"

echo "Done. Purge WP Rocket + SiteGround cache after JS/CSS changes."
