---
pdf_version: 0.1
pdf_updated_at: 2026-06-05 18:02 UTC
pdf_filename: CHANGELOG-v0.1.pdf
---

# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [1.23.0] - 2026-06-10

### Added (QR aj pre mapové kvízy)
- Sekcia **2. Vstup do konkrétneho kvízu** teraz zobrazuje aj **mapové kvízy** — každý s vlastným vstupným linkom a tlačidlami QR PNG/PDF. Na QR PDF sa pod kódom vytlačí **názov mapovej šablóny** (napr. „Pohoria sveta", „Rieky SR").
- Nový vstupný link pre mapový kvíz: hráč si vyberie tím a ide rovno do konkrétneho mapového kvízu (predtým to šlo len cez hlavný hub).

## [1.22.2] - 2026-06-10

### Changed (QR PDF — popis namiesto filename)
- Na QR PDF sa pod kódom vytlačí **typové označenie** kvízu (Audio / Movies / Knowledge / Sudoku) namiesto názvu súboru. Filename sťahovaného súboru ostáva „event-typ".

## [1.22.1] - 2026-06-10

### Fixed (mapový kvíz Svet — chýbal mapový podklad)
- Mapový kvíz v regióne **Svet** (napr. Pohoria sveta) teraz zobrazuje **kontinenty + hranice štátov** ako podklad. Predtým boli na bielej ploche len plávajúce úlohové polygóny bez geografického kontextu. Podklad je neinteraktívny — kliky na úlohy fungujú ako predtým.

## [1.22.0] - 2026-06-10

### Added (QR kódy pre linky kvízov)
- V metaboxe „🔗 Linky pre hráčov", sekcia **2. Vstup do konkrétneho kvízu**, má teraz každý kvíz tlačidlá **⬇ QR PNG** a **⬇ QR PDF** — stiahne QR kód danej linky.
- PDF = veľký QR vycentrovaný na stred A4 (ideálne na vytlačenie a vystavenie na stanovišti). Filename = **event-typkvízu** (napr. `siemens-music.png`).
- QR sa generuje priamo v prehliadači (žiadna externá služba, funguje offline).

## [1.21.0] - 2026-06-10

### Added (mapový kvíz — nová šablóna „Pohoria sveta")
- Nový dataset **Pohoria sveta** (16 pohorí) pre mapový kvíz typu „plocha", región Svet — pridáva sa v admine cez výber datasetu rovnako ako Pohoria SR / Pohoria Európy.
- Obsah poolu: Himálaj, Karakoram, Hindúkuš, Pamír, Ťan-šan, Altaj, Andy, Skalnaté vrchy, Apalačské pohorie, Sierra Nevada, Aljašské pohorie, Atlas, Južné Alpy, Alpy, Kaukaz, Sierra Madre.
- Reálne obrysy z Natural Earth 10m (public domain), rovnaký prístup ako pri Pohoriach Európy.

## [1.20.5] - 2026-06-09

### Changed (kontrast farebných odpovedí — štýl „bublín" z mapového kvízu)
- Zelené/červené zvýraznenie odpovede hráča (hudobný + filmový kvíz) je teraz **bublina s priesvitným podkladom a orámovaním** a svetlým čitateľným textom — rovnaká konvencia ako v mapovom kvíze. Predtým bol len farebný text, ktorý mal na fialovom pozadí slabý kontrast.

## [1.20.4] - 2026-06-09

### Fixed (hudobný + filmový kvíz — farebné vyhodnotenie odpovedí)
- Na výsledkovej obrazovke hudobného a filmového kvízu sa odpoveď hráča teraz zvýrazní **zeleno (správne) / červeno (nesprávne)**, tak ako to už dávno robí vedomostný kvíz. Doteraz boli neutrálne (biele).
- Hudobný kvíz hodnotí **každú časť zvlášť** — napr. správny interpret je zelený aj keď je pieseň červená. Filmový kvíz zvýrazní názov filmu. Nezadané pole ostáva neutrálne.

## [1.20.3] - 2026-06-09

### Fixed (zamknutý režim — admin vidí všetky tímy)
- V zamknutom tímovom režime sa štatistika prihlásenému adminovi opäť zobrazí celá — **rebríček všetkých tímov pokope**, tak ako pred zavedením zamknutého režimu. Hláška „Štatistika je dostupná len cez tvoj tímový link" sa od teraz ukáže len neprihláseným hráčom bez tokenu. Súkromie medzi tímami ostáva zachované.

## [1.20.2] - 2026-06-08

### Changed (Pohoria Európy — reálne obrysy)
- Dataset **Pohoria Európy** (europe-mountains) prekreslený z hrubých ručných osemuholníkov (8–11 vrcholov/pohorie) na reálne obrysy z **Natural Earth 10m** (public domain) — ~9× viac detailu (1127 vs 121 vrcholov). Pohoria už nie sú hranaté, sledujú skutočné tvary (Ural, Kavkaz, Alpy, …). Názvy všetkých 12 pohorí ostávajú identické → kvíz funguje bez zmeny.

## [1.20.1] - 2026-06-08

### Fixed (zamknutý režim — dotiahnutie)
- Na zamknutej obrazovke (tím už načítaný z tokenu) zmizlo zbytočné tlačidlo **„Pokračovať"** — karty sa zobrazia rovno.
- Link **„Späť na linky s kvízmi"** z výsledku kvízu teraz v zamknutom režime nesie token → vráti na zamknutý hub s predvybratým tímom (predtým viedol na výber tímu, kde sa dal zvoliť cudzí tím).
- Kvízové stránky (music/movies/knowledge/sudoku/mapa) v zamknutom režime berú tím LEN z tokenu — ručné prepísanie `?team=` v URL kvízu sa ignoruje (centrálne v `set_team_code`).

## [1.20.0] - 2026-06-08

### Added (🔒 Zamknutý tímový režim — súkromie medzi tímami)
- Nový prepínač eventu **„🔒 Zamknutý tímový režim"** (General tab). Pri zapnutí sa identita tímu/hráča berie LEN z podpísaného tokenu v linku (`?t=…`) — ručné prepísanie `?team=` v adrese sa ignoruje, takže hráč si nevie zobraziť cudzí tím. Na vstupe sa nezobrazí výber tímu (dropdown), len načítaný tím (read-only). Tímové linky vygeneruješ v metaboxe „🔗 Linky pre hráčov".
- 2-krokový flow: admin vyberie tím (dá tímu jeho token link) → hráč je na zamknutej obrazovke len so svojím tímom, bez možnosti čokoľvek prepnúť.
- Štatistika v zamknutom režime: dostupná len cez vlastný token link (scoped na vlastný tím); bez tokenu sa rebríček vôbec nezobrazí (žiadny únik cudzích tímov). Prepísanie `?team=` sa ignoruje aj tu.

## [1.19.0] - 2026-06-07

### Added (zamknutie vyčerpaných kvízov na vstupnej obrazovke)
- Na vstupnej obrazovke (hub s kartami kvízov) sa kvíz, ktorému tím/hráč už vyčerpal všetky pokusy, zobrazí ako **zamknutá karta** — sivá, neklikateľná, so zámkom 🔒 namiesto šípky. Skóre badge ostáva viditeľné. Hráč sa tak na vyčerpaný kvíz vôbec nepreklikne.

### Fixed (načítanie statusu pri výbere tímu z dropdownu)
- Pri výbere tímu z preddefinovaného dropdownu sa po kliku na „Pokračovať" stránka načíta s tímom v URL → správne sa zobrazí skóre badge, súhrn bodov aj zámky vyčerpaných kvízov. Predtým sa status načítal len pri príchode s tímom už v adrese (napr. návrat z kvízu).

