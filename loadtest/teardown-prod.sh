#!/usr/bin/env bash
#
# Upratanie po load teste na PROD: zmaže výsledky/question_sety test-userov (lt*)
# pre event 'loadtest' a samotný 'loadtest' event. Nedotkne sa iných dát.
#
# Spúšťaj cez:  ! bash loadtest/teardown-prod.sh
#
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$DIR/../deploy/.env"

echo "🧹 Upratujem load test dáta na PROD…"
ssh -i "$PROD_SSH_KEY" -p "$PROD_SSH_PORT" -o StrictHostKeyChecking=accept-new \
    "$PROD_SSH_USER@$PROD_SSH_HOST" 'bash -s' <<'REMOTE'
cd /data/c/5/c56ccf41-7750-49d8-9a17-6bfe8408a515/eventkviz.sk/web || exit 1
DEL=$(echo "DELETE FROM pmgonijet_cct_results WHERE akcia='loadtest';" | wp db query 2>&1; echo "results zmazané")
echo "   $DEL"
LT=$(echo "SELECT ID FROM pmgoniposts WHERE post_name='loadtest' AND post_type='eventkviz_event' LIMIT 1;" | wp db query --skip-column-names 2>/dev/null | tr -d '[:space:]')
if [ -n "$LT" ]; then
  printf "DELETE FROM pmgonipostmeta WHERE post_id=%s; DELETE FROM pmgoniposts WHERE ID=%s;" "$LT" "$LT" | wp db query
  echo "   ✅ loadtest event ($LT) + meta zmazané"
else
  echo "   (loadtest event už neexistuje)"
fi
REMOTE
echo "✅ Hotovo — prod je čistý."
