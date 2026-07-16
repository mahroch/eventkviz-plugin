---
pdf_version: 0.1
pdf_updated_at: 2026-06-05 18:02 UTC
pdf_filename: EVENTKVIZ-EXPORT-API-v0.1.pdf
---

# EventKviz Export API (GeoChallenge headless port)

Read-only REST API ktoré exportuje dáta kvízov z EventKviz do GeoChallenge.
Pridané vo verzii **1.16.0** (music `production` tag od **1.16.1**). Zdroj: `includes/class-eventkviz-rest.php`.

> ⚠️ **Aktualizované 2026-07-16** (plugin je na 1.24.2) — pôvodná verzia tohto dokumentu (napísaná pri 1.16.1)
> tvrdila že implementovaný je len `music` a zvyšné typy sú „pripravený placeholder". To už neplatí — `movies`,
> `knowledge` a `mapquiz` sú dávno reálne implementované (viď registry nižšie). Len `sudoku` ostáva
> neimplementovaný (zakomentovaný v registry).

## Endpoint

```
GET /wp-json/eventkviz/v1/export/<typ>
```

**Registry** (`Eventkviz_Rest_Search::export_builders()`, `includes/class-eventkviz-rest.php`):

```php
'music'     => array( __CLASS__, 'build_music_export' ),
'movies'    => array( __CLASS__, 'build_movies_export' ),
'knowledge' => array( __CLASS__, 'build_knowledge_export' ),
'mapquiz'   => array( __CLASS__, 'build_mapquiz_export' ),
// 'sudoku' => array( __CLASS__, 'build_sudoku_export' ),   ← NEIMPLEMENTOVANÉ
```

Implementované typy: **`music`, `movies`, `knowledge`, `mapquiz`**.
Neimplementované: **`sudoku`** (žiadny builder, `/export/sudoku` vráti `404 ek_export_unknown_type`).

