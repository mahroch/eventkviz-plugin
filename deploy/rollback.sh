#!/usr/bin/env bash
#
# rollback.sh — vráti prod do stavu zachyteného v backupe.
#
# Usage:
#   bash deploy/rollback.sh                    # zoznam dostupných backup-ov
#   bash deploy/rollback.sh 2026-05-18-141522  # restore konkrétny timestamp
#   bash deploy/rollback.sh latest             # posledný backup
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

[[ -f .env ]] || { echo "❌ .env neexistuje"; exit 1; }
set -a; source .env; set +a

BACKUP_DIR="$SCRIPT_DIR/backups"

# List mode
if [[ $# -eq 0 ]]; then
    echo "Dostupné backupy v $BACKUP_DIR:"
    ls -t "$BACKUP_DIR"/prod-db-*.sql.gz 2>/dev/null | head -20 | while read -r f; do
        ts=$(basename "$f" .sql.gz | sed 's/^prod-db-//')
        echo "  $ts  ($(du -h "$f" | cut -f1))"
    done
    echo ""
    echo "Usage: bash rollback.sh <timestamp>  alebo  bash rollback.sh latest"
    exit 0
fi

TARGET="$1"
if [[ "$TARGET" == "latest" ]]; then
    TARGET=$(ls -t "$BACKUP_DIR"/prod-db-*.sql.gz 2>/dev/null | head -1 | xargs basename 2>/dev/null | sed 's/^prod-db-//; s/\.sql\.gz$//')
    [[ -z "$TARGET" ]] && { echo "❌ Žiadne backupy."; exit 1; }
    echo "🔍 Latest backup: $TARGET"
fi

DB_BACKUP="$BACKUP_DIR/prod-db-${TARGET}.sql.gz"
UP_BACKUP="$BACKUP_DIR/prod-uploads-${TARGET}.tar.gz"

[[ -f "$DB_BACKUP" ]] || { echo "❌ DB backup neexistuje: $DB_BACKUP"; exit 1; }

# SSH command setup
SSH_OPTS=(-p "${PROD_SSH_PORT:-22}" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
if [[ -n "${PROD_SSH_KEY:-}" ]]; then
    SSH_AUTH=(ssh "${SSH_OPTS[@]}" -i "$PROD_SSH_KEY")
    SCP_AUTH=(scp -P "${PROD_SSH_PORT:-22}" -i "$PROD_SSH_KEY" -o StrictHostKeyChecking=accept-new)
else
    SSH_AUTH=(sshpass -p "$PROD_SSH_PASS" ssh "${SSH_OPTS[@]}")
    SCP_AUTH=(sshpass -p "$PROD_SSH_PASS" scp -P "${PROD_SSH_PORT:-22}" -o StrictHostKeyChecking=accept-new)
fi
ssh_run() { "${SSH_AUTH[@]}" "${PROD_SSH_USER}@${PROD_SSH_HOST}" "$@"; }
scp_up()  { "${SCP_AUTH[@]}" "$1" "${PROD_SSH_USER}@${PROD_SSH_HOST}:$2"; }

echo "⚠  ROLLBACK PROD na stav z $TARGET"
echo "   DB backup:      $DB_BACKUP"
[[ -f "$UP_BACKUP" ]] && echo "   Uploads backup: $UP_BACKUP"
read -rp "Pokračovať? [yes/N] " confirm
[[ "$confirm" == "yes" ]] || { echo "Zrušené."; exit 0; }

REMOTE_TMP="~/eventkviz-rollback-$(date +%s)"
ssh_run "mkdir -p $REMOTE_TMP"

# Upload DB backup (uncompress on fly)
echo "📤 Upload DB backup…"
gunzip -c "$DB_BACKUP" | ssh_run "cat > $REMOTE_TMP/db.sql"

# Restore DB cez WP-CLI (číta wp-config.php)
echo "🗄  Restore prod DB…"
ssh_run "cd '$PROD_WP_PATH' && wp db import $REMOTE_TMP/db.sql"

# Restore uploads
if [[ -f "$UP_BACKUP" ]]; then
    echo "📤 Upload uploads backup…"
    scp_up "$UP_BACKUP" "$REMOTE_TMP/uploads.tar.gz"
    echo "🖼  Extract uploads…"
    ssh_run "cd '$PROD_WP_PATH/wp-content' && rm -rf uploads && tar xzf $REMOTE_TMP/uploads.tar.gz"
fi

# Cleanup
ssh_run "rm -rf $REMOTE_TMP"

echo "✅ Rollback hotový. Otestuj: $PROD_URL"
