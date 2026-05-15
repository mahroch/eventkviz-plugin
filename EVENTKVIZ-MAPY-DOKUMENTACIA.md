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
- Per-event tab „Mapa" v Edit Event (Fáza 3) — admin si zatiaľ nemôže priradiť template k eventu.

**Fázy 3-8** — viď chat history / plán