Voliteľný query param **`production`** — production/movies filter (pozri „Movies" nižšie); ostatné typy ho ignorujú. Obálka navyše nesie **`partial_subset`** (aplikovaný filter slug alebo `null` pri full exporte — GC si to ukladá do `ek_quiz_library.partial_subset` na UI indikáciu).

### Autentifikácia

Hlavička:

```
X-Eventkviz-Api-Key: <kľúč>
```

- Kľúč je uložený ako WP option **`eventkviz_export_api_key`**.
- Generuje sa automaticky (lazy) pri prvom prístupe na endpoint — `wp_generate_password(48)`, alfanumerický.
- Programové získanie / vynútené vygenerovanie: `Eventkviz_Rest_Search::get_or_create_export_key()`.
- Rotácia kľúča: zmazať option (`delete_option('eventkviz_export_api_key')`) → pri ďalšom requeste sa vygeneruje nový. GC stranu treba updatnúť.
- Porovnanie je timing-safe (`hash_equals`).
- Fallback pre manuálny debug: query param `?api_key=<kľúč>` (hlavička má prednosť).

### Odpovede

| Stav | Kód |
|------|-----|
| Validný kľúč + známy typ | `200` |
| Chýbajúci / zlý kľúč | `401` |
| Neznámy typ | `404` |

## Response obálka (zdieľaná všetkými typmi)

```json
{
  "quiz_type": "music",
  "generated_at": "2026-05-25T19:27:01+00:00",
  "questions": [
    {
      "id": 167,
      "audio_url": "https://.../23.mp3",
      "correct_artist": { "id": 501, "name": "Daft Punk" },
      "correct_song":   { "id": 2603, "name": "Around the World" },
      "production":     "zahranicne"
    }
  ],
  "scoring": {
    "both_correct": 100,
    "artist_only": 50,
    "song_only": 50,
    "secondary_artist": 0,
    "secondary_song": 0
  },
  "lookup_db": {
    "artists": [ { "id": 15, "name": "..." } ],
    "songs":   [ { "id": 14, "name": "..." } ]
  }
}
```

### Music — význam polí

- `questions` — celý pool CPT `questions-audio` (status `publish`), zoradené podľa ID.
  - `audio_url` — `wp_get_attachment_url(get_post_meta($qid,'media',true))`; `null` ak chýba.
  - `correct_artist` / `correct_song` — JetEngine relations (15=artist, 14=song) → CCT `{id,name}`; `null` ak chýba väzba.
  - `production` — slug WP taxonómie `production` priradenej otázke (`wp_get_post_terms($qid,'production',['fields'=>'slugs'])[0]`); `null` ak otázka nemá produkciu. Hodnoty: `skcz` (SK a CZ), `zahranicne` (Zahraničné), `rozpravky` (Rozprávky). **Toto je tá istá taxonómia, podľa ktorej music quiz filtruje pool** (`eventkviz_music_form()` → `tax_query` na `taxonomy=production, field=slug`), takže GC vie repliovať pôvodný produkčný filter (všetky / SK-CZ / zahraničné). Nie je to odvodenie z krajiny interpreta — je to explicitný term na otázke.
- `scoring` — default hodnoty z music scoring configu (`render_music_tab()`):
  - `both_correct` = correct artist + correct song (default 100)
  - `artist_only` = správny interpret, zlá pieseň (default 50)
  - `song_only` = správna pieseň, zlý interpret (default 50)
  - `secondary_artist` / `secondary_song` = správna odpoveď na nesprávnej pozícii (default 0, nie sú v admin UI)
- `lookup_db` — celý obsah CCT `jet_cct_artists` (stĺpec `artist`) + `jet_cct_songs` (stĺpec `song`) ako `[{id,name}]`, pre GC autocomplete.

> Pozn.: `scoring` je default na úrovni plugin-u, **nie** per-event. Per-event override `music_settings` sa v exporte zámerne nepoužíva (export je pool-level kontrakt, nie per-akcia).

### Movies — význam polí

- `questions` — pool CPT `questions-movies` filtrovaný voliteľným `production` query paramom (validovaný proti živej taxonómii `production` — neznámy slug = ignorovaný, full export; back-compat pre staré volania bez parametra).
- `scoring` — `{ "movie_correct": 100 }` (zhodné s `render_movies_tab()` defaultom `event_movies_credits_corr_movie`).
- `lookup_db.movies` — CCT `jet_cct_movies` (`original_title`); pri filtrovanom (`partial_subset`) exporte zúžené len na filmy reálne odkazované z vrátených otázok (šetrí prenos). `lookup_db.productions` — VŽDY plná lista termov (informačná, aj pri subset synce).
- `partial_subset` — aplikovaný `production` slug alebo `null` (full export).

### Knowledge — význam polí

- `questions` — pool CPT `questions-knowledge`: `prompt` (post title), `prompt_html` (post content, GC sanitizuje), `image_url` (featured image), `hint`, `choices` (meta `choices-for-correct-answer`, `;`/`,` split, `null` = voľný text input), `correct_variants` (meta `correct-answer-1`+`correct-answer-2`, pipe-split), `explanation`, `topic` (prvý term taxonómie `topic`).
- `scoring` — `{ "answer_correct": 100 }`.
- `lookup_db.topics` — dynamicky z reálnych termov taxonómie `topic` (nie hardcoded enum) — pre GC admin multi-select kategórií.
- Žiadny autocomplete lookup (na rozdiel od music/movies) — knowledge nemá entity-relation pole.

### Mapquiz — význam polí

- `questions`/templates — pool CPT `mapquiz_template`: `quiz_type` (`pin`/`area`/`line`, meta `_mapquiz_quiz_type`), `region` (default `slovakia`), `player_detail`, `max_points` (default 100), `score_tiers` (vzdialenostné pásma % bodov, default 4 tiery 5/10/20/40 km), `pins` (id/name/hint/description/photo_url/lat/lon), `overlays` config + `dataset_slug` + `feature_pool` (**pozri CLAUDE.md „feature pool sync" gotcha** — pool musí byť ručne rozšírený pri pridaní GeoJSON features, inak sa nikdy nevylosujú).
- Export vracia VŠETKY quiz_type (pin+area+line) — GC player UI (Fáza 1) filtruje len na `pin`, area/line čakajú na budúcu GC fázu.

## Pridanie ďalšieho typu (recyklácia — napr. sudoku, jediný zatiaľ chýbajúci)

1. Pridať záznam do registry `Eventkviz_Rest_Search::export_builders()`:
   ```php
   'sudoku' => array( __CLASS__, 'build_sudoku_export' ),
   ```
2. Implementovať statickú metódu `build_sudoku_export()` ktorá vráti:
   ```php
   array(
     'questions' => array(...),
     'scoring'   => array(...),
     'lookup_db' => array(...),
   )
   ```

Auth, routing, obálka aj `generated_at` sú zdieľané — netreba ich znova riešiť.

## Verifikácia (historická, 1.16.1 — len music, pred pridaním movies/knowledge/mapquiz)

- `php -l includes/class-eventkviz-rest.php` → OK
- `200` validný JSON: 49 otázok / 2722 interpretov / 6068 skladieb
- `production` prítomné vo všetkých 49 otázkach — rozloženie: **21× `skcz`, 28× `zahranicne`, 0× null**
- `401` bez kľúča aj so zlým kľúčom; `404` pre neznámy typ (`/export/movies`)
