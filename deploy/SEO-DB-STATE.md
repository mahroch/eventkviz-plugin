# EK dev→prod — JEDNODUCHÝ model (full overwrite)

> Aktualizované 2026-06-15. **Local = jediný zdroj pravdy. Prod sa prepíše celým localom.**
> Žiadne RankMath Google napojenie (Analytics modul VYPNUTÝ na oboch) → **nič prod-only sa nemusí chrániť.**

## Workflow pri pushi local → prod
1. **Záloha prod DB** (vždy): `wp db export` na prode → stiahnuť kópiu.
2. **Theme/plugin súbory** cez `deploy/deploy-theme-only.sh` (rsync, ako doteraz).
3. **Full DB**: `wp db export` local → scp na prod → `wp db import` na prode.
4. **search-replace URL** na prode: `wp search-replace 'http://localhost:8888/eventkviz' 'https://eventkviz.sk' --skip-columns=guid --all-tables`.
5. `wp cache flush` + `wp eval '\RankMath\Sitemap\Cache::invalidate_storage();'` (sitemap).

## Prečo je to teraz jednoduché
- **RankMath Google napojenie** (Analytics dashboard) = **vypnuté na localu aj prode**. GSC/GA dáta sa pozerajú **priamo v Google** (search.google.com, analytics.google.com). → žiadne prod-only options/tabuľky na ochranu.
- **SEO config** (tituly/meta/Organization+Service schema/sitemap exclusions/noindex/`google_verify`) = celé v **local DB** → prenesie sa overwriteom. *(parity netreba riešiť — local je master)*
- **GA meranie** = `functions.php` cookie-gated gtag (`G-9P2RF2EZE2`) — **kód**, ide s theme rsync, env-safe (beží len na eventkviz.sk).
- **Cookie lišta + Service schema** = tiež `functions.php` (kód).

## ⚠️ Pozor pri full overwrite
- Prepíše aj **ŽIVÉ prod dáta**: participanti, výsledky (`pmgonijet_cct_results`), používatelia (prod logins). → Push rob **PRED eventom / po exporte výsledkov**, NIE počas živej akcie.
- Vždy najprv **záloha prod DB** (krok 1).
- Na prode ostali po vypnutí Analytics modulu **orphaned `*_rank_math_analytics_*` tabuľky** — neškodné (modul off), prípadne dropnúť. Pri full overwrite local→prod sa connection options (`rank_math_connect_data`…) aj tak prepíšu (local ich nemá).

## SEO config — referenčný inventár (čo všetko sedí v DB)
- options: `rank_math_registration_skip=1`, titles(knowledgegraph=company/EventKviz, pt_page_default_rich_snippet=off, open_graph_image), general(breadcrumbs=on, `google_verify=X39p_apMF8MGlZbZim6UgIGyEkscp5T5uExKngNAkMk`), sitemap(pt_participants/questions-*/stanoviska*/e-floating-buttons/product/authors = off)
- postmeta: tituly/popisy na 297/1304/1302; `rank_math_robots=['noindex']` na všetkých ostatných stránkach
- médiá: OG obrázok `ek-og.png` (zdroj theme/`_og-image.png`)
- page IDs zhodné local/prod: home=297, ponuka=1304, kontakt=1302

> 🔑 GOTCHA prod wp-cli: `/usr/local/bin/wp` wrapper re-splituje argumenty s medzerami → hodnoty s medzerami nastavuj cez `php /home/wp-cli.phar` priamo.
