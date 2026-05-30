# EventKviz — Mapový kvíz: dokumentácia

Tento súbor dokumentuje **mapový kvíz** — typ kvízu kde hráč na blank/outline mape
označuje zadané miesta. Funkčne paralelný s music/movies/knowledge/sudoku
kvízmi, ale s vlastnou architektúrou kvôli komplexnosti (Leaflet, MapTiler,
geo-koordináty, distance scoring).

Dokument sa rozširuje **po každej implementovanej funkcionalite**.

---

## Architektúra (high-level)

**2 vrstvy:**

1. **Top-level menu „🗺️ Map Quizzes"** — globálne šablóny mapových kvízov
   - Admin CRUD: vytvorí šablónu „Hrady SR" / „Elektrárne EU" / atď.
   - Každá šablóna obsahuje mapový region, výber zoznamu pinov, score tiers
   - Šablóna sa **používa naprieč eventmi** (reusable)

2. **Per-event tab „Mapa"** (v Edit Event)
   - Vyber ktorú šablónu použiť
   - Per-event nastavenia (počet pokusov, min bodov, retry, GC integrácia, atď.)
   - Per-event override pre max body a score tiers

**Dáta:**
- CPT `mapquiz_template` — šablóna (1 záznam = 1 šablóna)
- Postmeta `_mapquiz_pins` — JSON: `[{id, name, hint, photo_attachment_id, description, lat, lon}, ...]`
- Postmeta `_mapquiz_region` — `slovakia` / `czechia` / `europe` / `world`
- Postmeta `_mapquiz_player_detail` — `outline-only` / `+regions` / atď.
- Postmeta `_mapquiz_max_points` — default body za pin v plnej tieri
- Postmeta `_mapquiz_score_tiers` — JSON: `[{maxKm, percent}, ...]`

**Per-event meta** (prefix `event_mapa_`):
- `template_id` — odkaz na CPT záznam
- `pocet_otazok_v_sete`, `max_points_override`, `score_tiers_override`
- Plus všetky štandardné: `pocet_pokusov`, `min_body_na_postup`,
  `mark_correctness_on_retry`, `new_questions_on_retry`,
  `zobraz_spravne_odpovede`, `zobraz_spravne_uhadnute_odpovede`,
  `poslat_vysledok_usera_mailom`, `admin_mail`

**Plugin-level:**
- MapTiler API key — uložený v `wp_options` ako `eventkviz_maptiler_api_key`

---

## Mapová technológia

- **Admin editor:** Leaflet + MapTiler **raster tiles** (streets style) — detailný
  podklad pre presné kliknutie/drag pinov.
- **Hráčov pohľad:** Leaflet + **GeoJSON polygón regiónu** (žiadne MapTiler tile
  requests = ušetríme billing). Admin per template vyberie úroveň detailu:
  - `outline-only` — len kontúra regiónu
  - `+regions` — + administratívne hranice
  - (ďalšie levely v budúcnosti: +rivers, +terrain)

---

## URL routing

- Form slug: TBD (vygenerovaný random 5-char — mirror existujúcich `merdfghh`/`aqljk`/...)
- Eval slug: `mapa-quiz-dynamic-evaluation`

---

## Skóring (gradient/stupne)

Per pin sa spočíta vzdialenosť (haversine, WGS84) medzi player guess a real
coord. Body sa udelia podľa **score tiers** — admin definuje thresholdy:

Príklad default tiers:
```json
[
  {"maxKm": 5,  "percent": 100},
  {"maxKm": 10, "percent": 75},
  {"maxKm": 20, "percent": 50},
  {"maxKm": 40, "percent": 25}
]
```
Pri vzdialenosti > max definovaného tier-u = 0 bodov.

**Body za pin** = `max_points * (tier_percent / 100)`. Sum cez všetky piny = celkové body kvízu.

---

## Integrácia s ostatnými mechanikami

Map kvíz používa **rovnaké spoločné helpery** ako music/movies/knowledge/sudoku:
- `check_number_of_tries()` — limit pokusov
- `check_if_questions_set_exists()` — reuse setu na retry (alebo regenerate
  podľa `new_questions_on_retry`)
- `write_results_to_db()` — výsledky do `jet_cct_results`, `quiz_type='mapa'`
- `send_results_by_email()` — admin mail
- `show_geochallenge_return()` — GC kód (HMAC binding na checkpoint)
- `render_retry_button()` — retry s `mark_correctness_on_retry` highlight
- `render_tries_remaining_banner()` — banner zostávajúcich pokusov

---

## Stav implementácie

**Fáza 1 — Infrastructure** ✅ commit hash sa doplní pri push
- [x] CPT `mapquiz_template` registrovaný (`admin/class-eventkviz-mapquiz-cpt.php`). Zobrazuje sa ako submenu „🗺️ Mapové kvízy" pod existujúcim top-level „EventKviz výsledky".
- [x] Plugin Settings stránka (`admin/class-eventkviz-settings.php`) — submenu „⚙️ Nastavenia" pod „EventKviz výsledky". Aktuálne obsahuje len MapTiler API key field (`wp_options` → `eventkviz_options['maptiler_api_key']`). Helper: `Eventkviz_Settings::get_maptiler_key()`.
- [x] Dokumentačný súbor (tento)

