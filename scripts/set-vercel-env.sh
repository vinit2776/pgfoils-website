#!/usr/bin/env bash
# Asks for the database and file-storage secrets and saves them as Vercel production variables.
# Nothing is printed or written to disk. Run it yourself from the site folder: ./scripts/set-vercel-env.sh
set -euo pipefail
cd "$(dirname "$0")/.."

put() { printf '%s' "$2" | vercel env add "$1" production --force >/dev/null && echo "saved $1"; }
ask() { local v; read -r -p "$1: " v; put "$2" "$v"; }
ask_secret() { local v; read -r -s -p "$1 (hidden): " v; echo; put "$2" "$v"; }

echo "--- TiDB Cloud (Connect dialog) ---"
ask "Host (looks like gateway01....tidbcloud.com)" DB_HOST
ask "Port (usually 4000)" DB_PORT
ask "Database name (pgfoils)" DB_DATABASE
ask "Username (looks like abc123.root)" DB_USERNAME
ask_secret "Password" DB_PASSWORD

echo "--- Cloudflare R2 ---"
ask "Bucket name" R2_BUCKET
ask "S3 API endpoint (https://<account-id>.r2.cloudflarestorage.com)" R2_ENDPOINT
ask "Access Key ID" R2_ACCESS_KEY_ID
ask_secret "Secret Access Key" R2_SECRET_ACCESS_KEY

echo "Done. Variables are saved for Production."
