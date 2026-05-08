# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

## [1.3.0] - 2026-05-08

### Changed (vedomostný kvíz)
- Pri všetkých topic counts = 0 sa otázky rozdelia rovnomerne medzi témy (round-robin) namiesto pure-random — predtým témy s viac otázkami v DB (Movies = 77) dominovali

### Added (filmový kvíz)
- Autosave odpovedí do localStorage — po výpadku siete sa rozohraný kvíz obnoví
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