**Fáza 2 — Admin pin editor** ✅
- [x] Meta box „🗺️ Mapa + piny" — Leaflet + MapTiler streets-v2 tiles
- [x] Region dropdown (slovakia/czechia/europe/world) — preset center+zoom v JS
- [x] Player detail dropdown (outline-only / +regions) — len uložené, hráčsky renderer príde vo Fáze 4
- [x] Klik na mapu → drop pin (lat/lon z `e.latlng`). Marker draggable.
- [x] Sidebar editor pinu: name, hint, description, fotka (WP media library)
- [x] Pin list pod editorom s klik-to-select, „is-selected" highlight
- [x] Pri zmene name v editore: live update markeru tooltip + list-u
- [x] Pin delete s confirm dialogom
- [x] Meta box „🏆 Bodovanie" — max points + score tiers tabuľka s repeater UI (add/remove riadkov, auto-sort by maxKm)
- [x] Save hook so sanitizáciou + validáciou (region/detail whitelist, max_points clamp 1-9999, pins JSON shape check, tiers sorted by maxKm)
- [x] Nonce + capability check pre security
- [x] CSS súbor `admin/css/mapquiz-editor.css` (panel layouty, hover/selected stavy)
- [x] JS súbor `admin/js/mapquiz-editor.js` (Leaflet init, pin CRUD, photo picker, tiers UI)

**Otestované:** vytvorenie templatu „Hrady SR (test)", klik na mapu vytvorí pin (lat=48.64, lon=19.48), name „Tematín" sa uloží do JSON v postmeta `_mapquiz_pins`. Score tiers majú default 4 stupne. Existujúce kvízy (music/movies/knowledge/hub) ostávajú HTTP 200.

**Známé limity Fázy 2 (riešené v ďalších fázach):**
- Hráčov outline-only renderer ešte neexistuje (Fáza 4) — uložená hodnota `_mapquiz_player_detail` sa zatiaľ nikde nepoužíva.

**Fáza 3 — Per-event tab „Mapa"** ✅
- [x] Nový tab „🗺️ Mapa" v Edit Event UI (`render_mapa_tab` v `admin/class-eventkviz-admin.php`) — pridaný do existujúcej tab navigácie ako 6. tab
- [x] Mapa active toggle s collapse/expand fields container
- [x] Template selector — dropdown s publish-nutými `mapquiz_template` postami
- [x] Per-event settings:
  - `pocet_otazok_v_sete` — koľko pinov v sete (default 10)
  - `max_points_override` (text, prázdne = template default)
  - `score_tiers_override` (JSON textarea, prázdne = template default; UI repeater odložený)
  - `show_entry_form`, `pocet_pokusov`, `min_body_na_postup`
  - `mark_correctness_on_retry`, `new_questions_on_retry`
  - `zobraz_spravne_odpovede`, `zobraz_spravne_uhadnute_odpovede`
  - `poslat_vysledok_usera_mailom` + admin_mail
- [x] Save flow: existujúci generický loop iteruje `$quiz_types` array; pridaný `'mapa'` → automatický save všetkých fields. Checkbox auto-uncheck via existujúci `$quiz_checkbox_keys` map.
- [x] Bool key cast list (`class-eventkviz-quiz.php:113`) pridaný `mapa_quiz_active`. Quiz_types array (`class-eventkviz-quiz.php:97`) pridaný `'mapa' => 'mapa_settings'`.

**Otestované:** Mapa tab vidno v navigácii Edit Event, template dropdown vidí „Hrady SR (test)", expand/collapse funguje, save hook prevzal mapa fields automaticky. Žiadny regression na ostatné kvízy (smoke test → 200).

**Fáza 4 — Hráčsky form** ✅
- [x] Shortcode `[mapa_form_dynamic]` registrovaný v `eventkviz.php` cez `Eventkviz_MapaForm_Quiz_Class::load_shortcodes`
- [x] Trieda `Eventkviz_MapaForm_Quiz_Class extends Eventkviz_Quiz_Class` v `includes/class-eventkviz-mapaquiz.php` — flow:
  1. Load akcia/team/user + `geo_user_code('form')` override (cookie alebo `cp`-based)
  2. `select_from_teams_array` → entry form (existujúci helper)
  3. `check_number_of_tries()` exit ak vyčerpané + render tries banner
  4. Load `template_id` z eventu, validuje template post + `_mapquiz_pins` JSON
  5. `check_if_questions_set_exists`/`new_questions_on_retry` rozhoduje reuse vs regenerate
  6. Random pick `pocet_otazok_v_sete` pinov + uloží set + HMAC sign
  7. **Strip lat/lon** z JS-exposed task dát (klient dostane len id/name/hint/description/photo_url — anti-cheat)
  8. Render sidebar (task list) + map container + hidden inputs `mapa<N>_lat/lon/pin`
  9. POST → `/mapa-quiz-dynamic-evaluation/` (eval shortcode bude Fáza 5)
