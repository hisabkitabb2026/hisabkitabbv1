#!/usr/bin/env bash
# ==============================================================================
# Script: Instant Sync of PDF Templates to Running Container
#
# Usage:
#   ./scripts/sync-pdf-templates.sh
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEBAPP_CONTAINER="invoiceshelf-variant-mariadb"

echo "==> Syncing PDF templates directly into container volume..."
docker exec "$WEBAPP_CONTAINER" mkdir -p /var/www/html/storage/app/templates/pdf/invoice /var/www/html/storage/app/templates/pdf/estimate

docker cp "$PROJECT_ROOT/storage/app/templates/pdf/invoice/lr_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/lr_receipt.blade.php"
docker cp "$PROJECT_ROOT/storage/app/templates/pdf/invoice/lorry_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/lorry_receipt.blade.php"
docker cp "$PROJECT_ROOT/storage/app/templates/pdf/invoice/invoice_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/invoice_receipt.blade.php"
docker cp "$PROJECT_ROOT/storage/app/templates/pdf/estimate/quotation.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/estimate/quotation.blade.php"
docker cp "$PROJECT_ROOT/Modules/." "$WEBAPP_CONTAINER:/var/www/html/Modules/"

echo "==> Clearing cached compiled views..."
docker exec "$WEBAPP_CONTAINER" php artisan view:clear
docker exec "$WEBAPP_CONTAINER" php artisan optimize:clear

echo "==> Done! All PDF templates are updated and live."
