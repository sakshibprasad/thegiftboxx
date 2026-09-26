#!/usr/bin/env bash
# Builds the three upload-ready zips described in docs/DEPLOYMENT.md.
# Usage: bash tools/build-zips.sh [output-folder]
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/dist}"
mkdir -p "$OUT"
rm -f "$OUT"/*.zip
cd "$ROOT"
zip -qr "$OUT/1-private-app.zip" app cron storage/.htaccess storage/.gitkeep \
  -x "app/config.php" "*.DS_Store"
(cd admin && zip -qr "$OUT/2-admin.zip" . -x "app-path.php" "*.DS_Store")
# Never ship the uploads folder: extracting it with "Replace" would wipe the photos.
# (Its protection file is recreated automatically by the app.)
(cd public && zip -qr "$OUT/3-website.zip" . -x "uploads" "uploads/*" "*.DS_Store")
# One-file update for Admin → Updates (never contains config.php or uploads).
VER="$(tr -d '[:space:]' < app/VERSION)"
zip -qr "$OUT/thegiftboxx-update-$VER.zip" app cron admin public \
  -x "app/config.php" "public/uploads" "public/uploads/*" "admin/app-path.php" "*.DS_Store"
ls -lh "$OUT"/*.zip