- [x] Player Leaflet JS (`public/js/eventkviz-mapa-form.js`):
  - **Zero tile cost** — žiadny TileLayer, len GeoJSON outline z `public/data/regions/<region>.geojson`
  - Region presets pre center/zoom/bounds (slovakia, czechia, europe, world)
  - Klik na mapu → umiestni numbered pin na aktívnu úlohu, auto-advance na ďalšiu unanswered
  - Pin draggable → updatuje hidden inputs
  - Task list sidebar: číslo, name, status (… pending / ✓ placed), hint, description, photo
  - `restorePrevReview()` rieši retry/autosave restore
- [x] Player CSS (`public/css/eventkviz-mapa-form.css`) — flex layout 280px sidebar + map, mobile breakpoint 720px
- [x] Region GeoJSON outlines v `public/data/regions/`:
  - `slovakia.geojson` ✅ (zjednodušený obrys, ~30 bodov)
  - `czechia.geojson` ✅ (zjednodušený obrys, ~38 bodov)
  - `europe.geojson` + `world.geojson` — TODO Fáza 5+ (fallback placeholder rectangle zatiaľ)
- [x] Public enqueue (`public/class-eventkviz-public.php`) — `is_mapa_form_page()` helper, Leaflet CDN + custom JS/CSS sa loadnu len keď stránka obsahuje `[mapa_form_dynamic]`
- [x] Hub page auto-create — `Eventkviz_Activator::hub_pages()` zahŕňa `mapa-quiz` → `[mapa_form_dynamic]` shortcode (idempotent na každom `admin_init`)

**Otestované:** Page `mapa-quiz` (ID 1975) auto-vytvorená v DB. Smoke test existujúcich kvízov (music/movies/knowledge/sudoku/vstup) všetky vracajú HTTP 200. Lint: PHP `-l` + `node --check` + JSON validation všetko OK.

**Známé limity Fázy 4 (riešené v ďalších fázach):**
- ~~Eval shortcode `[eval_mapa_quiz_dynamic]`~~ ✅ vyriešené v Fáze 5
- Autosave coords (localStorage) zatiaľ nie je riešený. Fáza 7.
- Europe + World GeoJSON sú placeholder, treba realne polygony pred použitím týchto regiónov.

**Fáza 5 — Eval (vyhodnotenie)** ✅
- [x] Eval shortcode `[eval_mapa_quiz_dynamic]` registrovaný v `eventkviz.php` → `Eventkviz_MapaEval_Quiz_Class::load_shortcodes`
- [x] Trieda `Eventkviz_MapaEval_Quiz_Class extends Eventkviz_Quiz_Class` v `includes/class-eventkviz-mapaquiz.php`
- [x] Flow:
  1. POST → load akcia + GC user override (`geo_user_code('eval')`)
  2. `verify_question_set_signature` (HMAC) — chráni proti zmene `set` v POST
  3. `check_number_of_tries('mapa')` — exit ak limit vyčerpaný
  4. Load template + `_mapquiz_pins` JSON → mapa pin_id → pin (autoritatívne lat/lon)
  5. Resolve max_points (event override → template default → 100) + tiers (event override → template default → 4 default tiers)
  6. Per task: haversine vzdialenosť medzi guess (POST mapa<N>_lat/lon) a correct (server-side pin), find tier, percent × max_per_task
  7. `gained_credits = sum(points)`, write_results_to_db (insert), send_email, show_seed, show_geochallenge_return
  8. Render: review map (red guess + green correct pin) + textual per-task summary + retry button s previous_state (ak mark_correctness_on_retry + ! new_questions_on_retry)
- [x] Haversine: `Eventkviz_MapaEval_Quiz_Class::haversine_km($lat1,$lon1,$lat2,$lon2)` — sférická formula s polomerom 6371 km
- [x] Tier scoring: `percent_for_distance($km, $tiers)` — pre vzostupne usporiadané tiers vráti percent prvého, kde `$km <= maxKm`, inak 0
- [x] Parent class doplnenia (`class-eventkviz-quiz.php`):
  - `check_number_of_tries`: pridaný `elseif($place == 'mapa') $pocet_pokusov = mapa_settings['pocet_pokusov']`
  - `show_answer`: pridaný `elseif($quiz_type == 'mapa')` čítanie z `mapa_settings`
  - `send_results_by_email`: pridaný `elseif($quiz_type == 'mapa')`
