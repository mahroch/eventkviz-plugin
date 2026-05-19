#!/usr/bin/env bash
#
# discovery.sh — overí prod prostredie (Websupport) po nahodení credentials
# do deploy/.env. Otestuje:
#   1. SSH connect
#   2. WP-CLI dostupnosť (najlepšie nainštalovaný globally, alebo phar)
#   3. MySQL prod connect
#   4. Nájde WP install path + plugin path
#   5. Otestuje že lokálne MAMP MySQL beží + tabuľky existujú
#
# Usage: cd deploy && bash discovery.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ ! -f .env ]]; then
    echo "❌ .env neexistuje. Skopíruj .env.example → .env a vyplň credentials."
    echo "   cp .env.example .env && nano .env"
    exit 1
fi

# Load .env (ignore comments, trim quotes)
set -a
# shellcheck disable=SC1091
source .env
set +a

echo "═══════════════════════════════════════════════════════════"
echo "🔍 DISCOVERY — overujem prod prostredie pre eventkviz.sk"
echo "═══════════════════════════════════════════════════════════"
echo ""

# 1. SSH connect
echo "1️⃣  SSH connect test…"
if [[ -z "${PROD_SSH_HOST:-}" || -z "${PROD_SSH_USER:-}" ]]; then
    echo "   ❌ PROD_SSH_HOST alebo PROD_SSH_USER nie sú nastavené v .env"
    exit 1
fi

SSH_OPTS=(-p "${PROD_SSH_PORT:-22}" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10)
if [[ -n "${PROD_SSH_KEY:-}" ]]; then
    SSH_OPTS+=(-i "$PROD_SSH_KEY")
    SSH_CMD=(ssh "${SSH_OPTS[@]}" "${PROD_SSH_USER}@${PROD_SSH_HOST}")
elif [[ -n "${PROD_SSH_PASS:-}" ]]; then
    if ! command -v sshpass &> /dev/null; then
        echo "   ⚠  sshpass nie je nainštalovaný (potrebný pre password auth)."
        echo "   Install: brew install hudochenkov/sshpass/sshpass"
        echo "   Alebo lepšie: nastav SSH key (ssh-keygen + ssh-copy-id) a PROD_SSH_KEY v .env"
        exit 1
    fi
    SSH_CMD=(sshpass -p "$PROD_SSH_PASS" ssh "${SSH_OPTS[@]}" "${PROD_SSH_USER}@${PROD_SSH_HOST}")
else
    echo "   ❌ Ani PROD_SSH_KEY ani PROD_SSH_PASS nie sú nastavené"
    exit 1
fi

if "${SSH_CMD[@]}" "echo 'SSH OK: '\$(uname -n)" 2>&1; then
    echo "   ✅ SSH funguje"
else
    echo "   ❌ SSH zlyhal. Skontroluj host/port/user/heslo v .env"
    exit 1
fi
echo ""

# 2. WP-CLI
echo "2️⃣  WP-CLI test na proď…"
WPCLI=$("${SSH_CMD[@]}" "which wp 2>/dev/null || which wp-cli || ls ~/wp-cli.phar 2>/dev/null || echo NONE")
if [[ "$WPCLI" == "NONE" ]]; then
    echo "   ⚠  WP-CLI nie je nainštalovaný. Odporúčam:"
    echo "   ssh ${PROD_SSH_USER}@${PROD_SSH_HOST} -p ${PROD_SSH_PORT:-22}"
    echo "   curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
    echo "   chmod +x wp-cli.phar"
    echo "   Po install: deploy script použije ~/wp-cli.phar automaticky"
else
    echo "   ✅ WP-CLI: $WPCLI"
fi
echo ""

# 3. MySQL prod — cez WP-CLI (číta wp-config.php → žiadne credential parsing)
echo "3️⃣  MySQL prod test (cez WP-CLI)…"
if [[ -z "${PROD_WP_PATH:-}" ]]; then
    echo "   ⚠  PROD_WP_PATH zatiaľ neznáme — preskakujem (doplň po kroku 4)"
elif [[ "$WPCLI" == "NONE" ]]; then
    echo "   ⚠  WP-CLI nedostupné — preskakujem"
else
    if "${SSH_CMD[@]}" "cd '$PROD_WP_PATH' && wp db check 2>&1" | head -5; then
        echo "   ✅ MySQL funguje (WP-CLI vie pripojiť)"
    else
        echo "   ❌ MySQL spojenie zlyhalo (skontroluj wp-config.php credentials)"
    fi
fi
echo ""

# 4. Find WP install path
echo "4️⃣  Hľadám WordPress install path na prod…"
WP_PATH=$("${SSH_CMD[@]}" "find ~ -name 'wp-config.php' -type f 2>/dev/null | head -3")
if [[ -n "$WP_PATH" ]]; then
    echo "   ✅ Nájdené wp-config.php:"
    echo "$WP_PATH" | sed 's/^/      /'
    FIRST_PATH=$(echo "$WP_PATH" | head -1 | xargs dirname)
    echo "   💡 Pravdepodobný WP root: $FIRST_PATH"
    echo "   💡 Plugin path: $FIRST_PATH/wp-content/plugins/eventkviz"
    if [[ -z "${PROD_WP_PATH:-}" ]]; then
        echo "   ⚠  Nastav v .env: PROD_WP_PATH=$FIRST_PATH"
    fi
else
    echo "   ❌ wp-config.php sa nenašiel v home directory"
fi
echo ""

# 5. Local MAMP MySQL
echo "5️⃣  Lokálne MAMP MySQL test…"
# Preferuj LOCAL_MYSQL_BIN z .env (default ukazuje na MAMP 7.x mysql80 path).
# --ssl-mode=DISABLED — MAMP 7.x balí MySQL 8.0 ktorý default-uje na TLS pre
# TCP spojenia; local MAMP ale nema realny TLS cert -> bez tohto flagu chyba
# "TLS/SSL error: self-signed certificate in certificate chain".
MYSQL_BIN="${LOCAL_MYSQL_BIN:-/Applications/MAMP/Library/bin/mysql80/bin/mysql}"
if [[ ! -x "$MYSQL_BIN" ]]; then
    MYSQL_BIN=$(which mysql 2>/dev/null || echo "")
fi
if [[ -n "$MYSQL_BIN" ]]; then
    if "$MYSQL_BIN" --ssl-mode=DISABLED -h "${LOCAL_DB_HOST%:*}" -P "${LOCAL_DB_HOST##*:}" -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" -e "SHOW TABLES;" "$LOCAL_DB_NAME" 2>&1 | head -5; then
        echo "   ✅ Local MySQL funguje"
    else
        echo "   ❌ Local MySQL spojenie zlyhalo"
    fi
else
    echo "   ❌ mysql binary nenájdený"
fi
echo ""

echo "═══════════════════════════════════════════════════════════"
echo "✅ Discovery hotové. Ak všetky 5 testov OK, môžem spustiť deploy."
echo "═══════════════════════════════════════════════════════════"
