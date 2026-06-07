---
pdf_version: 0.1
pdf_updated_at: 2026-06-05 18:02 UTC
pdf_filename: EVENTKVIZ-EXPORT-API-v0.1.pdf
---

# EventKviz Export API (GeoChallenge headless port — Fáza 1)

Read-only REST API ktoré exportuje dáta kvízov z EventKviz do GeoChallenge.
Pridané vo verzii **1.16.0** (music `production` tag od **1.16.1**). Zdroj: `includes/class-eventkviz-rest.php`.

## Endpoint

```
GET /wp-json/eventkviz/v1/export/<typ>
```

Implementované typy: **`music`** (Fáza 1).
Pripravené (registry placeholder): `movies`, `knowledge`, `sudoku`, `mapquiz`.

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

## Pridanie ďalšieho typu (recyklácia)

1. Pridať záznam do registry `Eventkviz_Rest_Search::export_builders()`:
   ```php
   'movies' => array( __CLASS__, 'build_movies_export' ),
   ```
2. Implementovať statickú metódu `build_movies_export()` ktorá vráti:
   ```php
   array(
     'questions' => array(...),
     'scoring'   => array(...),
     'lookup_db' => array(...), // napr. 'movies' => [...]
   )
   ```

Auth, routing, obálka aj `generated_at` sú zdieľané — netreba ich znova riešiť.

## Verifikácia (1.16.1, localhost:8888)

- `php -l includes/class-eventkviz-rest.php` → OK
- `200` validný JSON: 49 otázok / 2722 interpretov / 6068 skladieb
- `production` prítomné vo všetkých 49 otázkach — rozloženie: **21× `skcz`, 28× `zahranicne`, 0× null**
- `401` bez kľúča aj so zlým kľúčom; `404` pre neznámy typ (`/export/movies`)
