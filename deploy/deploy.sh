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
SKIP_UPLOADS=0
for arg in "$@"; do
    case "$arg" in
        --dry-run)      DRY_RUN=1 ;;
        --yes|-y)       SKIP_CONFIRM=1 ;;
        --skip-uploads) SKIP_UPLOADS=1 ;;
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

# Git sync check: prod stiahne code z GitHub cez git pull. Ak local plugin má
# necommitnuté/nepushnuté zmeny, prod by dostal staršiu verziu než local DB
# očakáva → mismatch. Veriť že local code = GitHub main = prod po deploy.
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PLUGIN_DIR"
if [[ -n "$(git status --porcelain 2>/dev/null)" ]]; then
    echo "❌ Plugin má uncommitted zmeny:"
    git status --short
    echo "   Najprv commit + push na GitHub, potom spusti deploy."
    exit 1
fi
LOCAL_SHA=$(git rev-parse HEAD 2>/dev/null || echo "")
REMOTE_SHA=$(git ls-remote origin main 2>/dev/null | awk '{print $1}' || echo "")
if [[ -n "$LOCAL_SHA" && -n "$REMOTE_SHA" && "$LOCAL_SHA" != "$REMOTE_SHA" ]]; then
    echo "❌ Local HEAD ($LOCAL_SHA) ≠ GitHub main ($REMOTE_SHA)"
    echo "   Buď push najprv: git push origin main"
    echo "   Alebo pull zaostávajúce: git pull origin main"
    exit 1
fi
echo "   ✅ Git sync OK (local = GitHub main)"
cd "$SCRIPT_DIR"

# Code Snippets pre-flight: hľadáme aktívne snippets s require_once / include_once
# na lokálne MAMP/Applications paths. Tieto na proď zhodia WP s fatal error pri
# loadovaní pluginu (snippet sa eval-uje na každý request).
DANGEROUS_SNIPPETS=$("$MYSQLDUMP_BIN" --ssl-mode=DISABLED -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" "$LOCAL_DB_NAME" pmgonisnippets 2>/dev/null \
    | grep -oE "INSERT INTO [^(]+\([^)]+\) VALUES \([^)]*'/Applications/MAMP[^']*'[^)]*\)" || true)