### Changed (krajšia stránka „Pokusy vyčerpané")
- Hláška o vyčerpaní pokusov je teraz vycentrovaná karta v dizajne vstupnej obrazovky (zámok + „Pokusy vyčerpané"), jednotná pre mapový aj ostatné kvízy. Nahrádza holý text „Limit of tries…" pri mapovom kvíze.

## [1.18.25] - 2026-06-03

### Added (REST export movies — voliteľný `production` filter pre selective sync)
- `/export/movies` endpoint v `includes/class-eventkviz-rest.php` prijme nový voliteľný query param **`?production=<slug>`** (sk / cz / zahranicne / rozpravky — všetky reálne termy taxonómie `production`). Pri zadaní vráti len otázky priradené k tomu termu + zúžené `lookup_db.movies` na korešpondujúce CCT záznamy. `lookup_db.productions` ostáva plné (informačné pre admin UI v GC).
- Response envelope obsahuje nové pole `partial_subset` (slug alebo null) — GC ho ukladá do `ek_quiz_library.partial_subset` pre indikáciu „selective sync" stavu.
- Implementačne: `build_movies_export($production = null)` validuje slug proti živej taxonómii (nový term v EK = automaticky validný filter, bez zmeny kódu). `movies_questions($filter)` aplikuje `tax_query` priamo na MySQL JOIN.

### Rationale
Maros 2026-06-03: full movies sync na DEV (90 otázok × videá v MB) preplňuje Supabase egress — pre testing stačí napr. len 2 SK filmy + filter v test kvíze. Selective sync šetrí EK egress, GC storage egress aj DB veľkosť `ek_quiz_library.questions` JSONB. Default (bez param) ostáva full sync — žiadna spätná inkompatibilita pre existujúcich klientov.

## [1.18.24] - 2026-06-01

### Fixed (Direct URL generator — GC integration `cp` placeholder)
- `admin/class-eventkviz-event-links.php` sekcia „Priame URL": URL šablóna teraz obsahuje `&cp={cpId}` placeholder ako string suffix (po `add_query_arg`, aby `{}` neboli URL-encoded). GeoChallenge frontend `app/map/page.tsx` ho automaticky nahradí ID konkrétneho checkpointu pri otvorení v hráčovom modali.
- Týka sa: priame URL pre music/movies/knowledge/sudoku/mapa kvízy + multi-mapa sub-kvízy.
- Description aktualizovaný: vysvetlenie `{cpId}` placeholderu + dôsledok keď chýba („Chyba GeoChallenge integrácie — QR kód neobsahoval väzbu na konkrétny checkpoint").

### Rationale
Maros 2026-06-01 bugreport: hráč otvoril URL+kód úlohu „Národné parky SR" v GC challenge „Biológia 9.roč ZŠ". CP externalUrl bol `https://eventkviz.sk/mapa-quiz/?akcia=chko&mq=2be96b&team=TEAM&user=USER` (chýbal `cp` parameter). Po dokončení mapquizu EK vyhodnotil score (99/100, 9/9 pokusov) ale GC integrácia zlyhala — score sa nezapísal do GC players.progress pre konkrétny checkpoint. Hráč videl error „Chyba GeoChallenge integrácie".

EK plugin parsuje `$_GET['cp']` v `class-eventkviz-musicquiz.php:182` a setuje hidden field `gc_cp` v form. Bez `cp` param-u zostane prázdny → no GC bridge. Fix: admin URL generator vždy pridá `&cp={cpId}` literal placeholder. GC pri render replaces na actual UUID.

## [1.18.23] - 2026-06-01

### Added (nový mapový dataset + template — Chránené krajinné oblasti SR)
- Pridaný dataset **sk-chko** (14 chránených krajinných oblastí SR): Biele Karpaty, Cerová vrchovina, Dunajské luhy, Horná Orava, Kysuce, Latorica, Malé Karpaty, Poľana, Ponitrie, Strážovské vrchy, Štiavnické vrchy, Vihorlat, Východné Karpaty, Záhorie.
- Zdroj: OSM Overpass — relations s `boundary=protected_area` + `protection_title=Chránená krajinná oblasť` v SK area. Outer ways assembled do polygon/multipolygon (5 CHKO sú MultiPolygon: Biele Karpaty 2, Dunajské luhy 5, Kysuce 2, Malé Karpaty 3, Záhorie 2). Simplify N=3, round 5 dp.
- Súbor `public/data/regions/sk-chko.geojson` (~414 KB, 14 features).
- Registrácia datasetu v `admin/class-eventkviz-mapquiz-datasets.php` (slug `sk-chko`, label „Chránené krajinné oblasti SR", region slovakia, geometry polygon, singular „chránenú krajinnú oblasť").
- Vytvorený `mapquiz_template` post „Chránené krajinné oblasti SR" (ID 2141, publish) s pool 14 CHKO — pripravený na pridanie do eventov cez admin UI.
- Nový tool `tools/build-sk-chko.py` (analógia `extend-sk-mountains-tier1-2.py`).
- **Pozn. „Dunajské luhy":** v slovenskej geografickej terminológii a v OSM s malým `l` (apelatívum); v Marošovom screenshote bol uvedený s veľkým `L` (typografická prevencia) — datasetu zachovávame slovenský pravopis.

## [1.18.22] - 2026-05-31

### Fixed (REST mapquiz export — overlay GeoJSONs vždy kompletné)
- `build_mapquiz_export()` v `includes/class-eventkviz-rest.php`: predtým sa do `lookup_db.overlay_geojsons[<region>]` exportovali LEN súbory pre overlay kľúče ktoré boli template-default enabled (cez `$needed_overlays`). Ak template mal default `rivers: false`, sk-rivers.geojson sa nedostal do exportu → GC admin override toggle „Rieky SR" nemohol nič zobraziť (klient nemá dáta). Fix: iterujeme `array_keys( $needed_regions )` a exportujeme VŠETKY súbory z `$overlay_registry[<region>]` — admin per-task override v GC tým vždy má dáta na rendering.

### Rationale
GC MapQuizPlayer (Fáza 1c) povolí adminovi per-task prepísať template overlay defaults (napr. Hrady SR template má rieky default off, ale konkrétna úloha v challenge chce mať rieky on pre vizuálny kontext). EK export musí zahŕňať plnú sadu regionálnych GeoJSONov, nielen "pre-enabled" podmnožinu — inak admin override nefunguje. Maros hlásil „rieky sa nezobrazujú na slepej mape" 2026-05-31.

## [1.18.21] - 2026-05-31

### Changed (REST export mapquiz — rozšírenie pre GC 1:1 player rewrite)
- `build_mapquiz_export()` v `includes/class-eventkviz-rest.php` rozšírený:
  - Per template: pridané polia **quiz_type** (pin/area/line), **overlays** (config object z `_mapquiz_overlays`), **dataset_slug**, **features_source**, **feature_pool**, **custom_features**. GC ich uloží do `mapquiz_library` pre Fáza 1c player rewrite (sidebar layout, číslované markery, sekvenčný flow, overlays render, autosave).
  - Top-level `lookup_db`: pridané **overlay_geojsons** (per región: bundled obsah overlay GeoJSON súborov z `public/data/regions/` ako sk-cities.geojson, sk-regions.geojson, europe-capitals.geojson, atď. — len súbory ktoré sú reálne použité v aspoň jednom template overlays config = needed_overlays dedup). Plus **maptiler_key** z plugin settings (pre real tile vrstvy v GC player).
- **Odstránený pin-only filter** — export teraz vráti aj area/line templaty. GC Fáza 1 player ich nepoužije (filter na strane GC sync alebo player), ale dáta sú v mapquiz_library pre Fáza 3 area/line UI.

### Rationale
GeoChallenge MapQuizPlayer rewrite (Fáza 1c) potrebuje 1:1 parita s EK player viewom — sidebar task list, číslované circular markery, sekvenčný flow, overlay vrstvy podľa admin nastavenia, localStorage autosave, review mode. Tieto vyžadujú overlays config + overlay GeoJSON files + MapTiler key v lookup_db. Bez tohto rozšírenia GC by musel mať hardcoded overlay list = strata flexibility.

Žiadne breaking changes pre existujúce GC sync (Fáza 1a) — staré polia (`pins`, `score_tiers`, `region_geojsons`, atď.) ostávajú. Len pridané nové polia.

## [1.18.20] - 2026-05-31

### Added (REST export — mapquiz typ pre GC sync, Fáza 1)
- Pridaný builder `build_mapquiz_export()` v `includes/class-eventkviz-rest.php`. Endpoint `GET /wp-json/eventkviz/v1/export/mapquiz` (autentif. `X-Eventkviz-Api-Key`) vráti:
  - `questions`: array všetkých `mapquiz_template` (publish) so štruktúrou `{id, name, region, player_detail, max_points, score_tiers, pins[], modified_at}`. Pin štruktúra: `{id, name, hint, description, photo_url, lat, lon}` — photo_url resolve-nuté cez `wp_get_attachment_url`.
  - `scoring`: prázdny stdClass (per-template tiers sú v každom template objekte).
  - `lookup_db.region_geojsons`: GeoJSON FeatureCollection per region embedded z `public/data/regions/{region}.geojson` (slovakia, czechia, europe-countries, world-rivers) — GC ich uloží do svojej DB, žiadny ďalší HTTP fetch pri player view.
- Registry `export_builders()` aktivovaný `'mapquiz'` (predtým commentnutý placeholder).
- Žiadne breaking changes — endpoint je read-only, izolovaný od ostatných WP funkcií.

### Rationale
GeoChallenge (Maroš side-project) ako consumer EK kvízových dát. Music/Movies/Knowledge mali export už dlho, mapquiz pribudol teraz (Fáza 1 pinové šablóny — area mapquiz mimo). GC pri sync prevezme pinové templates + GeoJSON regions + photos (kopíruje do GC Supabase Storage pre offline robust). Translations (SK→EN) GC dorobí cez vlastnú translations dashboard (parita s music/movies/knowledge).

## [1.18.19] - 2026-05-30

### Changed (Okresy SR — zlúčené mestské okresy Bratislavy a Košíc)
- 5 mestských okresov Bratislavy (Bratislava I, II, III, IV, V) zlúčených do **jednej** položky **„Bratislava"** (MultiPolygon, 5 sub-polygons).
- 4 mestské okresy Košíc (Košice I, II, III, IV) zlúčené do **jednej** položky **„Košice"** (MultiPolygon, 4 sub-polygons).
- **Košice-okolie** ostáva ako samostatný (vidiecky) okres — netýka sa zlúčenia.
- Dôvod: pri kvíze bolo 79 okresov zbytočne podrobné, hráč nerozlišuje mestské okresy.
- Pool template **Okresy SR** (post 2140) updatnutý zo 79 na 72 položiek.
- Súbor `public/data/regions/sk-districts.geojson`: 79 → 72 features.
- Nový tool `tools/merge-bratislava-kosice-districts.py` (idempotentný re-run safe).

## [1.18.18] - 2026-05-30

### Added (Pohoria SR — rozšírenie datasetu o 15 nových pohorí, Tier 1+2)
- Mapová šablóna **Pohoria SR** rozšírená z 16 na **31 pohorí**. Pridané (zdroj OSM Overpass, relation s `natural=mountain_range` alebo `boundary=geomorphological-unit` v SK area, outer ways assembled do polygon ringov, simplify každý 3. bod, round 5 dp):
  - **Tier 1 (7):** Považský Inovec, Vtáčnik, Kremnické vrchy, Volovské vrchy, Pieniny, Čergov, Javorníky.
  - **Tier 2 (8):** Bukovské vrchy, Vihorlatské vrchy, Spišská Magura, Krupinská planina, Cerová vrchovina, Čierna hora, Kysucké Beskydy, Žiar.
- Nový tool `tools/extend-sk-mountains-tier1-2.py` (multipolygon-assembly z OSM relation members → uzavretý ring).
- Súbor `public/data/regions/sk-mountains.geojson` narástol z ~116 KB na ~229 KB (31 features).
- Pool template **Pohoria SR** (post 1978) `_mapquiz_feature_pool` updatnutý zo 16 na 31 pohorí.
- **Poznámka „Beskydy":** Moravsko-sliezske Beskydy sú v ČR/PL — pridali sme **Kysucké Beskydy** ako SR ekvivalent (Tier 2).

## [1.18.17] - 2026-05-30

### Added (Rieky SR — rozšírenie datasetu o 15 nových riek, Tier 1+2)
- Mapová šablóna **Rieky SR** rozšírená z 13 na **28 riek**. Pridané (zdroj OSM Overpass, `waterway=river` v SK area, simplify každý 5. bod, súradnice round na 4 dp):
  - **Tier 1 (7):** Laborec, Latorica, Torysa, Orava, Kysuca, Bodva, Belá.
  - **Tier 2 (8):** Rimava, Turiec, Žitava, Myjava, Uh, Cirocha, Rajčianka, Slatina.
- Rajčianka stiahnutá pod OSM menom „Rajčanka" (variant), uložená pod preferovanou slovenskou formou „Rajčianka".
- Nový tool `tools/extend-sk-rivers-tier1-2.py` (analógia k existujúcemu `extend-rieky.php`) — bezpečné re-run, preskakuje rieky ktoré už v geojson existujú.
- Súbor `public/data/regions/sk-rivers.geojson` narástol z ~50 KB na ~141 KB (28 features).

## [1.18.16] - 2026-05-29

### Fixed (Rieky SR — Ondava a Topľa boli na východe vizuálne rozdelené)
- V GeoJSON `sk-rivers.geojson` mali rieky **Ondava** (37 segmentov → 3 geograficky nesúvislé klastre) a **Topľa** (15 segmentov → 3 klastre) gapy v dátach — Leaflet ich kreslil ako 2 paralelné línie, hoci je to jedna rieka. Algoritmus spojil klastre pridaním krátkych konektorov medzi najbližšie endpointy susedných klastrov. Po fixe: Ondava aj Topľa = 1 súvislý klaster.

## [1.18.15] - 2026-05-29

### Changed (štatistika filter ?team=/?user= — IBA daný tím/hráč, nie celý rebríček)
- Pri `?team=X` (alebo `?user=X`) sa zobrazí **iba** daný tím/hráč — jeho riadok v rebríčku tímov s pozíciou + jeho riadok v každej karte „Body po kvízoch". Ostatné tímy sa nezobrazujú. Nadpis sa zmenil na „🏆 Výsledky tímu: X". Pod jeho riadkom kontext „z celkového počtu N tímov / hráčov". (Predtým sa zobrazoval celý rebríček s daným tímom zvýrazneným — Maroš jasne chcel iba ten tím.)

## [1.18.14] - 2026-05-29

### Added (hub — sumár bodov + button na samostatnú štatistiku)
- Pod kartami absolvovaných kvízov sa zobrazí **sumár tvojho skóre** (súčet najlepších bodov zo všetkých už absolvovaných kvízov) a **button „📊 Pozri celú štatistiku"** vedúci na `/eventkviz-statistika/?akcia=X&team=Y` (alebo `&user=Y`). Zobrazí sa len ak tím/hráč už niečo absolvoval.

## [1.18.13] - 2026-05-29

### Added (mapový kvíz — nový dataset „Okresy SR")
- Pridaný dataset `sk-districts` (79 polygónov okresov SR, geoBoundaries ADM2 → ručne mapované SK mená). Registrovaný v `class-eventkviz-mapquiz-datasets.php`. Maroš môže vytvoriť `mapquiz_template` typu „plocha" s týmto datasetom cez admin UI — bude fungovať identicky ako Pohoria/Národné parky/Vodné nádrže.

## [1.18.12] - 2026-05-29

### Added (štatistika — filter pre jeden tím / hráča s zlatým zvýraznením)
- Shortcode `[statistika]` má teraz atribúty `team="..."` a `user="..."` (alebo cez URL `?team=...` / `?user=...`). Pri filtri sa cieľový tím/hráč **zvýrazní zlato** v rebríčku aj v každej karte „Body po kvízoch", subtitle hlavičky uvedie pre koho je pohľad. Použitie: pošli tímu po-eventový link s jeho štatistikou, vidí svoju pozíciu a body v kontexte ostatných.

## [1.18.11] - 2026-05-29

### Added (hub-prehľad — badge ✓ X b pri kvízoch, ktoré tím už absolvoval)
- V hub-prehľade kvízov sa pri kartách, ktoré tím (alebo hráč v režime identifikácie hráčom) **už aspoň raz absolvoval**, zobrazí zelený badge **„✓ X b"** s najlepším dosiahnutým skóre. Pre mapové sub-kvízy podľa konkrétneho `mq` slug → každá mapová šablóna sa kontroluje samostatne.

## [1.18.10] - 2026-05-29

### Added (mapový kvíz — hover nad vybranou plochou/líniou ukáže názov úlohy)
- V mapovom kvíze (line/area) sa pri hover myšou nad vybranou plochou/líniou zobrazí **názov úlohy** (`Vihorlat`), nie skutočný názov plochy (`Malá Fatra`) — aby to nebola nápoveda, čo tam hráč naozaj umiestnil. Analógia s pinmi, kde to už fungovalo cez `placeMarker` tooltip. Pri odznačení sa tooltip odbinduje (a obnoví sa reálny názov len ak je zapnutý overlay „feature labels").

## [1.18.9] - 2026-05-29

### Fixed (startup karta na desktope/fullscreene — vertikálne necentrovaná)
- Startup karta („Pripravte sa na kvíz") pri vysokom okne alebo fullscreene sedela v hornej tretine s prázdnym fialovým pruhom dole. `.ek-startup` má teraz `min-height: 100vh` (+ `100dvh` pre moderné prehliadače, kvôli mobilným toolbarom) → karta sa vycentruje vertikálne aj horizontálne na celej výške viewportu.

## [1.18.8] - 2026-05-29

### Fixed (návrat „Späť na linky s kvízmi" — netreba znova klikať Pokračovať)
- Po kliknutí na „Späť na linky s kvízmi" sa hráč vrátil na hub stránku a aj keď bola identifikácia (tím/meno) v URL, formulár ho nútil znova kliknúť Pokračovať. Hub stránka pri load-e teraz automaticky preskočí startup, ak sú údaje predvyplnené z URL — linky na kvízy sa zobrazia hneď, identifikácia ostane zachovaná.

## [1.18.7] - 2026-05-27

### Changed (štatistika — mapové šablóny samostatne, opravené skórovanie, čistka)
- **Mapový kvíz rozdelený na jednotlivé šablóny** (Hrady, Pohoria, Rieky, Vodné nádrže…) — každá je samostatná karta v „Body po kvízoch" aj samostatná položka v celkovom poradí.
- **Opravené skórovanie máp:** predtým sa všetky mapové šablóny zlievali do jednej a rátal sa len jeden najlepší pokus → tím, čo hral viac máp, bol ukrátený. Teraz sa najlepší pokus počíta za každú mapu zvlášť a sčítava do poradia (mení sa celkové poradie).
- Odstránená mätúca sekcia „Body vrátane opakovaní".
- Rebríček: medaily nahradené jednotnými poradovými číslami (1., 2., 3., … rovnako pre všetkých).

## [1.18.6] - 2026-05-27

### Fixed (CSS cache — štýly sa neaktualizovali bez hard refresh)
- Hlavný `eventkviz.css` sa enqueoval bez verzie → prehliadač ho cachoval natrvalo a zmeny štýlov sa neprejavili (vyžadovali hard refresh). Verzia je teraz `filemtime` súboru, takže cache sa obnoví automaticky pri každej úprave CSS.

## [1.18.5] - 2026-05-27

### Changed (stránka Výsledky/štatistika — nový dizajn v štýle kvízov)
- Štatistika prepracovaná do dizajnu kvízových stránok (fialový gradient + glass karta), po slovensky. Rebríček tímov/hráčov s medailami (🥇🥈🥉, top 3 zvýraznené), body po kvízoch ako prehľadné karty s ikonou a názvom kvíze.
- Zachované/vrátené všetky metriky: poradie, body po kvízoch (najlepší pokus), body vrátane opakovaní, počet hráčov v tímoch (pri identifikácii hráčom).
- CSS enqueue je viazaný na verziu pluginu — po tomto bumpe sa nové štýly načítajú bez nutnosti hard refresh.

## [1.18.4] - 2026-05-27

### Fixed (mapový kvíz — „Pri opakovaní označ správnosť" nefungovalo pri líniách)
- Pri opakovaní mapového kvíze sa správne určené rieky/plochy teraz zobrazia **zelené** + zelená fajka v zozname úloh, sú **zamknuté** (nedajú sa odznačiť) a pri hover ukážu **názov**. Predtým sa pri líniách výbery vôbec neobnovili (kreslili sa skôr, než sa načítala vrstva).
- **Nesprávne** určené sa pri opakovaní neobnovia — hráč ich hľadá znova bez nápovedy farbou či polohou (úmyselne, aby červená neprezrádzala správnu lokalitu).
- Funkcionalitu stanovil Maroš; popis + test plán v EVENTKVIZ-MAPY-DOKUMENTACIA.md („Opakovanie: označ správnosť").

## [1.18.3] - 2026-05-27

### Fixed (vedomostný kvíz — zle čitateľné červené/zelené hodnotenie odpovede)
- Hlášky „Odpoveď nie je správna…" / „Odpoveď je správna…" pri vyhodnotení boli svetločervené/svetlozelené a na svetlom (lavender) pozadí zanikali. Teraz sú v čitateľnom boxe s tmavým textom — čitateľné na ľubovoľnom pozadí (rovnaký princíp ako hlášky úspech/neúspech).

## [1.18.2] - 2026-05-27

### Fixed (filmový kvíz — opakovanie nenačítalo žiadne filmy pri per-production rozdelení)
- Pri evente s rozdelením počtu otázok podľa produkcie (napr. „sk = 10", hlavný „Počet otázok" = 0) sa po kliknutí na „Opakovať kvíz" nenačítal žiadny film a scoring info hlásil „0 filmov". Počet otázok sa pri opakovaní berie z uloženej sady hráča/teamu, takže opakovanie zobrazí rovnaké filmy ako prvé hranie.
- Scoring info filmového kvíze (formulár aj vyhodnotenie) zobrazuje správny počet filmov aj pri per-production rozdelení (predtým „0 filmov").

## [1.18.1] - 2026-05-27

### Fixed (zobrazenie indície pri splnení kvízu — hudba + vedomosti)
- Hudobný a vedomostný kvíz pri vyhodnotení ignorovali nastavenie „Formát pri splnení kvízu" — vždy hľadali obrázok-indíciu, aj keď bol zvolený Text. Pri prázdnom obrázku tak ostal mätúci prázdny box s textom „Vaša ďalšia indícia je:". Teraz rešpektujú Formát (Text / Obrázok) rovnako ako filmový kvíz.
- Ak nie je nastavený žiadny text ani obrázok, prázdna veta „Vaša ďalšia indícia je:" sa už nezobrazí.

### Changed (vyhodnotenie — odstránená nezmyselná motivačná veta)
- Veta „Skús získať čo najviac bodov." sa už pri vyhodnotení nezobrazuje (zostáva len vo formulári pred hraním).

## [1.18.0] - 2026-05-27

### Changed (Music production filter: hardcoded single-select → dynamický multi-select z taxonómie)
- **Hudobný kvíz – Production (typ hudby)** v admin nastaveniach eventu už nie je hardcoded `<select>` s natvrdo zapísanými hodnotami **all / skcz / zahranicne**. Starý term **skcz** bol z taxonómie **production** vymazaný (aktuálne termy: **CZ, SK, Rozprávky, Zahranicne**), takže pôvodný select ponúkal neexistujúcu hodnotu.
- Nový UI = **checkboxy generované dynamicky** z `get_terms(['taxonomy'=>'production','hide_empty'=>false])` (rovnaký vzor ako už majú MOVIES/KNOWLEDGE). Možnosť zvoliť viac produkcií naraz (multi-select). Nič zaškrtnuté = všetky produkcie.
- **Storage**: meta **event_music_production** sa ukladá ako **pole slugov** (predtým single string). Pri save sa sanitizujú len existujúce term slugy (neexistujúce sa zahodia).
- **Eval** (`Eventkviz_MusicForm_Quiz_Class::eventkviz_music_form()`): production filter z jednej hodnoty na **pole slugov** → `tax_query` `terms => [...]`, `operator => 'IN'` (otázky zo všetkých zvolených setov, union). Prázdne pole = bez filtra. Počet otázok (`pocet_otazok_v_sete`) logika zachovaná.
- **Spätná kompatibilita** so starými eventmi (nový helper `normalize_production_filter()`): single `'all'`/prázdne → žiadny filter (všetky); `'skcz'` → `['sk','cz']`; `'zahranicne'`/iný slug → `[slug]`; pole slugov → sanitizované pole. Existujúce eventy (uložené `'all'`/`'zahranicne'`) fungujú bez migrácie.
- Legacy súbor **public/eventkviz-akcie-settings.php** (už nepoužívaný): broken `'skcz' => 2` v movies `number_question_in_production` nahradené `'sk'`/`'cz'`; music `production` zladené na pole formát.
- Pozn.: MOVIES a KNOWLEDGE production/topic filter už boli dynamické (per-term počty otázok z taxonómie) — nemenili sa. Táto zmena dorovnáva MUSIC.
- Verify: `php -l` OK na všetkých zmenených súboroch; `get_terms('production')` vracia CZ/SK/Rozprávky/Zahranicne; WP_Query tax_query IN test — `[sk]`→21, `[sk,zahranicne]`→49 (union), bez filtra→49; unit test `normalize_production_filter` (legacy + array vstupy) prešiel.

## [1.17.0] - 2026-05-27

### Added (REST export — Fáza 2/3: MOVIES + KNOWLEDGE typy + dynamické kategórie pre GC ek-quiz integráciu)
- Rozšírenie read-only REST endpointu **`GET /wp-json/eventkviz/v1/export/<typ>`** o dva nové kvízové typy: **`/export/movies`** a **`/export/knowledge`** (predtým len `/export/music`). Aktivované odkomentovaním v registry `export_builders()` + doimplementovaním builderov v `includes/class-eventkviz-rest.php`.
- **Movies payload**: builder `build_movies_export()` — pool otázok cez `movies_questions()`, scoring defaulty cez `movies_scoring_defaults()`, výber odpovedí cez `split_choices()`, produkčný tag per otázka cez `movies_production()`. `lookup_db` obsahuje **`productions`** = dynamicky načítané termy WP taxonómie `production` (cez nový helper `taxonomy_terms('production')`).
- **Knowledge payload**: builder `build_knowledge_export()` — pool otázok cez `knowledge_questions()`, scoring defaulty cez `knowledge_scoring_defaults()`, výber odpovedí cez `split_knowledge_choices()`, varianty správnej odpovede cez `split_answer_variants()`, tematický tag per otázka cez `knowledge_topic()`. `lookup_db` obsahuje **`topics`** = dynamicky načítané termy WP taxonómie `topic` (cez `taxonomy_terms('topic')`).
- **Dynamické kategórie**: nový statický helper `taxonomy_terms($taxonomy)` vracia všetky termy danej taxonómie ako `[{id, name, slug}]` — GeoChallenge tak dostáva kategórie/filtre dynamicky z WP namiesto hardcodovaného zoznamu (productions pre movies, topics pre knowledge).
- Konzistentné s Fázou 1 (music): čisto aditívne, read-only, žiadny existujúci endpoint ani quiz logika sa nemenila. Tá istá auth (`X-Eventkviz-Api-Key`) a response obálka.
- Verify: `php -l` OK (žiadne syntax chyby).

## [1.16.2] - 2026-05-26

### Changed (hláška o vyčerpaní pokusov v dizajne kvízu)
- Oznam „limit pokusov vyčerpaný" už nie je holý anglický text na bielom podklade — teraz je v štandardnom dizajne kvízu (zaoblený box `.ek-quiz-message`), zdvorilo a po slovensky: „Vyčerpali ste všetky pokusy."
- Platí pre všetky kvízy (hudba, filmy, vedomosti, sudoku, mapa) aj pre finálnu stránku so zadávaním kódov.

## [1.16.1] - 2026-05-25

### Added (music export — `production` tag per otázka pre GC produkčný filter)
- Music export (`GET /wp-json/eventkviz/v1/export/music`) teraz vracia v každej otázke pole **`production`** — slug WP taxonómie `production` priradený danému CPT `questions-audio`. GeoChallenge tak vie replikovať pôvodný EK music quiz filter produkcie (všetky / SK-CZ / zahraničné).
- **Zdroj pravdy** = tá istá taxonómia `production`, podľa ktorej music quiz reálne filtruje pool otázok (`Eventkviz_MusicForm_Quiz_Class::eventkviz_music_form()` → `tax_query` na `taxonomy=production`, `field=slug`). Nie je to runtime heuristika ani odvodenie z krajiny interpreta — je to explicitný term na otázke. Termy: `skcz` (SK a CZ), `zahranicne` (Zahraničné), `rozpravky` (Rozprávky).
- Implementácia: nová statická helper metóda `music_production($qid)` v `includes/class-eventkviz-rest.php` — `wp_get_post_terms($qid,'production',['fields'=>'slugs'])`, vracia slug prvého termu alebo `null` ak otázka nemá priradenú produkciu. Pridané jedno pole do `music_questions()` builderu; žiadna iná logika sa nemenila.
- Verify: `php -l` OK, curl localhost:8888 → 200, `production` prítomné vo všetkých 49 otázkach. Rozloženie aktuálneho poolu: **21× `skcz`, 28× `zahranicne`, 0× null** (žiadna otázka bez produkcie).

## [1.16.0] - 2026-05-25

### Added (REST export endpoint pre GeoChallenge headless port — Fáza 1: MUSIC)
- Nový read-only REST endpoint **`GET /wp-json/eventkviz/v1/export/<typ>`** (teraz `/export/music`) ktorý exportuje kompletné dáta kvízu pre GeoChallenge headless CMS port. Rozšírené v `includes/class-eventkviz-rest.php` (rovnaký `register_rest_route` pattern ako `/search`).
- **Auth**: hlavička `X-Eventkviz-Api-Key` (timing-safe `hash_equals`) proti WP option `eventkviz_export_api_key`. Key sa generuje lazy pri prvom prístupe (`wp_generate_password(48)`). Chýbajúci/zlý key → 401. Neznámy typ → 404. Pre debug existuje aj fallback query param `?api_key=`.
- **Recyklovateľný dizajn**: zdieľaný `export_auth()` + jednotná response obálka (`quiz_type`, `generated_at`, `questions[]`, `scoring`, `lookup_db`) v `export()`. Per-typ data builderi sú v registry `export_builders()` — pridanie movies/knowledge/sudoku/mapquiz = pridať jeden záznam do registry + statickú `build_<typ>_export()` metódu (placeholdery už pripravené v komentári).
- **Music payload**: `questions` = celý pool CPT `questions-audio` (audio URL z meta `media` → `wp_get_attachment_url()`, správny interpret/pieseň cez JetEngine relations 15=artist, 14=song ako `{id,name}`); `scoring` = default hodnoty (both_correct=100, artist_only=50, song_only=50, secondary_artist=0, secondary_song=0); `lookup_db` = celý obsah CCT `jet_cct_artists` + `jet_cct_songs` ako `[{id,name}]` (pre GC autocomplete).
- `get_related_ids()` logika je v exporte zopakovaná staticky (`music_related_id()`) aby endpoint nezávisel na inštancii quiz triedy. **Žiadny existujúci endpoint ani quiz logika sa nemenila** — čisto aditívne, read-only.
- Verify: `php -l` OK, curl localhost:8888 → 200 (validný JSON, 49 otázok / 2722 interpretov / 6068 skladieb), 401 bez/zlý key, 404 neznámy typ.

## [1.15.4] - 2026-05-24

### Fixed (GeoChallenge integrácia — defenzívna kontrola proti broken HMAC kódu pri chýbajúcom `cp` v QR URL)
- BSD 2026 live event reprodukcia: hráč naskenoval QR pre „Mapový kvíz BSD - pohoria SR" (`akcia=bsd2026&mq=pohoria-sr`), URL **neobsahovala** `?cp=...&id=...` parametre. EventKviz form vyplnil hidden inputs len ak boli oba GET parametre prítomné → POST nemal `gc_cp` → `generate_geochallenge_code($score, '')` vyrobil HMAC s payload `"XX:"` (prázdne checkpoint id po dvojbodke). Hráč zadal kód v GeoChallenge appke pre real cpId → GC vyhodnotil HMAC pre `"XX:<real-cp-id>"` → mismatch → „Invalid code". Maros musel CP vymazať z aktivity.
- Fix: `show_geochallenge_return()` v `class-eventkviz-quiz.php` teraz pred volaním `generate_geochallenge_code()` overí konzistenciu — sme za guardom `geochallenge_integration === true`, takže pre validné generovanie kódu OBA POST polia (`$_POST['gc_id']` aj `$_POST['gc_cp']`) musia byť vyplnené. Ak ktorýkoľvek chýba, **NEVOLÁ** generátor kódu. Namiesto kódu renderuje červené chybové UI „Chyba: QR kód neobsahoval väzbu na konkrétny checkpoint. Kontaktuj organizátora — kód by nebol akceptovaný." + `error_log` zápis pre admin debug. Lepšie tvrdo zlyhať s jasnou hláškou ako tichý broken kód v ruke hráča.
- OR check (nie AND) zámerne — pokrýva aj plný BSD scenár (QR vôbec bez GC parametrov). `class-eventkviz-mapaquiz.php` r. 303 má AND podmienku `!empty($gc_id) && !empty($gc_cp)` pre rendering hidden inputov; ak v GET chýba čokoľvek z dvojice, NEvyrenderujú sa **ani** `gc_id` ani `gc_cp` → POST má oba prázdne → OR check ich oba zachytí.
- Pôvodná cesta pre validné `gc_cp` + `gc_id` (= GC appka korektne vygenerovala URL s placeholdermi `{cpId}` aj `{challengeId}`) ostáva nedotknutá.
- `class-eventkviz-mapaquiz.php` (riadky 300-307, čítanie `cp/id/return_url` z GET) ostáva bez zmeny — read-only z GET je správne, problém bol downstream v `show_geochallenge_return()`.

### Added (admin „GeoChallenge integrácia" checkbox — info o povinnom tvare QR URL)
- Pod `event_general[geochallenge_integration]` checkboxom v Edit Event admin pribudla žltá info-poznámka: pripomienka že QR pre GC-integrovaný event musí mať URL v tvare `?akcia=...&mq=...&cp={cpId}&id={challengeId}`, generuje to GC checkpoint editor cez placeholder substitúciu, plus warning že bez `cp` parametru plugin tvrdo zlyhá (no silent broken code).
- Eliminuje riziko že admin omylom natlačí QR len so `?akcia=...&mq=...` (= BSD reprodukcia).

### Coordination
- Pendant zmeny na GeoChallenge strane: per-checkpoint `externalUrl` s placeholder substitúciou (`{cpId}`, `{challengeId}`, `{returnUrl}`) + render „Otvoriť kvíz" tlačidla v `app/map/page.tsx` pre `url-code` task. Joint test plán v `GEOCHALLENGE-BSD-HMAC-COORDINATION.md` (sekcia 3, scenáre 1-4) pred prod deployom.
- TODO neskôr: admin QR builder v EK (`Mapa` tab → pole „GC cpId / challengeId" + „Generuj QR" tlačidlo) pre adminov ktorí chcú QR mimo GC. Nie súčasť tohto fixu.
- Regression scenáre v `EVENTKVIZ-MAPY-DOKUMENTACIA.md` → sekcia „Test plan / regression scenáre (v1.15.4)" (4 scenáre A-D: happy path, plný BSD repro bez params, partial bind, non-GC event negative control) na manuálne overovanie pred PR mergom do `show_geochallenge_return()` alebo `mapaquiz.php` form renderingu. PHPUnit setup zatiaľ nemáme — manual checklist je tým pádom jediný regression net.

## [1.15.3] - 2026-05-21

### Fixed (knowledge/music/movies — retry button sa neponúkal pri `min_body=0`)
- User na BSD evente reportoval: vedomostný kvíz má v admine nastavené **3 pokusy**, ale po prvom pokuse žiadny retry button. Príčina: `min_body_na_postup=0` v kombinácii s fixom z 1.15.0 (`$min_body <= 0` → vždy passed) spôsobila že hráč vždy padol do success vetvy. Retry button bol ale dostupný **len vo fail vetve** → pri `min_body=0` ho hráč nikdy nedostal, hoci podľa `pocet_pokusov` mal nárok na 2 zvyšné.
- Fix: presunutý retry button z fail vetvy MIMO success/fail bloku (mirror logika z mapquizu, ktorý to už robil správne). Retry sa teraz ponúka **vždy** keď `tries_left > 0`, bez ohľadu na passed/failed threshold.
- Aplikované na `class-eventkviz-knowledgequiz.php`, `class-eventkviz-musicquiz.php`, `class-eventkviz-moviesquiz.php`. Mapquiz je nedotknutý (už mal správnu logiku).

## [1.15.2] - 2026-05-21

### Added (mapový kvíz — counter „X / N" v hlavičke zoznamu úloh)
- User na mobile reportoval že keď má 10 úloh a vidí len 5, vyzerá to ako keby vyplnil všetkých 5 — zvyšných 5 jednoducho nevidno. Scrollbar (1.15.1) nestačil — user predsa nevedel že tam zoznam pokračuje.
- Fix: do hlavičky `.ek-mapa-tasks-header` pridaný pill counter `<span class="ek-mapa-counter">5 / 10</span>` (form mode = placed/total, review mode = total). User okamžite vidí koľko ešte chýba bez nutnosti scrollovať.
- Header je teraz **sticky** (`position: sticky; top: 0`) — counter ostáva viditeľný aj keď user scrolluje dolu v zozname. Semi-opaque background s backdrop-filter blur, aby content za ním nepresvitol.

### Added (mapový kvíz — submit confirm „vyplnené X z N úloh, naozaj odoslať?")
- Analogicky s `eventkviz-quiz-form.js` (music/movies/knowledge) pridaný confirm dialog do `eventkviz-mapa-form.js`: ak hráč submituje s placed < total úloh, vyskočí `Vyplnené: X z N úloh. Naozaj odoslať tento pokus? (Ak máš ešte pokusy, môžeš kvíz po vyhodnotení opakovať.)`
- Counter čerpá z interného `taskMarkers` state (presnejší než DOM input scan — mapquiz nemá vyplniteľné inputy v klasickom slova zmysle, len hidden state).
- Ak hráč klikne Cancel → submit sa zruší a môže pokračovať. Ak OK → odoslať tak ako predtým.

## [1.15.1] - 2026-05-20

### Changed (mapový kvíz — viditeľný scrollbar v zozname úloh)
- User reportoval že mapquiz sa na mobile „ťažko naviguje" — zoznam pohorí (`.ek-mapa-tasks`) má `max-height` + `overflow-y: auto`, čo na touch zariadeniach default skryje scrollbar. User nevidel ktorá časť stránky scrolluje a špekuloval kde chytiť pre page scroll.
- Fix: vždy-viditeľný scrollbar na `.ek-mapa-tasks` (`scrollbar-width: thin`, `::-webkit-scrollbar` styling, na mobile 12 px hrúbka pre touch). Plus `-webkit-overflow-scrolling: touch` pre smooth iOS scroll. Konzistentný so semi-transparent vzhľadom mapquiz UI.

## [1.15.0] - 2026-05-18

### Fixed (GeoChallenge kód — skóre nad 1295 sa capovalo)
- `generate_geochallenge_code` predtým capoval score na **1295** (max 2-znakového base36 kódu). User v Pohoria kvíze získal 1600 bodov, GeoChallenge dostal kód `ZZF70` = 1295 bodov.
- Fix: pre score ≤ 1295 ostáva 5-znakový kód (2 score + 3 HMAC, kompatibilita), pre vyšší score 6-znakový (3 score + 3 HMAC, max **46 655**). GeoChallenge `/api/verify-score` decoder (v1.37.0) podporuje obe dĺžky.

### Fixed (kvízy music/movies/knowledge — `min_body_na_postup = 0` ukazoval fail aj pri úspechu)
- Ak admin nastavil `min_body_na_postup` na **0** (= žiadny prah, každý prejde), evaluation v `class-eventkviz-musicquiz.php`, `class-eventkviz-moviesquiz.php` a `class-eventkviz-knowledgequiz.php` vyhodnotil pokus ako fail (`$gained >= 0` strict comparison nefungoval pri 0-bodovom skóre, plus zlá control-flow vetva). Hráč videl „nezískal si dostatok bodov" aj keď reálne dosiahol kompletné skóre.
- Fix: explicit `$_passed_threshold = ($_min_body <= 0) || ($gained_credits >= $_min_body);` — pri threshold ≤ 0 vždy passed.

### Changed (admin „Linky pre hráčov" — filter neaktívnych kvízov)
- V Edit Event meta-boxe „Linky pre hráčov" sa už nezobrazujú linky na kvízy, ktoré nemajú zaškrtnutý `*_quiz_active` toggle. Doteraz admin videl linky napr. na hudobný kvíz aj keď ho v evente nepoužíval — riskoval že omylom zdieľa funkčný-ale-prázdny link.
- Filter sa aplikuje na sekcie 2 (Linky pre tím), 3 (Plain placeholder linky), 3b (Tokenizované linky pre konkrétny tím). Ak event nemá ani jeden aktívny kvíz, meta-box upozorní textovo.

### Changed (wording — „binárne hodnotenie" prepísané user-friendly)
- Texty pre admin/hráča v `class-eventkviz-admin.php` + `class-eventkviz-mapquiz-editor.php` už nepoužívajú technický pojem „binárne". Namiesto „Binárne — trafil/netrafil" sa zobrazuje „Hodnotenie je 'buď trafil — plné body, alebo netrafil — 0 bodov'" / „hráč buď klikne na správnu plochu/líniu (= plné body), alebo nie (= 0)".
- User feedback: „binárne" je nezrozumiteľné pre netechnického klienta.

### Changed (quiz submit — confirm dialog pripomína že po odoslaní sa už nedá opakovať aktuálny pokus)
- `public/js/eventkviz-quiz-form.js` submit confirm pri nevyplnených odpovediach teraz hovorí: „Vyplnené: X z Y odpovedí. Naozaj odoslať tento pokus? (Ak máš ešte pokusy, môžeš kvíz po vyhodnotení opakovať.)" — hráč vie že odoslanie je finálne pre tento pokus, ale opakovanie kvízu (ak je povolené) je samostatná akcia po vyhodnotení.

### Changed (mapový kvíz — mini-mapa: jemnejší zoom + názvy štátov v EU)
- Mini-mapa: zoom je teraz na `featureBounds.pad(0.8)` (= viewport zväčšený o 80% okolo feature) capnutý na region bounds — vidno feature aj okolité štáty/regióny pre geo kontext. Pin mode: bbox ±5° v každom smere.
- EU mini-mapy: na všetkých štátoch sa zobrazujú permanent labely (názov štátu) ako jemné šedé texty s bielym text-shadow. Pomáha hráčovi orientovať sa („Srbsko je tu, vedľa Maďarsko, Rumunsko, Bulharsko..."). Leaflet renderuje len label-y štátov v aktuálnom viewporte mini-mapy.

### Removed (admin „Výsledky" submenu — duplikát so [statistika] shortcode)
- `EventKviz → Výsledky` submenu (admin leaderboard view) odstránené. Funkčne sa duplikoval s verejným `[statistika]` shortcode (`/eventkviz-statistika/?akcia=X`), užívateľ ho nepoužíval.
- Zmazaný `admin/class-eventkviz-leaderboard.php` + require/init v `eventkviz.php` + odkaz v Linky pre hráčov.
- Pre raw záznamy ostáva **JetEngine → Results** (CCT view) — neukončené.
- Pre verejný leaderboard ostáva `[statistika]` shortcode na `/eventkviz-statistika/?akcia=X`.

### Fixed (admin „Výsledky" — „Sorry, you are not allowed" pri zmene akcie)
- Form na zmenu akcie v EventKviz → Výsledky používal default action URL (= current URL) bez explicit `post_type=eventkviz_event`. Po submit GET request URL nemala `post_type` parameter → WP admin router nerozpoznal že submenu patrí pod CPT menu → permission check fail → 403 „Sorry, you are not allowed to access this page".
- Fix: form action explicitne na `admin_url('edit.php')` + hidden `post_type=eventkviz_event` input.

### Added (admin event editor — warning ak pocet_otazok > pool size templatu)
- Pri každom mapquiz sub-quize v Edit Event admin teraz vidí: „📊 Aktuálna šablóna má **X** features v poole." Pri zmene template alebo počtu otázok (set) sa text live aktualizuje.
- Ak `set > pool` zobrazí sa červené upozornenie: „⚠ Set N otázok je väčší než pool M features — hráč dostane reálne len M úloh. Pridaj viac features do šablóny alebo zníž počet otázok." Admin tak nezistí problém až keď ho hráč nahlási.

### Added (scoring info — upozornenie pri new_questions_on_retry)
- Ak template/event má zapnuté `new_questions_on_retry` (= pri každom pokuse nová sada otázok), scoring info text pripojí: „Pri opakovaní môžu byť otázky iné (vyberú sa nanovo)." Hráč vie že nemá zmysel rátať s opakovaním tých istých otázok.

### Fixed (mapquiz scoring info — nesprávny počet úloh keď pool < set)
- Ak admin nastavil `pocet_otazok_v_sete = 10` v evente ale pool template má len 5 features, plugin správne cap-uje na 5 úloh. Scoring info text ale hovoril „označuješ 10 území" (z raw event settings) — nesprávne. Teraz text reflektuje **reálny task count po cappingu** („označuješ 5 území"). max_per_task tiež správne počítaný z reálneho countu.

### Added (admin „Linky pre hráčov" — generátor tokenizovaných liniek)
- Pribudla sekcia **„3b. Tokenizované linky pre konkrétny tím"** v admin Event-Links meta box. Admin zadá kód tímu + (voliteľne) hráča, klikne „Generuj" → JS volá REST `/eventkviz/v1/link-token` a vygeneruje **opaque `?t=...` linky** pre všetky kvízy (vrátane mapquiz sub-kvízov). Každý link má vlastné „Kopírovať" tlačidlo.
- Plain šablóny v sekcii 3 (s `team=TEAM&user=USER` placeholdermi) ostávajú — pre batch ručnú replacement scenarios. Backwards compat zachovaný.

### Added (security — opaque link tokens proti URL podvodom)
- Quiz URLs sa už negenerujú v plain forme `?akcia=velka-noc&team=TEAM&user=USER&mq=0f0ab8`, ale ako opaque token `?t=<base64+HMAC>` (cca 80 znakov). Hráč v URL nevidí team/user/akcia/mq — útočník nedokáže fabricate vlastný token bez prelomenia HMAC secretu.
- **Helper class** `Eventkviz_Link_Token` (`includes/class-eventkviz-link-token.php`) — `encode()` / `decode()` / `build_url()`. Krátke aliasy (a/t/u/m) + URL-safe base64 bez padding + HMAC-SHA256 podpis (10 znakov, dosť pre brute-force resistance).
- **Init hook** (priorita 4) dekóduje `?t=...` a namapuje na `$_GET[akcia/team/user/mq]` — zvyšok plugin kódu ostáva nezmenený.
- **REST endpoint** `/wp-json/eventkviz/v1/link-token` — JS link generátory (show_team_links, show_link_to_quiz) ho volajú async pri kliknutí na „Start" tlačidlo aby získali tokenized URL. Pri REST chybe fallback na plain URL.
- **Retry buttons** vo všetkých kvízoch (music/movies/knowledge/sudoku/mapa) cez `build_retry_url()` automaticky generujú tokenized URL.
- **GeoChallenge return URL ostáva plain** — link do externej GC appky musí byť dekódovateľný GC-om, ten nemá náš secret. Plus `gc_id/gc_cp/gc_return` v retry context tiež ostávajú plain (musia byť čitateľné mimo nášho pluginu).
- **Backwards compat:** staré plain `?akcia=...&team=...&user=...` linky stále fungujú (žiadny mandatory redirect). Bookmark z prv vytvoreného linku sa nepokazí.
- **Secret key** auto-generated pri plugin activate cez `wp_generate_password(64)` do `wp_options` (key `eventkviz_link_secret`). Lazy init pre už-bežiace installs.

### Added (mapquiz — šablóna „Vodné nádrže a jazerá SR")
- 11 najznámejších slovenských vodných plôch — mix umelých priehrad + prírodných plies/sopečných jazier:
  - **Priehrady:** Oravská priehrada, Liptovská Mara, Zemplínska šírava, Domaša (Veľká), Sĺňava, Ružín, Nosická priehrada, Gabčíkovo (Hrušovská zdrž)
  - **Tatry plesá:** Štrbské pleso, Popradské pleso
  - **Vihorlatské sopečné jazero:** Morské oko
- Fetch z OSM Overpass podľa explicitných OSM IDs (presnejšie než name match — vyhne sa duplicit „Morské oko" / „Malé Morské oko"). Pre plesá menšia simplifikácia (n=2) aby si zachovali aspoň základný tvar viditeľný pri SK zoom-e.
- Nový dataset `sk-waterbodies` v registry (area, slovakia, singular „vodnú plochu"), bundle 65 KB.
- Fetch skript: `tools/fetch-sk-waterbodies.py` (idempotentný). Seed skript: `tools/seed-sk-waterbodies.php`.

### Added (mapquiz — vlastné kreslenie polygónov / línií v admin editore)
- **Veľká feature** — admin si môže nakresliť vlastné polygony (area mód) alebo línie (line mód) priamo v admin editore, bez programátora. Doteraz boli polygony/línie možné len cez pre-bundle datasety (pohoria SR, štáty EU…).
- **Toggle „Zdroj features"** v admin editor: `Bundle dataset` (z registry) vs `Vlastné kreslenie`. Pre vlastné sa zobrazí Leaflet.draw mapa s toolbarom (nakresliť polygon/líniu, upraviť vertexy drag-and-drop, vymazať).
- Po nakreslení sa otvorí prompt na názov feature. Pri uložení sa features ukladajú do postmeta `_mapquiz_custom_features` (GeoJSON FeatureCollection). Pool features `_mapquiz_feature_pool` sa pri custom sourci automaticky synchronizuje so všetkými nakreslenými.
- **Edit polish (fáza 2):** drag vertexov, pridanie / odobratie vertexu (cez Leaflet.draw edit tool), vymazanie features (cez delete tool alebo cez ✕ v zozname), premenovanie cez ✎ v zozname.
- **Validácia** v save handler (`sanitize_custom_features()`): GeoJSON štruktúra, polygon ≥3 vertexy, line ≥2 vertexy, sanitizácia názvov. Nevalid features sa odstránia bez chyby.
- **Player JS** podporuje obe sources — pre custom nefetch-uje bundle súbor, ale použije inline `window.ekMapaCustomFeatures` z PHP (passnuté pri renderi container-u).
- Nový postmeta: `_mapquiz_features_source` (`bundle`|`custom`), `_mapquiz_custom_features` (GeoJSON JSON).
- Nový registry helper `Eventkviz_MapQuiz_Datasets::feature_names_for_template()` — vráti pool podľa source (bundle file vs custom postmeta).
- Bundle: `public/vendor/leaflet.draw/` — Leaflet.draw v1.0.4 (MIT licensed), local copy (žiadny CDN runtime dep).

### Fixed (scoring info — centering + margin-bottom)
- Theme alebo Elementor agresívne CSS pre `p` (text-align: left) prepisovalo moje `.ek-scoring-info` rules — text bol zarovnaný vľavo, nie na strede stránky. Wrap z `<p>` na `<div>` + vyššia specificity (`body .ek-scoring-info`, `.ek-quiz .ek-scoring-info`) s `!important` na auto margin. Zväčšený margin-bottom z 28px na 32px aby bol viac oddelený od ďalšieho obsahu (tries banner / sumár bodov).

### Fixed (Pohoria Európy — polygony prerobené ručne pre geo presnosť)
- Natural Earth `ne_10m_geography_regions_polys` má pre niektoré pohoria iba **schematické polygóny** (Tatry = úzky obdĺžnik, Sudety = 3 nesúvislé kúsky, Kaukaz nezobrazený v EU viewport-e). Pre kvíz to bolo nepoužiteľné — hráč napríklad nemohol klepnúť na Kaukaz lebo nebol vidieť.
- Nahradené **ručne definovanými polygónmi** pre 12 top európskych pohorí (Alpy, Karpaty, Pyreneje, Apeniny, Škandinávske vrchy, Tatry, Sudety, Balkán, Dinárske vrchy, Južné Karpaty, Kavkaz, Ural). Polygony sú jednoduché 8-13 bodové aproximácie reálnej rozlohy — geograficky presné + dostatočne veľká plocha na klik.
- Tatry teraz pokrývajú celý hrebeň (Vysoké, Západné, Belianske), nie len úzky pásik. Kaukaz je viditeľný v EU viewport-e.
- Pribudli **Apeniny** (Taliansko) + **Škandinávske vrchy** ktoré v NE neboli; odstránené Malý Kaukaz (slabá geo signál) a Vogézy (príliš malé).
- Pool template „Pohoria Európy" (ID 2111) automaticky aktualizovaný na nový set 12 features.
- Build skript `tools/build-europe-mountains.py` (idempotentný, regeneruje GeoJSON z hardcoded polygons).

### Fixed (kvízy — scoring info čistejšie + lepší kontrast fail/success boxov)
- Mapa scoring info: odstránené zátvorky s vysvetľujúcimi príkladmi („(rieky, železnice…)", „(štát, pohorie, národný park…)") — názov šablóny už hráčovi povie o čo ide, opakovať to v scoring info nedávalo zmysel.
- `.ek-scoring-info` má teraz väčší `margin-bottom: 28px` — predtým bol natlačený na nasledujúce elementy.
- `.ek-quiz-message--success` a `--fail` boxy: solid pozadie + tmavá farba textu (zelená/oranžová) — zaručená čitateľnosť na ľubovoľnom Elementor background (predtým rgba s alpha 0.12 = na fialovom hero pozadí biele písmo zanikalo, „Je potrebné dosiahnuť aspoň 400 bodov…" bolo nečitateľné).
- Inline override štýlov pre mapquiz fail box odstránený — používa globálny CSS.

### Changed (všetky kvízy — human-friendly „scoring info" text nad formulárom + v evale)
- Pre **všetky druhy kvízov** (music / movies / knowledge / sudoku / mapa) sa zobrazí krátky informačný text v ľudskej reči — vysvetľuje za koľko bodov je čo, koľko ich treba na kód a koľko pokusov má hráč. Text je prispôsobený typu kvízu:
  - **Music:** rôzne body za interpreta vs skladbu vs obe spolu k tej istej ukážke.
  - **Movies:** body za správny film vs film priradený k správnej ukážke.
  - **Knowledge:** body za správnu odpoveď + počet otázok.
  - **Sudoku:** rôzne body podľa obťažnosti (ľahká/stredná/ťažká).
  - **Mapa:** varianty pre pin (podľa vzdialenosti — čím bližšie, tým viac), area (klik na plochu — binárne) a line (klik na líniu — binárne).
- Veta o **kóde** sa prispôsobuje: ak `min_body_na_postup > 0` → „Na získanie kódu musíš dosiahnuť aspoň X bodov"; ak = 0 → „Skús získať čo najviac bodov".
- Veta o **pokusoch** sa prispôsobuje: pred kvízom „Máš X pokusov", v evale „Zostáva ti X pokusov" / „Zostávajú ti X pokusy" / „Zostáva ti 1 pokus" / „Toto bol tvoj posledný pokus" — slovenská gramatika 1 / 2-4 / 5+.
- V evale je text v minulom čase („hádal si", „odpovedal si"…).
- Helper `Eventkviz_Quiz_Class::render_scoring_info()` v parent class — jeden formátovač pre všetky kvízy.
- Predchádzajúci verbose box „Ako sa kvíz vyhodnocuje" (s tiers + percentami) v mapquiz evale **odstránený** — nahradený novým friendly textom.

### Added (mapový kvíz — 4 nové šablóny + scoring info box)
- **Pohoria Európy** (area, 12 pohorí z Natural Earth: Alpy, Karpaty, Pyreneje, Tatry, Sudety, Vogézy, Balkán, Dinárske vrchy, Kavkaz, Malý Kaukaz, Ural, Južné Karpaty)
- **Top rieky sveta** (line, 17 riek z NE: Amazonka, Níl, Jang-c’-ťiang, Mississippi, Jenisej, Ob, Lena, Mekong, Volga, Dunaj, Ganga, Niger, Kongo, Paraná, Amur, Mackenzie, Žltá rieka)
- **Top vrcholy sveta** (pin, 15 vrcholov: Everest, K2, Kančendžonga, Aconcagua, Denali, Kilimandžáro, Elbrus, Vinson, Puncak Jaya, Mont Blanc, Matterhorn, Fudži, Mt. Kenya, Mt. Cook, Pico de Orizaba) — Wikipedia fotky.
- **Sopky sveta** (pin, 15 sopiek: Vezuv, Etna, Stromboli, Fudži, Krakatoa, Mt. St. Helens, Kīlauea, Mauna Loa, Yellowstone, Popocatépetl, Eyjafjallajökull, Pinatubo, Cotopaxi, Erebus, Tambora) — Wikipedia fotky.
- World pin templates používajú **world-scale score tiers** (200/500/1000/2000 km) namiesto SR defaults (5/10/20/40 km) — pri svetových vzdialenostiach by tradičné tiers znamenali vždy 0 b.
- Fetch scripty: `tools/fetch-europe-mountains.py` (z Natural Earth `ne_10m_geography_regions_polys`), `tools/fetch-world-rivers.py` (z NE `ne_10m_rivers_lake_centerlines`).
- Seed scripty: `tools/seed-world-peaks.php`, `tools/seed-world-volcanoes.php`, `tools/seed-eu-mountains-world-rivers.php` — všetky idempotentné.

### Added (mapový kvíz — info box „Ako sa kvíz vyhodnocuje" v evale)
- Pred sumarizáciou bodov sa zobrazí vysvetlovací box: max body, počet úloh, rozdelenie, scoring metóda. Pre `pin` mode vypíše tiers s presnými percentami + bodmi (napr. „0–5 km = 100 % = 20 b"). Pre `area`/`line` upozornenie že hodnotenie je binárne. Pomáha hráčovi pochopiť výpočet skóre.

### Fixed (mapový kvíz — chýbal language switcher na desktope)
- `eventkviz_is_eventkviz_page()` (detektor stránok ktoré dostanú floating jazykový prepínač + `eventkviz-page` body class) mal hardcoded zoznam shortcodov, ale mapquiz shortcody `mapa_form_dynamic` + `eval_mapa_quiz_dynamic` v ňom chýbali. Mapquiz stránky nedostávali language switcher renderovaný v `wp_footer`. Doplnené.

### Changed (mapový kvíz — sidebar a eval review čistejšie pre PIN mode)
- Form mode sidebar pre PIN mode už nezobrazuje `description` — bol to spoiler (popis lokality prezrádzal čo hľadať). Zachovaný `hint` (krátka indícia) + `photo` (môže byť súčasťou úlohy „nájdi miesto na obrázku").
- Eval review pre PIN mode: odstránený nadbytočný „🎯 Správna lokalita: <názov>" textový blok — názov je už v hlavičke úlohy aj v sidebare. Zachovaný popis + foto ako vzdelávací box.
- Sidebar review pre PIN mode: predpona „Vzdialenosť: " pred číslo km — predtým „26.31 km · 31 b" bez kontextu, teraz „Vzdialenosť: 26.31 km · 31 b".

### Fixed (mapový kvíz — underline na zoom +/- tlačidlách)
- Theme CSS aplikoval `text-decoration: underline` na `<a>` tagy v contenti, čo postihlo aj Leaflet `+` / `–` zoom ikony. Pridaný `text-decoration: none !important` v hover aj default state.

### Changed (mapový kvíz — výraznejšie zoom controls aj pre mini-mapy)
- Default biele Leaflet zoom `+`/`–` tlačidlá nad svetlým fillom mapy prakticky zanikali. Plugin už mal dark styling pre hlavnú mapu — teraz rovnaký tmavomodrý štýl aj pre mini-mapy v review móde (menšie 26px vs hlavná mapa 32px). Pridaný subtle box-shadow + disabled state (jemne tlmené keď je dosiahnutý max/min zoom).

### Changed (mapový kvíz — mini-mapa interaktívna + lepší default zoom pre SR pin)
- Mini-mapa v review móde má teraz povolený zoom (`+`/`–` controls, double-click, pinch), drag a box-zoom. Hráč si môže dozoom-nuť pre detail miesta. `scrollWheelZoom` zostáva vypnutý — aby kolieskom myši hráč scrolloval stránku, nezasekol sa pri prejazde cez mapu.
- Pin mode default bbox je teraz proporcionálny k regiónu: SR/ČR ±1°/±1.5° (tesný zoom, vidno cca 30 % SR), EU/svet ±5°/±6° (vidno okolité štáty). Predtým bolo univerzálnych ±5°/±6° — pre Slovensko príliš zoom-out.

### Added (mapový kvíz — Národné parky SR migrácia z pin na area)
- Šablóna „Národné parky SR" prepnutá z `pin` (jedna súradnica = stredisko parku) na `area` (polygon kompletného územia parku). Národný park je oblasť, nie miesto.
- Nový dataset `sk-national-parks` (9 SK NP polygónov, stiahnutých z OSM Overpass cez `tools/fetch-sk-national-parks.py`, simplified `n=6`, výsledný bundle 107 KB).
- Hráč teraz klikne na polygon parku ako pri Pohoria SR / Štáty Európy. Binárne hodnotenie (správny park = full body, iný = 0).

### Added (mapový kvíz — mini-mapa: aj nesprávny guess ako červený polygón/línia)
- Mini-mapa pre wrong answer doteraz ukazovala iba zelený správny štát — hráč nevidel kde sám klikol. Teraz sa renderuje aj **červený polygón** (area mód) alebo **červená dashed línia** (line mód) hráčovho výberu, zoom-uje fit-Bounds nad oboma. Hráč vidí súčasne „kde si trafil = červený" a „kde malo byť = zelený".
- Mini-mapa EU labely — zmenšené na 7px + nowrap, aby pri zoom na región/štát nebol crowd.

### Fixed (mapový kvíz — mini-mapa zoom na konkrétnu feature)
- Mini-mapa pre nesprávnu odpoveď v review fitla celý región (kontinent Európy alebo SR bounding box), takže pri malom štáte (Srbsko, Slovinsko, …) alebo riečke bola feature ledva vidno. Teraz mini-mapa po načítaní fit-ne bounds samotnej feature s paddingom 16px a `maxZoom: 7` — dostatočne blízko aby bolo vidno detail, ale stále s nejakým geo kontextom. Pre pin mode auto-bbox ±1.2° okolo súradnice.
- ResizeObserver pri zmene kontajnera tiež refit-uje na feature bounds (predtým by re-set-ol pohľad na celý kontinent).

### Changed (mapový kvíz — area polygon base color zo zelenej na šedú)
- V area móde (štáty, pohoria, NP, …) mali polygóny default zelený fill, čo v review móde kolidovalo so semantikou: zelená = správna odpoveď, červená = nesprávna, oranžová = vybraté v form móde. Base je teraz **neutrálna šedo-modrá** (`#cfd8dc` fill, `#78909c` border), hover **svetlomodrá** (`#90caf9` fill, `#1565c0` border). Zelená/červená/oranžová sú vyhradené pre stavové signály.

### Fixed (mapový kvíz — modrý placeholder rectangle pre Európu)
- Pre Európu sa renderoval modrý ohraničujúci obdĺžnik (placeholder z `europe.geojson` 187B súboru) — kontinentálne regióny ale nemajú jeden polygónový obrys, takže to bolo iba vizuálne rušenie. Teraz pre `europe`/`world` región nerenderujeme outline ani fallback rectangle vôbec; kontextové vodítka dodajú overlays (hranice štátov, hlavné mestá). Placeholder `europe.geojson` zmazaný.
- Mini-mapy v review móde pre Európu teraz renderujú jemné hranice štátov ako outline (jemne šedé, polotransparentné) — užitočnejšie než placeholder obdĺžnik.

### Added (mapový kvíz — EU overlays rendering)
- JS frontend teraz renderuje overlays pre `region=europe`:
  - **Hranice štátov** (`eu_borders`) — jemné šedé polygon outlines s polotransparentným fillom; pre `line` mód (Rieky Európy) dáva geografický kontext kde sú rieky vs štáty. Pre `area` mód (Štáty Európy) nemá zmysel zapínať — feature layer renderuje rovnaké polygóny interaktívne.
  - **Top európske rieky** (`eu_major_rivers`) — modré línie s tooltip názvom (rovnaký style ako SK rivers overlay).
  - **Hlavné mestá Európy** (`eu_capitals`) — pinky s permanentnými labelmi (auto-injected `tier=1` lebo point GeoJSON typicky tier nemá).
- Predtým boli EU overlay checkboxy v admin UI prítomné ale `loadOverlays()` mala early-return pre non-slovakia regióny — admin si zaškrtol overlay, no-op.

### Fixed (mapový kvíz — stale dataset_slug pri prepnutí quiz_type alebo regionu)
- Admin editor a save handler nevalidoval, či uložený `_mapquiz_dataset_slug` zodpovedá aktuálnemu `quiz_type` a `region`. Po prepnutí napr. z `area`+europe na `line`+europe ostal v postmeta starý slug („europe-countries"), čo spôsobilo že frontend načítal polygóny štátov namiesto riek + sidebar zobrazoval „Nájdi štát: Dunaj" namiesto „Nájdi rieku: Dunaj".
- Admin editor: pri renderovaní dropdown a features list teraz validuje, či stored slug match-uje `available_datasets`. Ak nie, fallback na prvý platný dataset.
- Save handler: pred uložením `dataset_slug` overí cez `for_mode_and_region()` že slug má správnu geometry + region. Pri mismatch fallback na prvý platný (alebo prázdno). Pin mode vždy clearuje slug (nerelevantný).

### Fixed (mapový kvíz — threshold check pre GeoChallenge code)
- Mapový kvíz pri vyhodnotení nekontroloval `min_body_na_postup` a hráč dostal kód do GeoChallenge / seed page aj keď nedosiahol prah. Teraz mirror logika z movies/music/knowledge kvízov: ak hráč nedosiahol prah, zobrazí sa „Nezískali ste dosť bodov na postup" + aktuálne získané body, žiadny seed kód ani GeoChallenge return link.

### Added (mapový kvíz — 4 mapové šablóny v novom multi-region móde)
- **Pohoria SR** (migrované z legacy 'Pohoria') — quiz_type `area`, dataset `sk-mountains`, default pool 5 pohorí (VT, NT, MF, VF, MK).
- **Rieky SR** (migrované z legacy 'Rieky') — quiz_type `line`, dataset `sk-rivers`, default pool 8 klasických riek (Dunaj, Váh, Hron, Hornád, Slaná, Ipeľ, Morava, Dunajec).
- **Štáty Európy** (nová) — quiz_type `area`, dataset `europe-countries`, default pool 25 najznámejších európskych štátov (admin si môže doklikávať z 43 dostupných).
- **Rieky Európy** (nová) — quiz_type `line`, dataset `europe-rivers`, default pool 13 top európskych riek (Dunaj, Rýn, Seina, Loira, Volga, Don, Visla, Odra, Labe, Temža, Vltava, Pád, Ebro).
- Migration CLI script `tools/migrate-and-seed-multiregion.php` (idempotentný, opakovateľné spustenie).

### Changed (mapový kvíz — multi-region architektúra: SR / Európa / Svet)
- **Centrálny dataset registry** `Eventkviz_MapQuiz_Datasets` (`admin/class-eventkviz-mapquiz-datasets.php`) — pridanie nového geo datasetu (železnice, cyklotrasy, sopky, ...) je teraz iba pridanie bundle súboru do `public/data/regions/` + jedného entry do registry array. Žiadne zmeny v editor/form/eval kóde.
- **Generic quiz typy:** `mountain` → `area` (označenie územia / oblasti — pohorie, štát, národný park, región), `river` → `line` (čiarový objekt — rieka, železnica, cyklotrasa). Pin mode bez zmeny. Dropdown v šablóne má nové labely.
- **Per-šablóna dataset dropdown:** admin v area/line móde vyberie konkrétny dataset z registry (filtruje sa podľa region + geometry). Pre vybraný dataset sa zobrazí checkbox list features (pool — admin vyberá ktoré features sa budú hádať).
- **Per-region overlay registry:** overlay vodítka (krajské mestá, hranice, rieky atď.) sú teraz definované per-region v registry. SR uvidí 4 SK overlays, Európa uvidí EU overlays (Hlavné mestá Európy 44, Hranice štátov, Top európske rieky 15). Admin sa v editore už neutopí v irrelevantných checkboxoch.
- **Nový toggle „Zobraziť názov priamo na polygone / čiare"** (`feature_labels_permanent`, default OFF) — vždy viditeľný label na features, vhodný len pre úvodné lekcie / deti. Pre súťažné kvízy ostáva vypnuté.
- Bundleované Európa dáta: `europe-countries.geojson` (110 KB, 43 európskych štátov, slovenské názvy), `europe-rivers.geojson` (99 KB, 13 top európskych riek vrátane Dunaja, Rýna, Seiny, Loiry, Volgy), `europe-capitals.geojson` (5 KB, 40 hlavných miest), `europe.geojson` (placeholder bounding box). Source: Natural Earth.
- Postmeta migration safe: legacy templates s `quiz_type=mountain` / `quiz_type=river` musia byť ručne reuložené (vybrať area/line + dataset). Bez migrácie sa post defaultuje na `pin`.

### Fixed (mapový kvíz — quiz typy)
- Hráč pri hover nad riekou/pohorím už nevidí tooltip s názvom (anti-cheat). Pridaný admin overlay checkbox **„Pomôcť hráčovi názvami pri hover"** v sekcii Geografické vodítka — default OFF; admin si môže zapnúť pre vzdelávacie scenáre (žiaci).
- `pocet_otazok_v_sete` v evente sa už rešpektuje aj keď admin zmení nastavenie po prvom hráčskom pokuse. Predtým stale set v DB s pôvodným počtom sa reuse-oval. Stale set detection rozšírená — porovnáva sa aj `count(stored_set) === count_in_set`, nielen členstvo IDs v poole.

### Added (mapový kvíz — quiz typy „rieka" a „pohorie")
- Nový dropdown v mapovej šablóne **„Typ kvízu":** `Hľadanie miest` (pin) | `Označenie rieky` (river) | `Označenie pohoria` (mountain). Per-šablóna jeden mód, žiadne miešanie.
- **Pin mode:** existujúce správanie (admin definuje konkrétne lokácie, hráč klikne kdekoľvek, scoring podľa haversine vzdialenosti + tier).
- **River mode:** pool je pevný — všetkých 8 SK riek z bundle (Dunaj, Váh, Hron, Hornád, Slaná, Ipeľ, Morava, Dunajec). Hráč dostane N náhodných (počet sa nastavuje v evente cez `pocet_otazok_v_sete`).
- **Mountain mode:** admin v šablóne checkboxom vyberie pohoria z 14 bundleovaných (Vysoké/Nízke/Belianske Tatry, Malá/Veľká Fatra, Malé/Biele Karpaty, Strážovské/Štiavnické vrchy, Slovenský raj, Vihorlat, Poľana, Slovenské rudohorie, Branisko). Pool ukladaný v postmeta `_mapquiz_feature_pool`.
- Hráčsky form: sidebar zobrazuje úlohy „Nájdi rieku: Dunaj" / „Nájdi pohorie: Vysoké Tatry". Mapa renderuje features ako interaktívne layers — modré línie pre rieky (weight 4), zelený fill pre pohoria. Hover highlight, klik = označenie. Auto-advance na ďalšiu unanswered úlohu.
- **Binárne hodnotenie:** správna feature = max body za úlohu (`max_per_pin`), nesprávna alebo neoznačená = 0 bodov. Žiadne tier scoring (na rozdiel od pin mode).
- Eval review map: ✅ zelený fill pre správne vybrané, ❌ červený pre nesprávne, šedo-zelený dashed pre nezvolené správne lokácie.
- Anti-cheat: `set` JSON s correct feature names podpísaný HMAC (rovnako ako pin mode); v feature mode je správna odpoveď zo svojej podstaty viditeľná v sidebar úlohe — anti-cheat má obmedzenú silu.
- Bundleovaný dataset `sk-mountains.geojson` (95 KB, 14 pohorí z OSM Overpass + simplification).
- Cities overlay v feature móde má `interactive: false` — neblokuje klik na rieky/pohoria pod nimi.
- Stale question_set z minulých submitov (po zmene `quiz_type` admin) sa detekuje a regeneruje (predtým by hádzal „prázdny set" error).

### Changed (mapový kvíz — base map redesign)
- Zrušený dropdown „Detail pre hráča" v admin šablóne (duplicita s overlay „Kraje"). Postmeta `_mapquiz_player_detail` ostáva pre legacy data, UI ho už nezobrazuje.
- Nová sekcia v šablóne **„Mapové podklady pre hráča"** so 3 checkboxami pre MapTiler tile vrstvy: **Streets** (uličná mapa), **Satelit** (letecké zábery), **Outdoor** (turistická / topografická). Default: žiadna zaškrtnutá → hráč vidí iba obrys regiónu (zero MapTiler tile cost — pôvodné správanie).
- Ak admin povolí ≥1 tile vrstvu, hráč dostane MapTiler tile + Leaflet `L.control.layers` prepínač v rohu mapy (môže prepínať medzi povolenými vrstvami). Ak iba 1 vrstva, prepínač sa neukáže (jedna by aj tak nemala čo prepnúť).
- MapTiler API key passnutý do hráčskej JS cez `wp_localize_script` (`ekMapaCfg.maptilerKey`).

### Added (mapový kvíz — overlay vodítka pre hráča)
- Admin mapová šablóna má novú sekciu „Vodítka pre hráča" so 3 checkboxami: **Mestá** (34 SK miest — krajské + významné okresné), **Kraje** (8 administratívnych krajov), **Rieky** (Dunaj, Váh, Hron, Hornád, Slaná, Ipeľ, Morava, Dunajec). Stav per-šablóna v postmeta `_mapquiz_overlays` (JSON `{cities,regions,rivers}`).
- Render na hráčskej + review mape: krajské mestá s permanentnými labelmi, okresné bodky s tooltipom on hover, kraje ako dashed hranice (jemné), rieky ako modré línie s hover tooltipom názvu.
- Data napevno bundleované v `public/data/regions/sk-{cities,regions,rivers}.geojson` (~30 KB total) — žiadne online fetching pri evente, plne offline.
- Aktívne iba pre `region=slovakia` (v1) — pre `czechia/europe/world` budú treba samostatné datasety.

### Changed (mapový kvíz — admin polish + UX fixes)
- Admin menu poradie pod „EventKviz": Zoznam eventov, Pridaj event, Mapové šablóny, Výsledky, Nastavenia. Mapquiz CPT auto-submenu vypnuté (`show_in_menu=false`), pridané manuálne v správnom poradí.
- Submenu „Výsledky" a „Nastavenia" bez emoji ikon. Tab „Mapa" v Edit Event tiež bez ikony.
- Fix: klik na top-level „EventKviz" hlavičku už nehadzuje 404 — leaderboard hookuje `admin_menu` na priority 15 (po `add_plugin_admin_menu` na priority 10), aby prvý submenu pod parentom ostal „Zoznam eventov" so slugom matching parent.
- Override polia v Mapa tabe majú podstatne podrobnejšie popisy: max body za celý kvíz s výpočtom `max_per_pin = max_body / počet_otázok_v_sete`, score tiers s vysvetlením matchovacej logiky a JSON príkladu.
- Hub stránky `/mapa-quiz/` a `/mapa-quiz-dynamic-evaluation/` automaticky dostávajú page template `elementor_canvas` (žiadny header/footer/sidebar — mapa potrebuje plnú šírku obrazovky). Backfill aj pre existujúce stránky pri každom `admin_init`.
- Player CSS: sidebar zúžený z 280 → 220 px, mapa zvýšená 540 → 600 px. Leaflet zoom controls majú dark background (32 × 32 px, biely text) — predtým biele tlačidlá splývali s outline regiónu.
- Slovakia + Czechia GeoJSON nahradené reálnymi obrysmi z Natural Earth (Slovensko 144 bodov, ~2.7 KB; Česko 232 bodov, ~4.3 KB) — pôvodné placeholdery (~30 bodov) vyzerali ako amorfná škvrna.

### Added (mapový kvíz — Fáza 7, autosave coords)
- Hráčsky form ukladá súradnice pinov do localStorage po každom klik/drag pod kľúčom `ek_autosave:mapa:<akcia>:<team>:<user>:<setHash>` (rovnaký formát ako iné kvízy). Po reload/zatvorení tabu sa zobrazí banner „💾 Obnovené z predchádzajúcej relácie" + tlačidlo „Vymazať a začať znova" ktoré odstráni markery + localStorage záznam. Restore má nižšiu prioritu ako POST `prev_review` (retry-button flow).

### Added (mapový kvíz — Fáza 6, hub integrácia)
- Hub stránka `/eventkviz-vstup/` zobrazuje aj kartu „🗺️ Mapový kvíz" keď je v evente `mapa_quiz_active`. Single-quiz režim cez `?type=mapa` redirectuje rovno na `/mapa-quiz/`.
- Admin metabox „🔗 Linky pre hráčov" pre event zobrazuje aj 3 mapa linky (per-quiz hub, priame URL, smerovanie po výbere).

### Changed (admin menu cleanup)
- Zlúčené 2× „EventKviz" top-level menu (CPT pre eventy + leaderboard) do jedného. Pôvodný leaderboard menu (`eventkviz-leaderboard`) sa stáva submenu pod `edit.php?post_type=eventkviz_event`. CPT Mapové šablóny + Nastavenia tiež presmerované pod tento parent. Žiadny duplikát top-level menu.

### Fixed (mapový kvíz)
- `mapa_settings` sa nekopírovalo do `$this->cAkcia->mapa_settings` v `load_basic_event_settings()` → form vždy zobrazoval „Pre tento event nie je nastavená šablóna mapového kvízu" aj keď template bol nakonfigurovaný. Doplnená kópia v parent class konštrukcii.
- Unicode escapes (`í` atď.) stratili backslash pri ukladaní pinov cez WP magic-quote roundtrip → diakritika v názvoch pinov sa zobrazovala ako „Tematu00edn" namiesto „Tematín". Použité `JSON_UNESCAPED_UNICODE` pri `wp_json_encode` v save handleri. Existujúce poškodené dáta sa môžu opraviť re-saveom šablóny.
- Hráčska Leaflet mapa sa pri inite v Elementor widgete (alebo skrytom tabe) renderovala s nesprávnymi rozmermi → polygon regiónu vyzeral mikroskopicky. Pridaný `ResizeObserver` ktorý zavolá `invalidateSize` + `fitBounds` keď sa kontajner dostane na finálne rozmery.

### Changed (admin menu)
- Top-level admin menu premenovaný z „EventKviz výsledky" na „EventKviz" (krátsí, čistejší ako parent pre submenu).
- Pôvodný leaderboard sa stáva submenu „🏆 Výsledky" pod EventKviz.
- CPT pre šablóny premenovaný z „Mapové kvízy" na „Mapové šablóny" (terminologicky presnejšie — šablóna ≠ inštancia kvízu na evente).

### Added (mapový kvíz — Fáza 5)
- Eval shortcode `[eval_mapa_quiz_dynamic]` (auto-vytvorená stránka `/mapa-quiz-dynamic-evaluation/`) — verifikuje HMAC podpis setu, načíta autoritatívne lat/lon zo `_mapquiz_pins` šablóny (server-side), počíta haversine vzdialenosť pre každú úlohu a aplikuje tier scoring: prvý tier kde `distance_km <= maxKm` určí percent z max_per_task = max_points / count, výsledok zaokrúhlený. Per-event override max_points + score_tiers má prednosť pred template defaultmi.
- Review mapa s dvoma sadami markerov: 🟢 zelený = správna lokácia, 🔴 červený = hráčov odhad. Sidebar zobrazuje vzdialenosť v km + získané body pre každú úlohu. Klik na úlohu → mapa pansne na guess/correct lokáciu.
- Retry button s pre-fill predošlých odpovedí (cez `mark_correctness_on_retry`), invariant `new_questions_on_retry` rešpektovaný (s novým setom sa pre-fill nezobrazí).
- GeoChallenge integration: ak `gained_credits > 0`, ukáže sa GC kód viazaný na checkpoint (existujúci HMAC-bound flow).
- Doplnené `mapa` vetvy v parent helperoch (`check_number_of_tries`, `show_answer`, `send_results_by_email`) v `class-eventkviz-quiz.php`.

### Added (mapový kvíz — Fáza 4)
- Hráčsky form `[mapa_form_dynamic]` — Leaflet mapa s GeoJSON outline regiónu (slovakia/czechia/europe/world), bez tile costu. Hráč klikne na mapu → umiestni numbered pin na aktívnu úlohu, sidebar zobrazuje task list s name/hint/description/photo a status (… pending / ✓ placed). Pin draggable, auto-advance na ďalšiu unanswered úlohu.
- Anti-cheat: server posiela do JS len id/name/hint/description/photo_url; lat/lon zostávajú server-side až do eval.
- Auto-vytvorenie hub stránky `mapa-quiz` cez `Eventkviz_Activator::ensure_hub_pages` (admin_init, idempotent).
- Public enqueue: Leaflet CDN + custom JS/CSS sa loadnu len keď stránka obsahuje shortcode `[mapa_form_dynamic]` alebo `[eval_mapa_quiz_dynamic]` (žiadny dopad na ostatné stránky).

### Added (GeoChallenge integrácia)
- Per-player scoping cez **browser cookie** `eventkviz_gc_<akcia>` (UUID, 6h TTL) — keď GC mode je zapnutý a v URL nie je `cp` (statický QR scenár). Každý browser/device dostane vlastný anonymný session ID. Funguje paralelne s `cp` z URL (priorita: POST gc_cp → GET cp → cookie). Aplikuje sa vo všetkých 4 kvízoch.

### Added (všetky kvízy)
- Banner v hlavičke kvízu „🎯 Zostávajú ti X pokusov z N" / „⚠️ Posledný pokus" — hráč vidí koľko pokusov mu ostáva
- „Opakovať kvíz" tlačidlo má v texte počet zostávajúcich pokusov: napr. „Opakovať kvíz (zostáva 2 pokusy)"

### Changed (admin descriptions, všetky 4 kvízy)
- Toggles „Zobraziť správne odpovede" a „Zobraziť správne uhádnuté odpovede používateľa" majú nové popisy v Zapnuté/Vypnuté štýle s explicitným vysvetlením že **fungujú nezávisle**: prvý kontroluje zobrazenie správneho riešenia, druhý feedback k hráčovým odpovediam. Premenované na „Odhaliť správne odpovede" a „Hodnotenie hráčových odpovedí (správne/nesprávne + body)" pre zrozumiteľnosť. Logika nezmenená.

## [1.4.1] - 2026-05-09

### Added (filmový, hudobný, vedomostný kvíz)
- Admin toggle „Pri opakovaní označ správnosť" — po neúspešnom kvíze sa formulár predvyplní predošlými odpoveďami a každé pole zafarbí: zelené ak bolo správne, červené ak nesprávne. Hudobný kvíz označuje samostatne meno interpreta aj názov piesne. Funguje pre voľne písané odpovede aj výberové dropdowny.

### Added (filmový, hudobný, vedomostný, sudoku kvíz)
- Admin toggle „Pri opakovaní vygeneruj nový set otázok" (default Vypnuté) — kontroluje či hráč pri opakovaní dostane ten istý set ako prvýkrát (odporúčané) alebo nový náhodný set. Ak sú zapnuté oba toggles („označ správnosť" + „nový set"), správnostný highlight sa nezobrazí (nemá zmysel — otázky sú iné).

### Fixed
- Hudobný kvíz vždy generoval nový set otázok pri opakovaní — dlho-stojaci bug v poradí argumentov volania `check_if_questions_set_exists` (interpretovalo `user_code` ako `quiz_type`). Teraz sa pri opakovaní reuse-uje predošlý set ako pri filmovom a vedomostnom kvíze.

### Changed
- „Opakovať kvíz" tlačidlo sa nezobrazí ak hráč vyčerpal posledný povolený pokus — namiesto toho sa zobrazí informácia „Toto bol váš posledný povolený pokus pre tento kvíz." Platí pre všetky 4 kvízy

## [1.4.0] - 2026-05-09

### Fixed (objavené pri testovaní hub stránok)
- `[statistika]` shortcode crashoval pre meta-based eventy (Berlin a novšie) — odstránené redundantné volanie `all_quizes_settings($akcia)` ktoré sa pokúšalo inštanciovať legacy class `Eventkviz_<event>_Class`. `load_basic_event_settings` nastavenia už načíta z post meta
- Per-quiz hub filter (`?type=music`) teraz reálne presmeruje rovno do daného kvízu — JS premenná `singleQuiz` čítala iba shortcode parameter, teraz aj query var
- Posledné hardcoded `localhost:8888` / `eventkviz.sk` v `show_team_links` URL buildoch nahradené `home_url()`
- Hub stránka renderuje selector aj keď má event `Vstupný formulár` vypnutý (legacy flag bol pôvodne pre per-quiz formulár, hub je nový entry point a má vlastnú sémantiku)
- „Späť na linky s kvízmi" tlačidlo na vyhodnotení teraz vedie na hub `/eventkviz-vstup/` namiesto natvrdo `eventkviz.sk/<akcia>/all-team-links-...`
- Posledné hardcoded `localhost:8888` / `eventkviz.sk` v `show_link_to_quiz` (per-quiz selector JS redirect) a v sudoku eval URL nahradené `home_url()`
- Admin metabox „Linky pre hráčov": URL je teraz klikateľný link + samostatné ↗ tlačidlo (otvorenie v novom tabe)

### Added (architektúra)
- **Globálne hub stránky** — `/eventkviz-vstup/` a `/eventkviz-statistika/` sa vytvárajú raz pri aktivácii pluginu (idempotentne); fungujú pre všetky eventy cez `?akcia=` query parameter
- **Admin metabox „🔗 Linky pre hráčov"** v Edit Event — admin vidí ready-to-copy URL na 4 scenáre: hlavný vstup, per-quiz vstup, priame URL bez výberu, štatistika. Každý link má „Kopírovať" tlačidlo

### Changed (architektúra)
- **Pre nové eventy sa už nevytvárajú per-event stránky** („Všetky linky" + „Statistika"). Ich úlohu prevzali globálne hub stránky. Existujúce per-event stránky (legacy ESMT, Berlin, atď.) ostávajú funkčné — žiadna migrácia
- Shortcode `[show_team_links]`, `[show_link_to_quiz]` a `[statistika]` čítajú `akcia` (a `type`) z query vara ak nie sú v shortcode atts — umožňuje hub-page použitie

### Changed (admin)
- Popisky a vysvetlivky v Settings → General prepísané user-friendly štýlom „Zapnuté / Vypnuté + konkrétny use case" namiesto „true/false - blabla"

## [1.3.2] - 2026-05-08

### Security (vedomostný kvíz)
- HMAC podpis question setu (parity s movies/music)

### Changed (vedomostný kvíz)
- Porovnanie odpovedí ignoruje veľkosť písmen a diakritiku — „bratislava" matchne „Bratislava", „hugo" matchne „Hugo"
- Admin môže napísať viac akceptovateľných odpovedí oddelených `|` (napr. `Bratislava|BA|hlavné mesto`) priamo do `correct-answer-1` / `correct-answer-2`
- Hardcoded URL nahradené `home_url()`
- Obrázky otázok majú `loading="lazy"` (rýchlejší prvý paint pri väčšom počte otázok)

### Added (admin)
- Pomôcka „Synonymá pre odpoveď" — sidebar metabox + inline hint pri editácii knowledge otázky vysvetľuje ako oddeliť synonymá znakom `|`

## [1.3.1] - 2026-05-08

### Security (hudobný kvíz)
- HMAC podpis question setu (rovnaká ochrana akú mal filmový od 1.3.0)
- Audio player blokuje download a kontextové menu (`controlsList=nodownload`)

### Changed (hudobný kvíz)
- Hardcoded `localhost:8888` / `eventkviz.sk` URL nahradené `home_url()`
- Polia interpret + pieseň vedľa seba na desktope (50/50), na mobile stackované

## [1.3.0] - 2026-05-08

### Changed (vedomostný kvíz)
- Pri všetkých topic counts = 0 sa otázky rozdelia rovnomerne medzi témy (round-robin) namiesto pure-random — predtým témy s viac otázkami v DB (Movies = 77) dominovali

### Added (všetky kvízy: filmový, hudobný, vedomostný, sudoku)
- Autosave odpovedí do localStorage — po výpadku siete sa rozohraný kvíz obnoví; pri obnove sa znova zobrazí ✓ indikátor pri rozpoznaných filmoch/interpretoch/piesňach
- Progress bar „Odpovedané N/10" hore v kvíze
- Potvrdenie pred odoslaním ak nie sú vyplnené všetky polia
- Admin stránka „EventKviz výsledky" s leaderboardom (celkové + podľa typu kvízu)

### Security (filmový kvíz)
- HMAC podpis question setu — hráč nemôže v DOM zameniť ID otázok pred submitom
- Video player blokuje download a kontextové menu (`controlsList=nodownload`)

### Changed
- Hardcoded `localhost:8888` / `eventkviz.sk` URL nahradené `home_url()` (funguje na staging / inom doméne bez patchovania kódu)

## [1.2.4] - 2026-05-08

### Fixed
- Vedomostný kvíz vykreslí otázky aj keď sú všetky topic counts = 0 (vyberie `Počet otázok v sete` náhodne zo všetkých knowledge otázok — zhoduje sa s pomocným textom v admine)

## [1.2.3] - 2026-05-08

### Changed
- Vyhľadávanie cez AJAX namiesto bulk dumpu celej DB do HTML (rýchlejšie načítanie kvíz stránky)
- Konzistentná diakritika + case-insensitive logika medzi klientom aj serverom
- Tolerantné voči preklepom — „plup fitcion" nájde „Pulp Fiction", „dolar" nájde „Dollar"
- Zvýraznenie písmen v dropdowne ktoré matchujú napísaný text
- Zelený rámček + ✓ indikátor keď je film/pieseň/interpret rozpoznaný
- Server-side cache pre vyhľadávací index (1 hodina)
- Detekcia kvíz stránok cez shortcode v obsahu (slug-fallback ostal cez `eventkviz_quiz_slug_map` filter)

## [1.2.2] - 2026-05-08

### Fixed
- Body sa už pripočítajú aj keď hráč napíše presný názov bez kliknutia na položku v dropdowne (filmový aj hudobný kvíz)

### Changed
- Vyhľadávanie vo formulároch ignoruje diakritiku a veľkosť písmen (napr. „tridsat" nájde „Tridsať")
- Vyhľadávanie hľadá kdekoľvek v názve, nie iba na začiatku (napr. „fic" nájde „Pulp Fiction")
- Filmový kvíz už nenahráva zoznam piesní a interpretov a naopak (rýchlejšie načítanie)

## [1.2.1] - 2026-04-28

### Changed
- Glass-morphism redizajn všetkých user stránok (úvodný formulár, 4 kvíz formuláre, 4 eval stránky)
- Spoločný CSS súbor `public/css/eventkviz.css` (CSS premenné v `:root`)
- Custom dropdown pre výber tímu namiesto natívneho `<select>`
- Skrytá WP site header + duplicitný page title na kvíz stránkach
- Floating jazykový prepínač (top-right) cez Google Language Translator shortcode
- Tlačidlá s vyšším kontrastom (gradient fialová → magenta + biely text)
- Inputy s tmavším pozadím pre lepší kontrast

### Fixed
- Hardcoded DB prefix `pmgonijet_cct_*` nahradený za `$wpdb->prefix . 'jet_cct_*'` — nezávislosť od WP table prefixu
- Warning banner pri skrytých správnych odpovediach teraz čitateľný (amber styled box s ⚠ ikonkou)

## [1.2.0] - 2026-04-28

### Added
- GeoChallenge integrácia — 5-znakový HMAC kód + tlačidlo návratu do GeoChallenge appky pri splnení kvízu
- Admin volba `geochallenge_integration` v event settings
