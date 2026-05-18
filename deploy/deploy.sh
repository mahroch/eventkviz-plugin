#!/usr/bin/env bash
#
# deploy.sh — kompletný deploy local MAMP → Websupport prod.
#
# Pipeline (idempotent, safe):
#   1. Pred-flight check: .env, lokálne MAMP MySQL, SSH connect
#   2. Backup prod stavu: dump prod DB + tar wp-content/uploads → deploy/backups/
#   3. Dump local DB + tar local uploads
#   4. Upload na prod cez SCP
#   5. Remote: drop & recreate prod DB → import local dump
#   6. Remote: wp search-replace LOCAL_URL → PROD_URL (serialization-safe)
#   7. Remote: extract uploads (rsync prefer)
#   8. Remote: git pull plugin code (z GitHub mahroch/eventkviz-plugin)
#   9. Remote: wp cache flush + rewrite flush
#  10. Rollback hint ak by sa niečo pokazilo
#
# Usage:
#   bash deploy/deploy.sh           # real deploy s confirm promptom
#   bash deploy/deploy.sh --yes     # bez promptu (pre automation)
#   bash deploy/deploy.sh --dry-run # ukáže čo by sa stalo, nemení nič
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

DRY_RUN=0
SKIP_CONFIRM=0
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --yes|-y)  SKIP_CONFIRM=1 ;;
    esac
done

# ===== Load .env =====
if [[ ! -f .env ]]; then
    echo "❌ deploy/.env neexistuje. Spusti najprv: cp .env.example .env && nano .env"
    exit 1
fi
set -a; source .env; set +a

TS=$(date +%Y-%m-%d-%H%M%S)
BACKUP_DIR="$SCRIPT_DIR/backups"
mkdir -p "$BACKUP_DIR"