# Lepší check cez DB query:
LOCAL_MYSQL="${LOCAL_MYSQL_BIN:-/Applications/MAMP/Library/bin/mysql}"
DANGEROUS_COUNT=$("$LOCAL_MYSQL" --ssl-mode=DISABLED -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" -se "
    SELECT COUNT(*) FROM pmgonisnippets
    WHERE active=1
    AND (
        code REGEXP '(require_once|include_once|require |include )[[:space:]]*[\\'\"]/Applications/MAMP'
        OR code REGEXP '(require_once|include_once|require |include )[[:space:]]*[\\'\"]/Users'
    );
" "$LOCAL_DB_NAME" 2>/dev/null || echo "0")
if [[ "${DANGEROUS_COUNT:-0}" -gt 0 ]]; then
    echo "❌ Found $DANGEROUS_COUNT aktívnych Code Snippets s require_once na local paths."
    echo "   Tieto pri load na prod hodia fatal error. Deaktivuj ich alebo oprav paths:"
    "$LOCAL_MYSQL" --ssl-mode=DISABLED -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" -e "
        SELECT id, name FROM pmgonisnippets
        WHERE active=1
        AND (code REGEXP '(require_once|include_once|require |include )[[:space:]]*[\\'\"]/Applications/MAMP'
          OR code REGEXP '(require_once|include_once|require |include )[[:space:]]*[\\'\"]/Users');
    " "$LOCAL_DB_NAME" 2>/dev/null
    exit 1
fi
echo "   ✅ Žiadne aktívne Code Snippets s local require paths"
echo ""

# ===== Backup prod =====
echo "💾 1/8 Backup prod DB (cez WP-CLI)…"
PROD_BACKUP_DB="$BACKUP_DIR/prod-db-${TS}.sql.gz"

if [[ $DRY_RUN -eq 0 ]]; then
    # wp db export číta wp-config.php → žiadne credential parsing (Websupport host:port format)
    ssh_run "cd '$PROD_WP_PATH' && wp --skip-plugins=code-snippets --skip-themes db export - 2>/dev/null" | gzip > "$PROD_BACKUP_DB"
    echo "   ✅ Prod DB backup: $PROD_BACKUP_DB ($(du -h "$PROD_BACKUP_DB" | cut -f1))"
else
    echo "   [DRY] would: wp db export prod → $PROD_BACKUP_DB"
fi
echo "   ℹ Uploads backup preskočený — rsync je delta sync, prod uploads"
echo "     sú stratené iba ak admin ručne zmaže local files. Pre full backup"
echo "     spusti: rsync prod:uploads → deploy/backups/prod-uploads-\$TS/"
echo ""

# ===== Dump local =====
echo "📦 2/8 Dump local MAMP DB…"
LOCAL_DUMP="$BACKUP_DIR/local-db-${TS}.sql"

if [[ $DRY_RUN -eq 0 ]]; then
    "$MYSQLDUMP_BIN" --ssl-mode=DISABLED --add-drop-table --skip-lock-tables \
        -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" \
        -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" \
        "$LOCAL_DB_NAME" > "$LOCAL_DUMP"
    echo "   ✅ Local DB dump: $LOCAL_DUMP ($(du -h "$LOCAL_DUMP" | cut -f1))"
else
    echo "   [DRY] would: mysqldump local → $LOCAL_DUMP"
fi
echo "   ℹ Uploads: rsync (delta sync) v kroku 6 — žiadny tar archív netreba"
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
echo "📤 3/8 Upload DB dump na prod…"
REMOTE_TMP="~/eventkviz-deploy-${TS}"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "mkdir -p $REMOTE_TMP"
    scp_up "$LOCAL_DUMP" "$REMOTE_TMP/db.sql"
    echo "   ✅ DB dump uploaded"
else
    echo "   [DRY] would: scp DB dump → prod:$REMOTE_TMP/db.sql"
fi
echo ""

echo "🗄  4/8 Import local DB do prod (cez WP-CLI)…"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "cd '$PROD_WP_PATH' && wp --skip-plugins=code-snippets --skip-themes db import $REMOTE_TMP/db.sql"
    echo "   ✅ DB import OK"
else
    echo "   [DRY] would: wp db import prod"
fi
echo ""

echo "🔁 5/8 URL replace ($LOCAL_URL → $PROD_URL)…"
if [[ $DRY_RUN -eq 0 ]]; then
    ssh_run "cd '$PROD_WP_PATH' && wp --skip-plugins=code-snippets --skip-themes search-replace '$LOCAL_URL' '$PROD_URL' --all-tables --skip-columns=guid --report-changed-only"
    echo "   ✅ URL replace (serialization-safe cez WP-CLI)"
else
    echo "   [DRY] would: wp search-replace $LOCAL_URL → $PROD_URL"
fi
echo ""

echo "🖼  6/8 Rsync uploads (delta sync, len changed files)…"
if [[ $SKIP_UPLOADS -eq 1 ]]; then
    echo "   ⏭  Preskočené (--skip-uploads)"
elif [[ $DRY_RUN -eq 1 ]]; then
    echo "   [DRY] would: rsync local uploads → prod uploads"
else
    # Rsync over SSH — resume + delta + len changed files
    # -e nastaví SSH command s našimi opts (port, key/sshpass)
    if [[ -n "${PROD_SSH_KEY:-}" ]]; then
        RSYNC_SSH="ssh -p ${PROD_SSH_PORT:-22} -i $PROD_SSH_KEY -o StrictHostKeyChecking=accept-new"
    else
        RSYNC_SSH="sshpass -p '$PROD_SSH_PASS' ssh -p ${PROD_SSH_PORT:-22} -o StrictHostKeyChecking=accept-new"
    fi
    # Note: openrsync (macOS default) nepodporuje --info flag — používame -v
    rsync -avz --partial \
        -e "$RSYNC_SSH" \
        "$LOCAL_WP_PATH/wp-content/uploads/" \
        "${PROD_SSH_USER}@${PROD_SSH_HOST}:${PROD_WP_PATH}/wp-content/uploads/" 2>&1 | tail -8
    echo "   ✅ Uploads synced"
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
    ssh_run "cd '$PROD_WP_PATH' && wp --skip-plugins=code-snippets --skip-themes cache flush 2>&1 && wp --skip-plugins=code-snippets --skip-themes rewrite flush 2>&1" | tail -5
    echo "   ✅ Flushed"
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
