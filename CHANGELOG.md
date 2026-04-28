# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

## [1.2.2] - 2026-04-28

### Changed
- Vlastný custom dropdown namiesto natívneho `<select>` pri výbere tímu (`Eventkviz_AllLinks_Quiz_Class::show_team_links` v `includes/class-eventkviz-links.php`) — glass-morphism menu s animáciou, hover/active stavy, klikateľné možnosti, fade-in animácia. Hodnota sa drží v `<input type="hidden" id="inputField2">`, takže existujúca `checkFields()` validácia funguje bez zmeny.
- Klávesnicová ovládateľnosť dropdownu: Enter/Space toggle, Escape close, ArrowUp/ArrowDown navigácia, Enter na výber. ARIA atribúty (`role="combobox"`, `role="listbox"`, `role="option"`, `aria-expanded`, `aria-selected`).
- Click-outside-to-close, animovaná chevron rotácia pri otvorení.
- Skrytý WP page title (auto-generovaný plugin titulok "Všetky linky") na stránkach s `[show_team_links]` shortcode — CSS `body header.entry-header, body .entry-title, body .page-title { display: none !important; }`. Admin listing v WP zostáva nedotknutý.

## [1.2.1] - 2026-04-28

### Changed
- Redizajn úvodnej user stránky `[show_team_links]` (napr. `/akcia/akcia-all-team-links/`) — glass-morphism karta na gradient pozadí, modernejšie inputy s placeholdermi, gradient CTA tlačidlo s `disabled` stavom a hover lift, výstupné quiz-cards s emoji ikonkami (🎵 🎬 🧠 🔢) namiesto plain `<a>` linkov. Mobile-responsive (`@media max-width: 480px`).
  - `includes/class-eventkviz-links.php` — metóda `Eventkviz_AllLinks_Quiz_Class::show_team_links()`
  - JS validácia teraz používa `disabled` atribút na buttone namiesto opacity hacku.
  - Opravené malformované HTML `<center><select ...></center>` pri team-select výbere.

## [1.2.0] - 2026-04-28

### Added
- **GeoChallenge integrácia** — nová admin checkbox `geochallenge_integration` v event settings.
  - Quiz formuláre (knowledge, movies, music, sudoku) prijímajú `id`, `cp`, `return_url` z URL params a ukladajú ich do hidden inputov.
  - Nové metódy `generate_geochallenge_code()` (HMAC SHA256) a `show_geochallenge_return()` v základnej `Eventkviz_Quiz_Class`.
  - Eval stránky zobrazia 5-znakový kód a tlačidlo návratu do GeoChallenge appky pri splnení kvízu.

### Changed
- Nahradený hardcoded DB prefix `pmgonijet_cct_*` za `$wpdb->prefix . 'jet_cct_*'` (resp. `{$wpdb->prefix}jet_cct_*` v interpolovaných SQL stringoch) — plugin je teraz nezávislý od konkrétneho prefixu DB.
  - `public/class-eventkviz-public.php` (artists, songs, movies)
  - `includes/class-eventkviz-musicquiz.php` (artists, songs, movies — aktívny kód; staré komentované bloky ponechané)
  - `includes/class-eventkviz-quiz.php` (results, seeds)
  - `includes/class-eventkviz-statistika.php` (results)
