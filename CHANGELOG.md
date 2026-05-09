# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

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
