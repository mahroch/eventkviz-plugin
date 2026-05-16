# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

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