- [x] Player JS (`public/js/eventkviz-mapa-form.js`) dual-mode:
  - Form mode: `window.ekMapaTasks` + interaktívna klikací renderer (existujúci flow)
  - Review mode: `window.ekMapaReview` + `data-review="1"` na kontajneri → read-only markery (red guess, green correct), sidebar zobrazuje distance + body per task
- [x] Public enqueue: `is_mapa_form_page()` rozšírený na detection oboch shortcodov (`mapa_form_dynamic` + `eval_mapa_quiz_dynamic`) — Leaflet + JS/CSS sa loadnu aj na eval stránke
- [x] CSS (`public/css/eventkviz-mapa-form.css`) doplnený o `.ek-mapa-task-result--ok/--miss` + `.ek-review-banner`
- [x] Hub page auto-create: `mapa-quiz-dynamic-evaluation` → `[eval_mapa_quiz_dynamic]` v `Eventkviz_Activator::hub_pages()`

**Otestované:** Eval page (ID 1976) auto-vytvorená. Smoke test (curl) všetkých kvízov + hub stránok → HTTP 200. Lint PHP/JS/JSON OK. End-to-end browser test odložený — formulár sa otestuje keď event Druzba bude mať nastavený template.

**Známé limity Fázy 5 (riešené ďalej):**
- Retry-with-previous-state musí byť odskúšaný v prehliadači (autosave pre mapa quiz Fáza 7)
- GeoChallenge fail-flow: ak `gained_credits === 0`, neukáže sa GC kód. Konzistentné s ostatnými kvízmi.

**Fáza 6 — Hub integrácia + admin menu cleanup** ✅
- [x] `Eventkviz_OneLink_Quiz_Class::show_link_to_quiz` (`/eventkviz-vstup/?type=mapa`) — pridaný `mapa` do `$type_to_slug` → JS redirect na `/mapa-quiz/?team=...&user=...&akcia=...` keď `mapa_quiz_active`
- [x] `Eventkviz_AllLinks_Quiz_Class::show_team_links` (`[show_team_links]` shortcode) — pridaný `link5` + single-quiz check + karta `🗺️ Mapový kvíz` v multi-quiz view + per-quiz redirect
- [x] Admin metabox „🔗 Linky pre hráčov" (`Eventkviz_Event_Links_Admin::QUIZ_SLUGS`) — pridaný `mapa => mapa-quiz` do 3 sekcií: per-quiz hub, priame URL, label v štatistike
- [x] Admin menu cleanup: zlúčené 2x „EventKviz" top-level menu do jedného (`edit.php?post_type=eventkviz_event` zostáva ako jediný parent). Leaderboard, Mapové šablóny CPT a Nastavenia teraz registrované cez `add_submenu_page` pod `edit.php?post_type=eventkviz_event`. Žiadny duplikát.

**Otestované:** Hub `?akcia=druzba` → zobrazuje 3 aktívne karty: Hudobný, Vedomostný, Mapový. Po výbere tímu klik na kartu otvorí cieľový kvíz. Admin Linky pre hráčov zobrazuje mapa link vo všetkých sekciách. Admin menu má len jeden „EventKviz" so submenu: Zoznam eventov, Pridaj event, Všetky mapové šablóny, 🏆 Výsledky, ⚙️ Nastavenia.

**Fáza 7 — Autosave coords do localStorage** ✅
- [x] Po každom klik/drag → `writeHidden()` zavolá `saveCoordsToStorage()` ktorá uloží `mapa<N>_lat/lon/pin` do localStorage pod kľúčom `ek_autosave:mapa:<akcia>:<team>:<user>:<setHash>` (rovnaký formát ako iné kvízy v `eventkviz-quiz-form.js`)
- [x] `setHash` = `shortHash` zo serializovaného `set` JSON-u → keď admin pri retry vygeneruje nový set, autosave kľúč sa zmení a stale dáta sa neaplikujú
- [x] Restore-on-load: `restorePrevReview()` najprv volá `restoreFromStorage()` ktorá doplní hidden inputs z localStorage (POST `prev_review` má prednosť — neprepíše už existujúce hodnoty)
- [x] Banner „💾 Obnovené z predchádzajúcej relácie. [Vymazať a začať znova]" sa zobrazí keď restore prebehol. Klik na tlačidlo zmaže markery, hidden inputs, localStorage kľúč
- [x] localStorage sa neclearje pri submit (úmyselne — retry/reload vie obnoviť stav)

**Otestované:** Klik na 2 lokácie → reload page → banner sa zobrazil, oba piny obnovené v správnych pozíciách, sidebar tasks ✓. Klik „Vymazať" → piny zmazané, banner zmizol, localStorage prázdne.

**Známé limity:**
- Autosave nevypisuje custom `ek-quiz-form.js`-style restored hint na other quizoch — tu má vlastný hint špecifický pre mapa flow.
- Žiadny TTL na localStorage — zostáva dokým prehliadač nezmaže.

