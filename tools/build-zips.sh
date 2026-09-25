#!/usr/bin/env bash
# Builds the three upload-ready zips described in docs/DEPLOYMENT.md.
# Usage: bash tools/build-zips.sh [output-folder]
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/dist}"
mkdir -p "$OUT"
rm -f "$OUT"/1-private-app.zip "$OUT"/2-admin.zip "$OUT"/3-website.zip
cd "$ROOT"
zip -qr "$OUT/1-private-app.zip" app cron storage/.htaccess storage/.gitkeep \
  -x "app/config.php" "*.DS_Store"
(cd admin && zip -qr "$OUT/2-admin.zip" . -x "app-path.php" "*.DS_Store")
(cd public && zip -qr "$OUT/3-website.zip" . -x "uploads/*" "*.DS_Store" && zip -q "$OUT/3-website.zip" uploads/.htaccess)
ls -lh "$OUT"/*.zip
