#!/usr/bin/env bash
#
# Vytvorí dedikovaný 'loadtest' event na PROD klonovaním 'demo' event-u.
# INSERT-only — nedotkne sa žiadnych iných dát (žiadny UPDATE/DELETE cudzích eventov,
# žiadny full DB import). Individuálny režim (hráč zadá len ?user=ltN).
#
# Spúšťaj cez:  ! bash loadtest/setup-prod-event.sh
# (priamy prod DB zápis cez SSH — auto-mode classifier ho Claude blokne, preto ho
#  spúšťa user; je idempotentný — starý loadtest event najprv zmaže a vytvorí nanovo.)
#
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# SSH config (host/port/key) berieme z deploy/.env — drží sa v sync s deploy.
# shellcheck disable=SC1091
source "$DIR/../deploy/.env"

echo "🔧 Vytváram 'loadtest' event na PROD (klon 'demo', INSERT-only)…"
ssh -i "$PROD_SSH_KEY" -p "$PROD_SSH_PORT" -o StrictHostKeyChecking=accept-new \
    "$PROD_SSH_USER@$PROD_SSH_HOST" 'bash -s' <<'REMOTE'
cd /data/c/5/c56ccf41-7750-49d8-9a17-6bfe8408a515/eventkviz.sk/web || exit 1
DEMO=$(echo "SELECT ID FROM pmgoniposts WHERE post_name='demo' AND post_type='eventkviz_event' LIMIT 1;" | wp db query --skip-column-names 2>/dev/null | tr -d '[:space:]')
[ -z "$DEMO" ] && { echo "❌ 'demo' event na prod nenájdený — nemám čo klonovať"; exit 1; }
echo "   zdroj demo ID: $DEMO"
LT=$(echo "SELECT ID FROM pmgoniposts WHERE post_name='loadtest' AND post_type='eventkviz_event' LIMIT 1;" | wp db query --skip-column-names 2>/dev/null | tr -d '[:space:]')
[ -n "$LT" ] && { echo "   mažem starý loadtest event $LT"; printf "DELETE FROM pmgonipostmeta WHERE post_id=%s; DELETE FROM pmgoniposts WHERE ID=%s;" "$LT" "$LT" | wp db query; }
cat <<SQL | wp db query
INSERT INTO pmgoniposts (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count,post_modified,post_modified_gmt)
SELECT post_author,post_date,post_date_gmt,post_content,'LOADTEST (zatazovy test)','',post_status,comment_status,ping_status,post_password,'loadtest',to_ping,pinged,post_content_filtered,post_parent,CONCAT(guid,'-loadtest'),menu_order,post_type,post_mime_type,comment_count,NOW(),NOW() FROM pmgoniposts WHERE ID=$DEMO;
SET @new=LAST_INSERT_ID();
INSERT INTO pmgonipostmeta (post_id,meta_key,meta_value) SELECT @new,meta_key,meta_value FROM pmgonipostmeta WHERE post_id=$DEMO;
UPDATE pmgonipostmeta SET meta_value='1' WHERE post_id=@new AND meta_key='event_general_identifikacia_kodom_usera';
UPDATE pmgonipostmeta SET meta_value='0' WHERE post_id=@new AND meta_key='event_general_identifikacia_userov_timu';
UPDATE pmgonipostmeta SET meta_value='0' WHERE post_id=@new AND meta_key='event_general_verify_users_in_db';
SQL
NEW=$(echo "SELECT ID FROM pmgoniposts WHERE post_name='loadtest' AND post_type='eventkviz_event' LIMIT 1;" | wp db query --skip-column-names 2>/dev/null | tr -d '[:space:]')
MQ=$(echo "SELECT meta_value FROM pmgonipostmeta WHERE post_id=$NEW AND meta_key='event_mapa_quizzes' LIMIT 1;" | wp db query --skip-column-names 2>/dev/null | sed -n 's/.*"slug":"\([^"]*\)".*/\1/p' | head -1)
echo "   ✅ loadtest event ID: $NEW   (mapa slug pre config.env MQ=$MQ)"
REMOTE
echo "✅ Hotovo. Daj Claudovi vedieť → nastaví AKCIA=loadtest + MQ a pustí testy."