**Fáza 8 — Quiz typy „rieka" a „pohorie" + base map redesign** ✅
- [x] Nový dropdown „Typ kvízu" v mapovej šablóne (`_mapquiz_quiz_type`): pin / river / mountain. Per-šablóna jeden mód.
- [x] **Pin mode** — existujúce (admin definuje lat/lon, scoring haversine + tier).
- [x] **River mode** — pool fixný = všetkých 8 SK riek z bundle. Admin len nastaví `pocet_otazok_v_sete` v evente.
- [x] **Mountain mode** — admin checkboxom vyberie subset z 14 bundleovaných pohorí (`_mapquiz_feature_pool` JSON). Hráč dostane N náhodných.
- [x] **Binárne scoring pre feature mode**: `points = max_per_pin` ak guess_id == correct_id, inak 0.
- [x] Player JS detekuje `data-quiz-type` na containeri:
  - `pin`: `onMapClick` placeMarker, hidden inputs `mapaN_lat/lon/pin`
  - `river`/`mountain`: `loadFeatureLayer` → render features s click handlerom, `onFeaturePick` ukladá hidden input `mapaN_feature`
- [x] Eval class — quiz_type-aware scoring + render summary (zelený correct, červený wrong v review).
- [x] Cities overlay `interactive: false` v feature móde aby neblokovali klik na rieky/pohoria.
- [x] Stale question_set detection: keď admin zmení quiz_type, stored set s pin UUID neexistuje v novom poole → treat_as_new + write update.
- [x] Base map redesign: zrušený dropdown „Detail pre hráča". Nová sekcia „Mapové podklady" so 3 tile checkboxami (Streets / Satelit / Outdoor) — ak ≥1 zvolená, hráč dostane MapTiler tile + L.control.layers prepinač. Inak iba blanket outline (zero tile cost).
- [x] Geografické vodítka split: `cities_main` (8 krajských) + `cities_regional` (26 okresných) ako samostatné checkboxes; ostatné (regions, rivers) nezmenené.
- [x] Bug fix: `+regions` value v dropdown sa po sanitize_key strácala — premenovaný na `outline-regions`. (Dropdown už nie je v UI, ale postmeta key migration.)

**Bundleované dáta SK (`public/data/regions/`):**
- `slovakia.geojson` — outline 144 bodov (z Natural Earth)
- `czechia.geojson` — outline 232 bodov
- `sk-cities.geojson` — 34 miest (8 krajských tier=1 + 26 okresných tier=2), hand-curated
- `sk-regions.geojson` — 8 administratívnych krajov SR (z NE)
- `sk-rivers.geojson` — **28 riek** (od v1.18.17). Pôvodných 13 (Dunaj, Váh, Hron, Hornád, Slaná, Ipeľ, Morava, Dunajec, Nitra, Topľa, Ondava, Bodrog, Poprad) z NE/OSM. V1.18.17 dopnené **15 ďalších** z OSM Overpass (`waterway=river` + area.sk, simplify N=5, round 4 dp): **Tier 1** — Laborec, Latorica, Torysa, Orava, Kysuca, Bodva, Belá. **Tier 2** — Rimava, Turiec, Žitava, Myjava, Uh, Cirocha, Rajčianka, Slatina. Rajčianka v OSM ako „Rajčanka", v datasete pod preferovanou SK formou. Tool: `tools/extend-sk-rivers-tier1-2.py`.
- `sk-mountains.geojson` — 14 pohorí SR (z OSM Overpass + simplification, ~95 KB)

**Fázy 1-8 hotové.** Plugin je production-ready pre pin / river / mountain quiz typy na Slovensku.

---

## GeoChallenge integrácia — kontrakt URL parametrov + defenzívna kontrola (v1.15.4)

EventKviz vie generovať **HMAC-podpísaný kód** na konci kvízu pre GeoChallenge appku — kód v sebe nesie skóre **+ binding na konkrétny GC checkpoint** (anti cross-checkpoint reuse). Aby HMAC payload sedel na oboch stranách, vyžaduje URL kontrakt:

**Povinné GET parametre na entry URL** (čo GC appka generuje pre `url-code` task):
- `akcia=<event-slug>` — ktorý EK event
- `mq=<mapquiz-slug>` — ktorý konkrétny mapový kvíz v evente (pri multi-quiz evente)
- `cp=<gc-checkpoint-uuid>` — **GeoChallenge checkpoint ID** (binding pre HMAC)
- `id=<gc-challenge-uuid>` — GC challenge ID (kontext)
- `return_url=<encoded-url>` — voliteľný deep-link späť do GC appky

Príklad: `https://eventkviz.sk/akcia/?akcia=bsd2026&mq=pohoria-sr&cp=633f3325-ef06-453a-960b-c5b73ebdf790&id=b0000002-0000-0000-0000-000000000002&return_url=https%3A%2F%2Fgeochallenge.sk%2Fmap%2F...`

