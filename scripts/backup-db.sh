#!/usr/bin/env bash
# ==============================================================================
# HisabKitabb / InvoiceShelf Daily Database Backup Script
#
# Runs daily (e.g., at 23:55 via cron) to:
# 1. Dump the complete MySQL/MariaDB database from the Docker container.
# 2. Save the backup named by Date (e.g., YYYY-MM-DD.sql).
# 3. Upon successful backup, automatically delete the previous day's backup.
# ==============================================================================

set -euo pipefail

# ------------------------------------------------------------------------------
# Configuration (override with environment variables if desired)
# ------------------------------------------------------------------------------
BACKUP_DIR="${BACKUP_DIR:-/home/ubuntu/db_backups}"
CONTAINER_NAME="${DB_CONTAINER:-invoiceshelf-mysql}"
DB_NAME="${DB_NAME:-invoiceshelf}"
DB_USER="${DB_USER:-invoiceshelf}"
DB_PASS="${DB_PASS:-somepass}"

DATE="$(date +%Y-%m-%d)"
BACKUP_FILE="${BACKUP_DIR}/${DATE}.sql"
TMP_FILE="${BACKUP_DIR}/${DATE}.sql.tmp"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# ------------------------------------------------------------------------------
# 1. Pre-flight Checks
# ------------------------------------------------------------------------------
if ! command -v docker >/dev/null 2>&1; then
    log "ERROR: docker command not found." >&2
    exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -Eq "^${CONTAINER_NAME}$"; then
    log "ERROR: Database container '${CONTAINER_NAME}' is not running." >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

# ------------------------------------------------------------------------------
# 2. Determine Dump Binary (mariadb-dump or mysqldump)
# ------------------------------------------------------------------------------
DUMP_BIN="mariadb-dump"
if ! docker exec "$CONTAINER_NAME" which mariadb-dump >/dev/null 2>&1; then
    DUMP_BIN="mysqldump"
fi

# ------------------------------------------------------------------------------
# 3. Create Backup to a Temporary File
# ------------------------------------------------------------------------------
log "Starting database backup for '${DB_NAME}' from container '${CONTAINER_NAME}'..."

rm -f "$TMP_FILE"

# Use MYSQL_PWD to prevent password exposure and avoid command-line warnings
docker exec -e MYSQL_PWD="$DB_PASS" "$CONTAINER_NAME" \
    "$DUMP_BIN" -u "$DB_USER" \
    --single-transaction \
    --quick \
    --skip-lock-tables \
    "$DB_NAME" > "$TMP_FILE"

# ------------------------------------------------------------------------------
# 4. Verify Backup Integrity
# ------------------------------------------------------------------------------
if [ ! -s "$TMP_FILE" ]; then
    log "ERROR: Backup failed. Dump file is empty." >&2
    rm -f "$TMP_FILE"
    exit 1
fi

# Verify it's not a small error message output
if [ "$(wc -l < "$TMP_FILE")" -lt 15 ] && grep -qi "error" "$TMP_FILE"; then
    log "ERROR: Dump file contains error message instead of valid SQL:" >&2
    cat "$TMP_FILE" >&2
    rm -f "$TMP_FILE"
    exit 1
fi

# Promote temporary file to final backup file
mv "$TMP_FILE" "$BACKUP_FILE"
BACKUP_SIZE="$(du -h "$BACKUP_FILE" | cut -f1)"
log "SUCCESS: Backup created at '${BACKUP_FILE}' (Size: ${BACKUP_SIZE})"

# ------------------------------------------------------------------------------
# 5. Delete Previous Day's Backup (keep only today's verified backup)
# ------------------------------------------------------------------------------
log "Cleaning up previous day's backup files in '${BACKUP_DIR}'..."
find "$BACKUP_DIR" -maxdepth 1 -type f -name "*.sql" ! -name "${DATE}.sql" -exec rm -v {} \;

log "Backup completed successfully."
exit 0
