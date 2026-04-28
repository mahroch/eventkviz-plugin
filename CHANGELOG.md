# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

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
