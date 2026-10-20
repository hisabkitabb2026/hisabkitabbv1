#!/usr/bin/env bash
# ==============================================================================
# Script 2: Container Startup, Health Watchdog & Module Verification
#
# Usage:
#   ./scripts/startup.sh          -> Checks if containers are running. If down, starts them and runs module commands.
#   ./scripts/startup.sh restart  -> Stops the containers, starts them, and runs module commands.
#   ./scripts/startup.sh force    -> Runs container startup and module commands unconditionally.
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEBAPP_CONTAINER="invoiceshelf-variant-mariadb"
DB_CONTAINER="invoiceshelf-mysql"
MODULES="LorryReceipt LrReceipt AiAssistant ChangeName InvoiceReceipt Quotation Trips"

ACTION="${1:-check}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

cd "$PROJECT_ROOT"

# Auto-detect existing compose file used by the running containers
RUNNING_COMPOSE=$(docker inspect "$WEBAPP_CONTAINER" --format '{{index .Config.Labels "com.docker.compose.project.config_files"}}' 2>/dev/null || true)

if [ -n "$RUNNING_COMPOSE" ] && [ -f "$RUNNING_COMPOSE" ]; then
    COMPOSE_FILE="$RUNNING_COMPOSE"
    COMPOSE_DIR="$(dirname "$COMPOSE_FILE")"
elif [ -f "$PROJECT_ROOT/docker-compose.ec2.yml" ]; then
    COMPOSE_FILE="$PROJECT_ROOT/docker-compose.ec2.yml"
    COMPOSE_DIR="$PROJECT_ROOT"
elif [ -f "$PROJECT_ROOT/docker/production/docker-compose.mysql.yml" ]; then
    COMPOSE_FILE="$PROJECT_ROOT/docker/production/docker-compose.mysql.yml"
    COMPOSE_DIR="$PROJECT_ROOT/docker/production"
fi

is_container_healthy() {
    local container="$1"
    local status
    status=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container" 2>/dev/null || echo "down")
    [ "$status" = "healthy" ] || [ "$status" = "running" ]
}

run_module_commands() {
    log "Waiting for application to boot and accept artisan commands..."
    local max_wait=60
    local count=0
    while [ $count -lt $max_wait ]; do
        if docker exec "$WEBAPP_CONTAINER" php artisan --version >/dev/null 2>&1; then
            break
        fi
        sleep 2
        count=$((count + 2))
    done

    log "Syncing latest module templates and code into container volume..."
    docker cp "$PROJECT_ROOT/Modules/." "$WEBAPP_CONTAINER:/var/www/html/Modules/"
    docker exec "$WEBAPP_CONTAINER" mkdir -p /var/www/html/storage/app/templates/pdf/invoice /var/www/html/storage/app/templates/pdf/estimate
    docker cp "$PROJECT_ROOT/Modules/LrReceipt/resources/views/pdf/lr_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/lr_receipt.blade.php"
    docker cp "$PROJECT_ROOT/Modules/LorryReceipt/resources/views/pdf/lorry_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/lorry_receipt.blade.php"
    docker cp "$PROJECT_ROOT/Modules/InvoiceReceipt/resources/views/pdf/invoice_receipt.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/invoice/invoice_receipt.blade.php"
    docker cp "$PROJECT_ROOT/Modules/Quotation/resources/views/pdf/quotation.blade.php" "$WEBAPP_CONTAINER:/var/www/html/storage/app/templates/pdf/estimate/quotation.blade.php"

    log "Enabling all custom modules..."
    docker exec "$WEBAPP_CONTAINER" php artisan module:enable $MODULES

    log "Applying module migrations..."
    docker exec "$WEBAPP_CONTAINER" php artisan module:migrate --force

    log "Clearing application and view cache..."
    docker exec "$WEBAPP_CONTAINER" php artisan view:clear
    docker exec "$WEBAPP_CONTAINER" php artisan optimize:clear

    log "All module commands completed successfully."
}

# Full restart mode: stop and start
if [ "$ACTION" = "restart" ] || [ "$ACTION" = "--restart" ] || [ "$ACTION" = "stop-start" ]; then
    log "Restarting containers..."
    (cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" stop webapp)
    docker restart "$DB_CONTAINER" || docker start "$DB_CONTAINER" || true
    (cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" up -d --no-deps webapp)
    run_module_commands
    log "SUCCESS: Containers restarted and modules active."
    docker ps --filter "name=invoiceshelf"
    exit 0
fi

# Check container health status
db_ok=true
webapp_ok=true

if ! is_container_healthy "$DB_CONTAINER"; then
    db_ok=false
fi

if ! is_container_healthy "$WEBAPP_CONTAINER"; then
    webapp_ok=false
fi

if [ "$db_ok" = true ] && [ "$webapp_ok" = true ] && [ "$ACTION" != "force" ]; then
    log "SUCCESS: Both containers ('$DB_CONTAINER' and '$WEBAPP_CONTAINER') are running healthy."
    log "To force a restart, run: ./scripts/startup.sh restart"
    exit 0
fi

log "One or more containers are down (DB healthy: $db_ok, Webapp healthy: $webapp_ok)."
if [ "$db_ok" = false ]; then
    log "Starting database container '$DB_CONTAINER'..."
    docker start "$DB_CONTAINER" || (cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" up -d database)
fi

log "Starting webapp container..."
(cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" up -d --no-deps webapp)
run_module_commands

log "SUCCESS: Containers started and modules active."
docker ps --filter "name=invoiceshelf"
exit 0
