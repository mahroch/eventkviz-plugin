#!/usr/bin/env bash
#
# EventKviz load test runner — opakovateľný.
#
# Použitie:
#   bash run.sh smoke              # 1 VU, validácia skriptu (zanedbateľná záťaž) — VŽDY najprv
#   bash run.sh realistic [VUS]    # ramp na VUS hráčov + think time (default 50)
#   bash run.sh burst [VUS]        # všetci naraz (najhorší prípad)
#   bash run.sh ramp-to-break      # dvíha 25→50→100→200 kým nezačne padať
#
# ⚠️  PROD test — spúšťaj LEN v tichom okne, NIE počas živej akcie.
#     Testuj proti DEDIKOVANÉMU eventu (AKCIA v config.env), po teste uprac (viď README).
#
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# shellcheck disable=SC1091
[ -f config.env ] && source config.env

SCENARIO="${1:-realistic}"
VUS="${2:-${VUS:-50}}"

command -v k6 >/dev/null || { echo "❌ k6 nie je nainštalované → spusti: brew install k6"; exit 1; }

case "$SCENARIO" in smoke|realistic|burst|ramp-to-break) ;; *)
  echo "❌ Neznámy scenár '$SCENARIO'. Použi: smoke | realistic | burst | ramp-to-break"; exit 1 ;;
esac

mkdir -p results
TS="$(date +%Y%m%d-%H%M%S)"
OUT="results/${SCENARIO}-${VUS}vu-${TS}"

echo "═══════════════════════════════════════════════════════════"
echo "🚀 EventKviz load test"
echo "   scenár:   $SCENARIO"
echo "   VUs:      $VUS"
echo "   akcia:    ${AKCIA}   (${BASE_URL})"
echo "   typy:     ${TYPES}"
echo "   assety:   ${FETCH_ASSETS}"
echo "   výstup:   ${OUT}.txt / .json"
echo "═══════════════════════════════════════════════════════════"
[ "$SCENARIO" != "smoke" ] && echo "⚠️  PROD záťaž — uisti sa, že NEBEŽÍ živá akcia. Ctrl+C na zrušenie." && sleep 3

k6 run \
  -e BASE_URL="${BASE_URL}" \
  -e AKCIA="${AKCIA}" \
  -e MQ="${MQ}" \
  -e TYPES="${TYPES}" \
  -e FETCH_ASSETS="${FETCH_ASSETS}" \
  -e MAP_GEOJSON="${MAP_GEOJSON}" \
  -e SCENARIO="$SCENARIO" \
  -e VUS="$VUS" \
  --summary-export="${OUT}.json" \
  eventkviz-load.js | tee "${OUT}.txt"

echo ""
echo "✅ Hotovo. Výsledky: ${OUT}.txt (čitateľné) + ${OUT}.json (strojové)"
