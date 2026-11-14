#!/usr/bin/env bash
# One-time setup of the live database and file storage. Run it yourself: ./scripts/go-live-setup.sh
# It asks for the same TiDB and R2 values you gave Vercel, keeps them only for this run, and then:
#   1. creates the tables   2. loads the Investors Corner list   3. creates your Admin account
#   4. copies the investor files into the private R2 bucket
set -euo pipefail
cd "$(dirname "$0")/.."

read -r -p "TiDB host: " DB_HOST
read -r -p "TiDB username: " DB_USERNAME
read -r -s -p "TiDB password (hidden): " DB_PASSWORD; echo
read -r -p "R2 access key ID: " R2_ACCESS_KEY_ID
read -r -s -p "R2 secret access key (hidden): " R2_SECRET_ACCESS_KEY; echo
read -r -p "Admin name: " ADMIN_NAME
read -r -p "Admin email: " ADMIN_EMAIL

export APP_ENV=production DB_CONNECTION=mysql DB_HOST DB_PORT=4000 DB_DATABASE=test DB_USERNAME DB_PASSWORD
export MYSQL_ATTR_SSL_CA=/etc/ssl/cert.pem
export R2_ACCESS_KEY_ID R2_SECRET_ACCESS_KEY R2_REGION=auto R2_BUCKET=pgfoils-investor-docs
export R2_ENDPOINT=https://b0966dda8247509d977c42f9d2dfb3d6.r2.cloudflarestorage.com
export SESSION_DRIVER=database CACHE_STORE=database

echo "== 1/4 Tables =="
php artisan migrate --force
echo "== 2/4 Investors Corner list and roles =="
php artisan db:seed --force
echo "== 3/4 Admin account (you will choose the password) =="
php artisan admin:create --name="$ADMIN_NAME" --email="$ADMIN_EMAIL"
echo "== 4/4 Copying investor files to R2 (takes several minutes) =="
php artisan documents:fetch-legacy --max-mb=100
echo "All done."
