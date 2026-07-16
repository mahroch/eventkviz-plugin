#!/usr/bin/env bash
#
# deploy-theme-only.sh — nasadí IBA zmenené theme súbory na eventkviz.sk.
# ŽIADNY zásah do databázy, žiadny git pull, žiadne uploads.
# Pred uploadom stiahne zálohu pôvodných prod súborov (rollback ready).
# Auth + cesty číta z deploy/.env (SSH key auth, stabilný port).
#
# Usage:  bash deploy/deploy-theme-only.sh
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"
set -a; source .env; set +a

HOST="${PROD_SSH_HOST}"
USER="${PROD_SSH_USER}"
PORT="${PROD_SSH_PORT:-22}"
PROD_THEME="${PROD_WP_PATH}/wp-content/themes/hello-theme-child-master"
LOCAL_THEME="${LOCAL_WP_PATH}/wp-content/themes/hello-theme-child-master"
TS=$(date +%Y-%m-%d-%H%M%S)
BK="$SCRIPT_DIR/backups/prod-theme-${TS}"

FILES=( page-eventkviz-home.php page-eventkviz-home.css page-eventkviz-ponuka.php page-eventkviz-kontakt.php functions.php )

SSH_OPTS=(-p "$PORT" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
if [[ -n "${PROD_SSH_KEY:-}" ]]; then
    SSH=(ssh "${SSH_OPTS[@]}" -i "$PROD_SSH_KEY")
    SCP=(scp -P "$PORT" -o StrictHostKeyChecking=accept-new -i "$PROD_SSH_KEY")
elif [[ -n "${PROD_SSH_PASS:-}" ]]; then
    command -v sshpass >/dev/null || { echo "❌ sshpass chýba"; exit 1; }
    SSH=(sshpass -p "$PROD_SSH_PASS" ssh "${SSH_OPTS[@]}")
    SCP=(sshpass -p "$PROD_SSH_PASS" scp -P "$PORT" -o StrictHostKeyChecking=accept-new)
else
    echo "❌ ani PROD_SSH_KEY ani PROD_SSH_PASS nie sú v .env"; exit 1
fi

echo "═══ THEME-ONLY DEPLOY → ${PROD_URL} ═══"
echo "Súbory: ${FILES[*]}"
echo ""

echo "🔌 SSH test…"
"${SSH[@]}" "$USER@$HOST" "echo OK" >/dev/null
echo "   ✅ SSH OK"

echo "💾 Backup pôvodných prod súborov → $BK"
mkdir -p "$BK"
for f in "${FILES[@]}"; do
    if "${SCP[@]}" "$USER@$HOST:$PROD_THEME/$f" "$BK/$f" 2>/dev/null; then
        echo "   ✅ záloha $f"
    else
        echo "   ℹ $f na prode zatiaľ neexistuje (nový súbor)"
    fi
done

echo "📤 Upload nových súborov…"
for f in "${FILES[@]}"; do
    "${SCP[@]}" "$LOCAL_THEME/$f" "$USER@$HOST:$PROD_THEME/$f"
    echo "   ✅ $f"
done

echo "🧹 Cache flush…"
"${SSH[@]}" "$USER@$HOST" "cd '$PROD_WP_PATH' && wp --skip-plugins=code-snippets --skip-themes cache flush 2>/dev/null" && echo "   ✅ flushed" || echo "   ℹ cache flush preskočený"

echo ""
echo "═══ HOTOVO ═══"
echo "Backup (rollback): $BK"
echo "Otestuj: ${PROD_URL}  +  ${PROD_URL}/ponuka/"
