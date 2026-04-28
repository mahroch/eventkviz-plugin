# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

## [1.4.1] - 2026-04-28

### Changed
- Warning text "Správne odpovede sa zámerne nezobrazia..." (`.eventkviz_warning_correct_answers`) prerobený z len-červeného textu na výrazný amber warning banner — `rgba(251,191,36,0.15)` pozadie, ľavý 4px amber border, `⚠` ikonka pred textom, padding + rounded corners. Teraz dobre čitateľné na glass-morphism pozadí.

## [1.4.0] - 2026-04-28

### Added
- Glass-morphism dizajn aplikovaný na všetky **evaluačné stránky** kvízov (`/audio-quiz-dynamic-evaluation/`, `/movies-quiz-dynamic-evaluation/`, `/knowledge-quiz-evaluation-dynamic/`, `/sudoku-quiz-evaluation-dynamic/`):
  - Wrapper `.ek-quiz` / `.ek-quiz-content` glass karta
  - Hlavička "Vyhodnotenie hudobného/filmového/vedomostného/sudoku kvízu"
  - Každá otázka v `.ek-question` boxe s gradient číselným badgeom
  - `.ek-user-answer` — štylizovaný riadok s odpoveďou hráča (tmavé pozadie, ľavý border)
  - `.ek-quiz-message--success/--fail` — banner pri úspechu/neúspechu (zelené/ružové pozadie)
  - "Opakovať kvíz" link teraz štýlovaný ako `.ek-quiz-submit` button
- CSS pre eval-specific obsah: `h1/h2/h3/p` typografiu, `.eventkviz_correct_answer`/`_gained_points` (bright green `#4ade80`), `.eventkviz_incorrect_answer`/`_warning_correct_answers` (bright rose `#fb7185`), `.explanation-of-correct-answer`, `.seed_block`/`.seed_for_place` (amber `#fbbf24`).

### Changed
- Refaktored `evaluate_combination_music`, `evaluate_movie`, `evaluate_knowledge`, `evaluate_sudoku` — každá otázka teraz wrapnutá v `.ek-question` (vrátane gradient badge + audio/video containera).
- Sudoku eval texty preložené z angličtiny do slovenčiny.

## [1.3.3] - 2026-04-28

### Fixed
- Floating language switcher nebol viditeľný — používaný plugin je **Google Language Translator** so shortcodom `[google-translator]`, nie `[gtranslate]`. Footer hook teraz skúša `[google-translator]` ako prvý a `[gtranslate]` ako fallback.
- Pridané CSS overrides pre Google Translate špecifické selektory (`.goog-te-gadget`, `.goog-te-menu-value`) — biele písmo, transparent pozadie, schované Google logo.

## [1.3.2] - 2026-04-28

### Fixed
- Text "Odoslať odpovede" na submit tlačidle bol prebíjaný témou/Elementorom na tmavú farbu — pridané `body` prefix pre vyššiu špecificitu + `!important` na `color: #ffffff`, `font-weight: 700`, `background` a `border`. Plus `text-shadow: 0 1px 2px rgba(0,0,0,0.25)` pre extra čitateľnosť. Font size zvýšený 16 → 17px.

## [1.3.1] - 2026-04-28

### Added
- **Floating jazykový prepínač (GTranslate)** — `[gtranslate]` shortcode renderovaný cez `wp_footer` action v pravom hornom rohu na všetkých eventkviz stránkach. Glass-morphism `.ek-langswitch` kontajner (fixed position, blur, dark frosted bg). Štýly pokrývajú GTranslate dropdown aj flag mode.
- Nová helper funkcia `eventkviz_is_eventkviz_page()` (`public/class-eventkviz-public.php`) — zdieľaná pre body_class filter aj footer hook.

### Changed
- **Skrytá celá WP site header** na `body.eventkviz-page` (selektory `header.site-header`, `#masthead`, `.elementor-location-header`, atď.) — kvíz stránky majú clean fullscreen vzhľad bez navigácie/loga, jazykový prepínač nahradzuje funkcionalitu.
- **Vyšší kontrast tlačidla "Odoslať odpovede"** — `--ek-btn-grad` zmenené z svetlého růžovo-červeného `#f093fb → #f5576c` na sýtejší `#9333ea → #db2777` (fialová → magenta). Biele písmo je teraz dobre čitateľné. Shadow farba prispôsobená.

## [1.3.0] - 2026-04-28

### Added
- Glass-morphism dizajn aplikovaný na **filmový (`/merdfghh/`), vedomostný (`/kwersdfzx/`) a sudoku (`/sweertydfd/`)** kvíz — rovnaký vzhľad ako hudobný kvíz: gradient pozadie, glass karta, otázky v `.ek-question` boxoch s gradient číselným badgeom, štýlované inputy/selecty.
- Body class filter `eventkviz-page` (`public/class-eventkviz-public.php`) — automaticky pridaná na akúkoľvek stránku obsahujúcu eventkviz shortcode (`show_team_links`, `*_form_dynamic`, `eval_*_quiz_dynamic`, `statistika`, `show_seed_page`, `show_final_page`). Slúži na scoping CSS pravidiel.
- Nové CSS triedy `.ek-question-text` (text otázky), `.ek-question-hint` (nápoveda).

### Changed
- **Vyšší kontrast inputov** — pozadie inputov zmenené z `rgba(255,255,255,0.2)` (svetlé) na `rgba(0,0,0,0.18)` (tmavé), border lepší (`0.35` opacity), placeholder text z `0.7` na `0.88` opacity. Biele písmo je teraz čitateľné aj na svetlejších oblastiach gradient pozadia.
- **Refactor: spoločný CSS súbor** — všetky `.ek-*` glass-morphism štýly presunuté z inline `<style>` blokov v `class-eventkviz-links.php` a `class-eventkviz-musicquiz.php` do globálneho `public/css/eventkviz.css`. Súbor obsahuje CSS premenné v `:root` (`--ek-bg-grad`, `--ek-btn-grad`, `--ek-card-bg`, atď.) pre jednoduchú zmenu farieb.
- Title-hide CSS scoped na `body.eventkviz-page` selector (predtým neselektívne `body header.entry-header`) — neovplyvňuje stránky bez eventkviz shortcodu.
- `class-eventkviz-moviesquiz.php::show_media_file` — odstránená pevná šírka `width='500'` na `<video>`, teraz `width:100%`.
- `class-eventkviz-knowledgequiz.php::show_media_file` — odstránená inline šírka `80%`, teraz `width:100%` + `border-radius`.

## [1.2.3] - 2026-04-28

### Changed
- Redizajn hudobného kvízu (`Eventkviz_MusicForm_Quiz_Class::eventkviz_music_form`, slug `/aqljk/`) — glass-morphism karta na gradient pozadí, hlavička s nadpisom + podtitulkom, jednotlivé otázky vo vlastných „question card" boxoch s číselným badge (gradient circle), audio playerom a inputmi s placeholdermi.
- Submit button: `<input type="submit">` → `<button type="submit" class="ek-quiz-submit">` so štýlom zhodným s úvodnou kartou.
- jQuery UI autocomplete dropdown štýlovaný do glass-morphism vzhľadu (override `.ui-autocomplete`, `.ui-menu-item-wrapper`).
- Skrytý duplicitný WP page title aj na quiz stránkach.
- Nové triedy: `.ek-quiz`, `.ek-quiz-content`, `.ek-quiz-title`, `.ek-quiz-subtitle`, `.ek-quiz-form`, `.ek-question`, `.ek-question-header/num/label/audio/fields`, `.ek-quiz-submit`.

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
