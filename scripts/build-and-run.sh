#!/usr/bin/env bash
# ==============================================================================
# Script 1: Build Docker Image & Run Containers with Existing Database
#
# Usage:
#   ./scripts/build-and-run.sh
#
# What it does:
#   1. Builds the latest Docker image from current code.
#   2. Starts/recreates the webapp container while preserving the database volume.
#   3. Waits for the application container to become healthy.
#   4. Enables all custom modules, runs migrations, and clears cache.
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.ec2.yml"
WEBAPP_CONTAINER="invoiceshelf-variant-mariadb"
DB_CONTAINER="invoiceshelf-mysql"
MODULES="LorryReceipt LrReceipt AiAssistant ChangeName InvoiceReceipt Quotation Trips TasksProjects AccessRequest"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

cd "$PROJECT_ROOT"

if [ ! -f "$COMPOSE_FILE" ]; then
    log "ERROR: Could not find compose file at $COMPOSE_FILE" >&2
    exit 1
fi

log "==> Step 1: Building Docker image with latest application code..."
docker build -t hisabkitabb-app:latest -f docker/production/Dockerfile .

log "==> Step 2: Starting containers (database data volume is preserved)..."
docker compose -f "$COMPOSE_FILE" up -d

log "==> Step 3: Waiting for database container '$DB_CONTAINER' to be healthy..."
max_wait=60
count=0
while [ $count -lt $max_wait ]; do
    status=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$DB_CONTAINER" 2>/dev/null || echo "down")
    if [ "$status" = "healthy" ]; then
        log "Database container '$DB_CONTAINER' is healthy."
        break
    fi
    sleep 2
    count=$((count + 2))
done

log "==> Step 4: Waiting for application inside '$WEBAPP_CONTAINER' to be ready..."
count=0
while [ $count -lt $max_wait ]; do
    if docker exec "$WEBAPP_CONTAINER" php artisan --version >/dev/null 2>&1; then
        log "Application container is responsive."
        break
    fi
    sleep 2
    count=$((count + 2))
done

log "==> Step 5: Enabling all custom modules..."
docker exec "$WEBAPP_CONTAINER" php artisan module:enable $MODULES

log "==> Step 6: Running module migrations..."
docker exec "$WEBAPP_CONTAINER" php artisan module:migrate --force

log "==> Step 7: Clearing and refreshing application cache..."
docker exec "$WEBAPP_CONTAINER" php artisan optimize:clear

log "==> SUCCESS: Build complete and application is up and running!"
docker ps --filter "name=invoiceshelf"