**Flow:**
1. `class-eventkviz-mapaquiz.php` (~r. 300-307) prečíta `cp/id/return_url` z `$_GET` a ak sú **oba** `gc_id` aj `gc_cp` set, vyrenderuje hidden inputs `gc_id`, `gc_cp`, `gc_return` do form-u.
2. Po submit + eval, `Eventkviz_Quiz_Class::show_geochallenge_return($gained_credits)` číta tieto hidden inputs z `$_POST` a volá `generate_geochallenge_code($score, $checkpoint_id)` ktorý vyrobí kód `<scorePart><HMAC>` s payload `"<scorePart>:<gc_cp>"`.
3. Hráč zadá kód v GC appke pre real cpId → GC volá `/api/verify-score`, prepočíta HMAC nad rovnakým payload-om a kód buď akceptuje alebo odmietne.

**Defenzívna kontrola (BSD 2026 regression guard, v1.15.4):**
`show_geochallenge_return()` beží len ak event má `geochallenge_integration === true` (early return inak). Sme teda za guardom, takže pre validné generovanie kódu **oba** POST polia `gc_id` aj `gc_cp` musia byť vyplnené. Ak ktorýkoľvek z nich chýba (`empty($gc_id) || empty($gc_cp)`), funkcia **NEvolá** `generate_geochallenge_code` a namiesto kódu vyrenderuje červený error block:

> Chyba: QR kód neobsahoval väzbu na konkrétny checkpoint. Kontaktuj organizátora — kód by nebol akceptovaný.

Plus `error_log('[eventkviz] GC integration active but gc_id/gc_cp missing in POST — refusing to generate code')` do PHP error logu pre admin debug.

OR check (nie AND) zámerne pokrýva aj plný BSD scenár — `class-eventkviz-mapaquiz.php` r. 303 má AND podmienku pre rendering hidden inputov, takže ak v GET URL chýba `cp` aj `id`, **ani jeden** z `gc_id`/`gc_cp` sa nevyrenderuje do formu → POST má oba prázdne. AND defenzíva v `show_geochallenge_return` by to neodhalila, OR áno.

**Prečo to robíme:** počas BSD 2026 live eventu (2026-05-23) hráči naskenovali QR ktorý mal len `?akcia=bsd2026&mq=pohoria-sr` (bez `cp/id`). Plugin tichu vygeneroval kód s HMAC nad payload `"XX:"` (prázdne checkpoint id). GC ho odmietol ako „Invalid code", Maros musel CP vymazať z aktivity. Pôvodne admin musel QR generovať ručne — `class-eventkviz-admin.php` (`geochallenge_integration` checkbox) teraz pod checkboxom zobrazuje žltú info-poznámku o povinnom tvare URL aby admin to nikdy neurobil omylom.