# ===== Helper: SSH command =====
SSH_OPTS=(-p "${PROD_SSH_PORT:-22}" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
if [[ -n "${PROD_SSH_KEY:-}" ]]; then
    SSH_AUTH=(ssh "${SSH_OPTS[@]}" -i "$PROD_SSH_KEY")
    SCP_AUTH=(scp -P "${PROD_SSH_PORT:-22}" -i "$PROD_SSH_KEY" -o StrictHostKeyChecking=accept-new)
elif [[ -n "${PROD_SSH_PASS:-}" ]]; then
    command -v sshpass >/dev/null || { echo "❌ sshpass missing — brew install hudochenkov/sshpass/sshpass"; exit 1; }
    SSH_AUTH=(sshpass -p "$PROD_SSH_PASS" ssh "${SSH_OPTS[@]}")
    SCP_AUTH=(sshpass -p "$PROD_SSH_PASS" scp -P "${PROD_SSH_PORT:-22}" -o StrictHostKeyChecking=accept-new)
else
    echo "❌ Ani PROD_SSH_KEY ani PROD_SSH_PASS nie sú nastavené v .env"
    exit 1
fi

ssh_run() { "${SSH_AUTH[@]}" "${PROD_SSH_USER}@${PROD_SSH_HOST}" "$@"; }
scp_up()  { "${SCP_AUTH[@]}" "$1" "${PROD_SSH_USER}@${PROD_SSH_HOST}:$2"; }
scp_down(){ "${SCP_AUTH[@]}" "${PROD_SSH_USER}@${PROD_SSH_HOST}:$1" "$2"; }

MYSQL_BIN="${LOCAL_MYSQL_BIN:-/Applications/MAMP/Library/bin/mysql}"
MYSQLDUMP_BIN="${LOCAL_MYSQLDUMP_BIN:-/Applications/MAMP/Library/bin/mysqldump}"

# ===== Pred-flight checks =====
echo "═══════════════════════════════════════════════════════════"
echo "🚀 DEPLOY local MAMP → eventkviz.sk (prod)"
[[ $DRY_RUN -eq 1 ]] && echo "   MODE: DRY-RUN (žiadne zmeny, len simulácia)"
echo "═══════════════════════════════════════════════════════════"
echo ""

echo "🔍 Pred-flight checks…"
[[ -x "$MYSQLDUMP_BIN" ]] || { echo "❌ mysqldump binary nenájdený: $MYSQLDUMP_BIN"; exit 1; }
ssh_run "echo OK" >/dev/null || { echo "❌ SSH zlyhal"; exit 1; }
echo "   ✅ SSH OK, mysqldump dostupný"
echo ""

# ===== Backup prod =====
echo "💾 1/8 Backup prod DB + uploads…"
PROD_BACKUP_DB="$BACKUP_DIR/prod-db-${TS}.sql.gz"
PROD_BACKUP_UPLOADS="$BACKUP_DIR/prod-uploads-${TS}.tar.gz"

if [[ $DRY_RUN -eq 0 ]]; then
    # Dump prod DB cez SSH (Websupport blokuje external MySQL prístup, takže to robíme remote)
    ssh_run "mysqldump --add-drop-table --skip-lock-tables -h '$PROD_DB_HOST' -u '$PROD_DB_USER' -p'$PROD_DB_PASS' '$PROD_DB_NAME'" | gzip > "$PROD_BACKUP_DB"
    echo "   ✅ Prod DB backup: $PROD_BACKUP_DB ($(du -h "$PROD_BACKUP_DB" | cut -f1))"

    # Tar prod uploads cez SSH (streamed)
    ssh_run "cd '$PROD_WP_PATH/wp-content' && tar czf - uploads 2>/dev/null" > "$PROD_BACKUP_UPLOADS" || echo "   ⚠ uploads tar zlyhal (možno žiadne uploads)"
    [[ -f "$PROD_BACKUP_UPLOADS" ]] && echo "   ✅ Prod uploads backup: $PROD_BACKUP_UPLOADS ($(du -h "$PROD_BACKUP_UPLOADS" | cut -f1))"
else
    echo "   [DRY] would: mysqldump prod → $PROD_BACKUP_DB"
    echo "   [DRY] would: tar prod uploads → $PROD_BACKUP_UPLOADS"
fi
echo ""

# ===== Dump local =====
echo "📦 2/8 Dump local MAMP DB + uploads…"
LOCAL_DUMP="$BACKUP_DIR/local-db-${TS}.sql"
LOCAL_UPLOADS="$BACKUP_DIR/local-uploads-${TS}.tar.gz"

if [[ $DRY_RUN -eq 0 ]]; then
    "$MYSQLDUMP_BIN" --add-drop-table --skip-lock-tables \
        -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" \
        -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" \
        "$LOCAL_DB_NAME" > "$LOCAL_DUMP"
    echo "   ✅ Local DB dump: $LOCAL_DUMP ($(du -h "$LOCAL_DUMP" | cut -f1))"

    tar czf "$LOCAL_UPLOADS" -C "$LOCAL_WP_PATH/wp-content" uploads 2>/dev/null || echo "   ⚠ uploads tar zlyhal"
    [[ -f "$LOCAL_UPLOADS" ]] && echo "   ✅ Local uploads: $LOCAL_UPLOADS ($(du -h "$LOCAL_UPLOADS" | cut -f1))"
else
    echo "   [DRY] would: mysqldump local → $LOCAL_DUMP"
    echo "   [DRY] would: tar local uploads → $LOCAL_UPLOADS"
fi
echo ""

# ===== Confirm pre destructive operations =====
if [[ $SKIP_CONFIRM -eq 0 && $DRY_RUN -eq 0 ]]; then
    echo "⚠  ĎALŠÍ KROK PREPISUJE PROD eventkviz.sk DB A UPLOADS."
    echo "   Backup prod DB: $PROD_BACKUP_DB"
    echo "   Backup uploads: $PROD_BACKUP_UPLOADS"
    echo "   Rollback: bash deploy/rollback.sh ${TS}"
    read -rp "Pokračovať? [yes/N] " confirm
    [[ "$confirm" == "yes" ]] || { echo "Zrušené."; exit 0; }
fi

# ===== Upload + remote restore =====
echo "📤 3/8 Upload local dump na prod…"
REMOTE_TMP="~/eventkviz-deploy-${TS}"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "mkdir -p $REMOTE_TMP"
    scp_up "$LOCAL_DUMP" "$REMOTE_TMP/db.sql"
    [[ -f "$LOCAL_UPLOADS" ]] && scp_up "$LOCAL_UPLOADS" "$REMOTE_TMP/uploads.tar.gz"
    echo "   ✅ Uploaded"
else
    echo "   [DRY] would: scp dump + uploads → prod:$REMOTE_TMP"
fi
echo ""

echo "🗄  4/8 Import local DB do prod…"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "mysql -h '$PROD_DB_HOST' -u '$PROD_DB_USER' -p'$PROD_DB_PASS' '$PROD_DB_NAME' < $REMOTE_TMP/db.sql"
    echo "   ✅ DB import OK"
else
    echo "   [DRY] would: mysql import prod"
fi
echo ""

echo "🔁 5/8 URL replace ($LOCAL_URL → $PROD_URL)…"
if [[ $DRY_RUN -eq 0 ]]; then
    # Pokus o wp-cli; fallback na priame SQL UPDATE (rieši hlavné tabuľky, NIE serialized data — riziko!)
    WPCLI_BIN=$(ssh_run "which wp 2>/dev/null || ls ~/wp-cli.phar 2>/dev/null || echo NONE")
    if [[ "$WPCLI_BIN" != "NONE" ]]; then
        WP_CMD="$WPCLI_BIN"
        [[ "$WP_CMD" == *.phar ]] && WP_CMD="php $WP_CMD"
        ssh_run "cd '$PROD_WP_PATH' && $WP_CMD search-replace '$LOCAL_URL' '$PROD_URL' --all-tables --skip-columns=guid --report-changed-only"
        echo "   ✅ URL replace cez WP-CLI (serialization-safe)"
    else
        echo "   ⚠  WP-CLI nedostupné — robím raw SQL UPDATE (POZOR: serialized data sa môžu pokaziť!)"
        ssh_run "mysql -h '$PROD_DB_HOST' -u '$PROD_DB_USER' -p'$PROD_DB_PASS' '$PROD_DB_NAME' -e \"
            UPDATE pmgonioptions SET option_value=REPLACE(option_value,'$LOCAL_URL','$PROD_URL') WHERE option_name IN ('siteurl','home');
            UPDATE pmgoniposts SET post_content=REPLACE(post_content,'$LOCAL_URL','$PROD_URL'), guid=REPLACE(guid,'$LOCAL_URL','$PROD_URL');
            UPDATE pmgonipostmeta SET meta_value=REPLACE(meta_value,'$LOCAL_URL','$PROD_URL') WHERE meta_value LIKE '%$LOCAL_URL%' AND meta_value NOT LIKE '%a:%' AND meta_value NOT LIKE '%s:%';
        \""
        echo "   ⚠  Inštaluj WP-CLI na prod pre safe URL replace nabudúce!"
    fi
else
    echo "   [DRY] would: wp search-replace $LOCAL_URL → $PROD_URL"
fi
echo ""

echo "🖼  6/8 Extract uploads…"
if [[ $DRY_RUN -eq 0 && -f "$LOCAL_UPLOADS" ]]; then
    ssh_run "cd '$PROD_WP_PATH/wp-content' && tar xzf $REMOTE_TMP/uploads.tar.gz"
    echo "   ✅ Uploads extracted"
else
    echo "   [DRY/SKIP] uploads"
fi
echo ""

echo "🔄 7/8 Git pull plugin code z GitHub…"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "cd '$PROD_PLUGIN_PATH' && git pull origin main" || echo "   ⚠ git pull zlyhal — možno plugin nie je git repo; treba clone-núť ručne (jednorazovo)"
else
    echo "   [DRY] would: cd $PROD_PLUGIN_PATH && git pull origin main"
fi
echo ""

echo "🧹 8/8 Cache + rewrite flush…"
if [[ $DRY_RUN -eq 0 ]]; then
    WPCLI_BIN=$(ssh_run "which wp 2>/dev/null || ls ~/wp-cli.phar 2>/dev/null || echo NONE")
    if [[ "$WPCLI_BIN" != "NONE" ]]; then
        WP_CMD="$WPCLI_BIN"
        [[ "$WP_CMD" == *.phar ]] && WP_CMD="php $WP_CMD"
        ssh_run "cd '$PROD_WP_PATH' && $WP_CMD cache flush && $WP_CMD rewrite flush"
        echo "   ✅ Flushed"
    else
        echo "   ⚠ Preskakujem (WP-CLI N/A)"
    fi
    # Cleanup remote tmp
    ssh_run "rm -rf $REMOTE_TMP"
else
    echo "   [DRY] would: wp cache flush + rewrite flush + cleanup remote tmp"
fi
echo ""

echo "═══════════════════════════════════════════════════════════"
echo "✅ DEPLOY HOTOVÝ"
[[ $DRY_RUN -eq 1 ]] && echo "   (toto bol dry-run, žiadne reálne zmeny)"
echo "   Otestuj: $PROD_URL"
echo "   Rollback: bash deploy/rollback.sh $TS"
echo "═══════════════════════════════════════════════════════════"
