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
WEBAPP_CONTAINER="invoiceshelf-variant-mariadb"
DB_CONTAINER="invoiceshelf-mysql"
MODULES="LorryReceipt LrReceipt AiAssistant ChangeName InvoiceReceipt Quotation Trips TasksProjects AccessRequest"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

cd "$PROJECT_ROOT"

# Auto-detect existing compose file used by the running containers
RUNNING_COMPOSE=$(docker inspect "$WEBAPP_CONTAINER" --format '{{index .Config.Labels "com.docker.compose.project.config_files"}}' 2>/dev/null || true)

if [ -n "$RUNNING_COMPOSE" ] && [ -f "$RUNNING_COMPOSE" ]; then
    COMPOSE_FILE="$RUNNING_COMPOSE"
    COMPOSE_DIR="$(dirname "$COMPOSE_FILE")"
elif [ -f "$PROJECT_ROOT/docker/production/docker-compose.mysql.yml" ]; then
    COMPOSE_FILE="$PROJECT_ROOT/docker/production/docker-compose.mysql.yml"
    COMPOSE_DIR="$PROJECT_ROOT/docker/production"
else
    COMPOSE_FILE="$PROJECT_ROOT/docker-compose.ec2.yml"
    COMPOSE_DIR="$PROJECT_ROOT"
fi

log "Using Docker Compose config: $COMPOSE_FILE"

# ------------------------------------------------------------------------------
# Step 0: Fetch Latest Code from GitHub
# ------------------------------------------------------------------------------
log "==> Step 0: Fetching latest code from GitHub (origin/main)..."

# Check if GITHUB_TOKEN is defined in environment or .env file
if [ -z "${GITHUB_TOKEN:-}" ] && [ -f "$PROJECT_ROOT/.env" ]; then
    ENV_TOKEN=$(grep -E '^GITHUB_TOKEN=' "$PROJECT_ROOT/.env" | cut -d '=' -f2- | tr -d ' "' | tr -d "'" || true)
    if [ -n "$ENV_TOKEN" ]; then
        GITHUB_TOKEN="$ENV_TOKEN"
    fi
fi

if [ -n "${GITHUB_TOKEN:-}" ]; then
    log "Pulling latest code using GITHUB_TOKEN..."
    git pull "https://${GITHUB_TOKEN}@github.com/hisabkitabb2026/hisabkitabbv1.git" main
else
    log "Pulling latest code using server's git credentials..."
    git pull origin main
fi

log "==> Step 1: Building Docker image with latest application code..."
(cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" build webapp)

log "==> Step 2: Updating web application container (database data volume is preserved)..."
# Ensure the existing database container is running if it was stopped
if docker ps -a --format '{{.Names}}' | grep -Eq "^${DB_CONTAINER}$"; then
    if ! docker ps --format '{{.Names}}' | grep -Eq "^${DB_CONTAINER}$"; then
        log "Starting database container '$DB_CONTAINER'..."
        docker start "$DB_CONTAINER"
    fi
fi

# Recreate webapp container with the newly built image without conflicting with database
(cd "$COMPOSE_DIR" && docker compose -f "$COMPOSE_FILE" up -d --no-deps webapp)

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