**Open items:**
- Per Maros plán: admin QR builder v EK (`Mapa` tab → polia „GC cpId / challengeId" + tlačidlo „Generuj QR kód") — eliminuje human error pri printed QR. Nie súčasť v1.15.4 fixu (sledované ako TODO).
- Žiadne autom. fail-safe na strane GC, ak admin nezadá `externalUrl` s placeholdermi pre `url-code` task — GC strana to rieši v paralelnom fixe (per-checkpoint `externalUrl` substitúcia + render „Otvoriť kvíz" tlačidla v `app/map/page.tsx`). Joint test plán: `GEOCHALLENGE-BSD-HMAC-COORDINATION.md`, sekcia 3 (scenáre 1-4).

### Test plan / regression scenáre (v1.15.4)

Plugin **nemá PHPUnit setup** (per projektový stav), preto regressiu pre BSD HMAC defensive check overujeme manuálne v MAMP-e (`http://localhost:8888/eventkviz/`). Pred akoukoľvek úpravou `show_geochallenge_return()` v `includes/class-eventkviz-quiz.php` alebo form renderingu hidden inputov `gc_id`/`gc_cp`/`gc_return` v `includes/class-eventkviz-mapaquiz.php` (~r. 300-307) treba prebehnúť všetky 4 scenáre nižšie. Predpoklad: existuje testovacia akcia so slugom `bsd2026`, mapový kvíz so slugom `49faea`, event má v admine zaškrtnutý toggle „GeoChallenge integrácia" (`event_general[geochallenge_integration] = true`), event má aspoň 1 mapquiz s validnou question pool-ou.

**Príprava pre každý scenár:**
- `tail -f /Applications/MAMP/logs/php_error.log` v separátnom termináli (sledovať `[eventkviz]` error_log zápisy).
- Open URL v incognito (alebo po `localStorage.clear()`) aby nešiel resume mód.
- Vyplniť kvíz „naplno" alebo `min_body_na_postup`-konformne aby `gained_credits > 0` (kontrola red-box vs green-box logiky nezávisí od skóre, ale jasnejšie sa overuje keď kvíz prejde).

#### Scenár A — happy path (full URL contract)

- **URL:** `http://localhost:8888/eventkviz/mapa-quiz/?akcia=bsd2026&mq=49faea&cp=633f3325-ef06-453a-960b-c5b73ebdf790&id=b0000002-0000-0000-0000-000000000002`
- **Postup:** vyplniť mapquiz → klik „Odoslať odpovede" → potvrdiť confirm dialog ak vyskočí.
- **DOM check (post-submit page):**
  - PRÍTOMNÉ: `<div class="geochallenge-return">` so zeleným borderom (`#4caf50`), nadpis „GeoChallenge kód", 6-znakový alfanumerický kód v monospace, optional „Návrat do GeoChallenge" tlačidlo (ak bol `return_url` v GET).
  - NEPRÍTOMNÉ: `<div class="eventkviz-gc-error">` (red error block).
- **PHP error_log:** žiadny `[eventkviz] GC integration active but gc_id/gc_cp missing` zápis.
- **Form sanity (DevTools → Network → POST request body):** `gc_id`, `gc_cp` oba neprázdne, optional `gc_return`.

#### Scenár B — plný BSD repro (no GC params v URL)

- **URL:** `http://localhost:8888/eventkviz/mapa-quiz/?akcia=bsd2026&mq=49faea` (BEZ `cp` a BEZ `id`)
- **Postup:** vyplniť mapquiz → „Odoslať odpovede".
- **DOM check:**
  - PRÍTOMNÉ: `<div class="eventkviz-gc-error">` s červeným borderom (`#c62828`), nadpis „Chyba GeoChallenge integrácie", text „Chyba: QR kód neobsahoval väzbu na konkrétny checkpoint. Kontaktuj organizátora — kód by nebol akceptovaný."
  - NEPRÍTOMNÉ: `<div class="geochallenge-return">` (žiadny zelený kód-box, žiadny 6-znakový kód, žiadne „Návrat do GeoChallenge" tlačidlo).
- **PHP error_log:** PRÍTOMNÝ `[eventkviz] GC integration active but gc_id/gc_cp missing in POST — refusing to generate code` (presne 1×).
- **Form sanity:** POST body NEOBSAHUJE polia `gc_id` ani `gc_cp` (mapaquiz.php r. 303 AND check zabránil renderingu hidden inputov), takže `$_POST['gc_id']` a `$_POST['gc_cp']` sú oba `''` po `sanitize_text_field` → OR check (r. 728) triggeruje red block.

#### Scenár C — partial bind (cp bez id)

- **URL:** `http://localhost:8888/eventkviz/mapa-quiz/?akcia=bsd2026&mq=49faea&cp=633f3325-ef06-453a-960b-c5b73ebdf790` (s `cp`, BEZ `id`)
- **Postup:** vyplniť mapquiz → „Odoslať odpovede".
- **DOM check:** identicky ako scenár B — PRÍTOMNÝ red error block, NEPRÍTOMNÝ green code block.
- **PHP error_log:** PRÍTOMNÝ rovnaký `[eventkviz] GC integration active but gc_id/gc_cp missing` zápis.
- **Form sanity:** mapaquiz.php r. 303 AND check (`!empty($gc_id) && !empty($gc_cp)`) padá lebo `$gc_id` je prázdny — ani `gc_cp` sa nevyrenderuje do hidden inputu, hoci v URL bol. Symetria fixu: aj keby sa `gc_cp` vyrenderovalo, OR check v `show_geochallenge_return` by ho zachytil.
- **Variant C' (id bez cp):** rovnaké URL ale `&id=...&` namiesto `&cp=...&` — identický expected output (red error block + error_log).

#### Scenár D — non-GC event (negative control)

- **URL:** `http://localhost:8888/eventkviz/mapa-quiz/?akcia=<INÝ-EVENT-SLUG>&mq=<MQ-SLUG>` kde event NEMÁ zaškrtnutý `geochallenge_integration` checkbox v admine.
- **Postup:** vyplniť mapquiz → „Odoslať odpovede".
- **DOM check:**
  - NEPRÍTOMNÉ: `<div class="geochallenge-return">` (zelený kód-box) AJ `<div class="eventkviz-gc-error">` (red error block) — show_geochallenge_return early-returnuje na r. 712-714 pred OR checkom.
  - PRÍTOMNÉ: štandardný eval výsledok kvízu („Získal si X bodov..."), žiadny GC reference whatsoever.
- **PHP error_log:** žiadny `[eventkviz]` zápis (žiaden code path s error_log sa neexekvuje).
- **Účel:** garantuje že defenzívna kontrola sa NEspúšťa pre non-GC eventy a netriggeruje false-positive red error blocks pre štandardné EK akcie.

**Pri PR-e meniacom `show_geochallenge_return()` alebo `mapaquiz.php` form rendering:** prejdiť všetky 4 scenáre, screenshot z DevTools (Elements panel + Network POST body) priložiť k PR description. Ak ktorýkoľvek scenár padne, fix nie je ready-to-merge.

## Hláška o vyčerpaní pokusov (v1.16.2)

Keď hráč/team vyčerpá povolený počet pokusov daného (sub-)kvízu, `Eventkviz_Quiz_Class::check_number_of_tries()` vráti `false` a vypíše oznam. Predtým to bol holý anglický text bez markupu (`Limit of tries for this quiz was reached…`) — vizuálne vytrhnutý z dizajnu kvízu. Od v1.16.2 je oznam obalený do štandardného `.ek-quiz-message ek-quiz-message--fail` boxu (rovnaký dizajn ako success/fail správy ostatných kvízov), slovensky a zdvorilo.

- **Default oznam** (music/movies/knowledge/sudoku/mapa): `class-eventkviz-quiz.php` v `check_number_of_tries()` — „Vyčerpali ste všetky pokusy. Pre tento kvíz ste využili všetky povolené pokusy (N). Ďakujeme za hru!"
- **Finálna stránka** (zadávanie kódov/seeds): `class-eventkviz-finalpage.php:120` cez `$alternative_text` — analogický text pre „zadanie správnych kódov".

### Test plán (regression)

1. Nastav v admine kvíz s nízkym `pocet_pokusov` (napr. 1).
2. Odohraj kvíz toľkokrát, aby si limit prekročil (vrátane „posledného pokusu").
3. Pri ďalšom načítaní/odoslaní očakávaj: zaoblený box v dizajne kvízu (nie holý text), tučný nadpis „Vyčerpali ste všetky pokusy.", zdvorilý slovenský text, čitateľný na fialovom/tmavom Elementor pozadí.
4. Over default kvíz (hudba/filmy/vedomosti/sudoku/mapa) aj finálnu stránku so seedmi.
5. Negatívne: hráč s ešte zostávajúcimi pokusmi NESMIE vidieť tento box (vidí normálny formulár/eval).

## Opakovanie: „Pri opakovaní označ správnosť" (mark_correctness_on_retry)

**⚠️ Túto funkcionalitu stanovil Maroš 2026-05-27 — pri ďalších úpravách rešpektuj toto zadanie, nie skoršie správanie.**

Keď je v admine pre mapový (sub-)kvíz zaškrtnuté **„Pri opakovaní označ správnosť"** (a NIE „Pri opakovaní nový set"), a hráč po vyhodnotení klikne **„Opakovať kvíz"**, opakovací formulár sa správa takto:

- **Správne** určené feature (rieky/plochy) z predošlého pokusu sa **obnovia a sú ZELENÉ** na mape + **zelená fajka** v zozname úloh. Sú **zamknuté** — user ich nemôže odznačiť ani prepísať (kliknutie na ne aj v zozname aj na mape sa ignoruje).
- **Nesprávne** určené feature sa **NEobnovia** — žiadna farba na mape, žiadna fajka v zozname. Predošlý nesprávny výber sa zahodí (vyčistí sa aj hidden input `mapaN_feature`). User danú úlohu **hľadá znova od nuly**, bez nápovedy farbou či polohou.
- **Hover** myšou nad **správne** určenou feature ukáže jej **názov** (tooltip). Len nad správnymi — nesprávne/neurčené ostávajú bez nápovedy.

**Rozdiel oproti vyhodnoteniu (eval review):** vyhodnotenie ukazuje OBOJE — zelené správne aj červené nesprávne (hráč vidí ako dopadol). Opakovanie ukazuje LEN zelené správne (nesprávne háda znova). Červená sa pri opakovaní zámerne nepoužíva.

**Kde to žije (JS, `public/js/eventkviz-mapa-form.js`):**
- `restorePrevReview()` — obnoví do `taskMarkers` len `prev_mapaN_correct === '1'`, nesprávne vyčistí.
- `applyFeatureStyle()` + `featureSelectedStyle(isCorrect)` — zelená pre správne, oranžová pre nový výber.
- `bindCorrectTooltips()` — hover názov nad správnymi.
- `renderTaskList()` — zelená fajka + `is-review-correct`, bez × pre zamknuté správne.
- PHP (`class-eventkviz-mapaquiz.php`) posiela `mapaN_feature` + `prev_mapaN_correct` v hidden inputoch; logika je čisto na strane JS.

### Test plán (regression)
1. Mapový kvíz (line/area), v admine zaškrtni „Pri opakovaní označ správnosť", NEzaškrtni „nový set", počet pokusov > 1.
2. Odohraj: časť úloh urči **správne**, časť **nesprávne**, niektoré nechaj prázdne. Odošli.
3. Klikni „Opakovať kvíz". Očakávaj: správne = zelené + zelená fajka + zamknuté + hover názov; nesprávne = bez označenia (háda znova); prázdne = bez označenia.
4. Skús kliknúť na zelenú (správnu) — NESMIE sa odznačiť. Skús nesprávnu úlohu doplniť — nový výber je oranžový.
5. Pin typ: pri opakovaní sa správanie zatiaľ nemenilo (mimo tohto zadania).
