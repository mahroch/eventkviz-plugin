# Changelog

Všetky podstatné zmeny v plugine EventKviz.

## [Unreleased]

### Changed (mapový kvíz — mini-mapa: jemnejší zoom + názvy štátov v EU)
- Mini-mapa: zoom je teraz na `featureBounds.pad(0.8)` (= viewport zväčšený o 80% okolo feature) capnutý na region bounds — vidno feature aj okolité štáty/regióny pre geo kontext. Pin mode: bbox ±5° v každom smere.
- EU mini-mapy: na všetkých štátoch sa zobrazujú permanent labely (názov štátu) ako jemné šedé texty s bielym text-shadow. Pomáha hráčovi orientovať sa („Srbsko je tu, vedľa Maďarsko, Rumunsko, Bulharsko..."). Leaflet renderuje len label-y štátov v aktuálnom viewporte mini-mapy.

### Fixed (admin „Výsledky" — „Sorry, you are not allowed" pri zmene akcie)
- Form na zmenu akcie v EventKviz → Výsledky používal default action URL (= current URL) bez explicit `post_type=eventkviz_event`. Po submit GET request URL nemala `post_type` parameter → WP admin router nerozpoznal že submenu patrí pod CPT menu → permission check fail → 403 „Sorry, you are not allowed to access this page".
- Fix: form action explicitne na `admin_url('edit.php')` + hidden `post_type=eventkviz_event` input.

### Added (admin event editor — warning ak pocet_otazok > pool size templatu)
- Pri každom mapquiz sub-quize v Edit Event admin teraz vidí: „📊 Aktuálna šablóna má **X** features v poole." Pri zmene template alebo počtu otázok (set) sa text live aktualizuje.
- Ak `set > pool` zobrazí sa červené upozornenie: „⚠ Set N otázok je väčší než pool M features — hráč dostane reálne len M úloh. Pridaj viac features do šablóny alebo zníž počet otázok." Admin tak nezistí problém až keď ho hráč nahlási.

### Added (scoring info — upozornenie pri new_questions_on_retry)
- Ak template/event má zapnuté `new_questions_on_retry` (= pri každom pokuse nová sada otázok), scoring info text pripojí: „Pri opakovaní môžu byť otázky iné (vyberú sa nanovo)." Hráč vie že nemá zmysel rátať s opakovaním tých istých otázok.

### Fixed (mapquiz scoring info — nesprávny počet úloh keď pool < set)
- Ak admin nastavil `pocet_otazok_v_sete = 10` v evente ale pool template má len 5 features, plugin správne cap-uje na 5 úloh. Scoring info text ale hovoril „označuješ 10 území" (z raw event settings) — nesprávne. Teraz text reflektuje **reálny task count po cappingu** („označuješ 5 území"). max_per_task tiež správne počítaný z reálneho countu.

### Added (admin „Linky pre hráčov" — generátor tokenizovaných liniek)
- Pribudla sekcia **„3b. Tokenizované linky pre konkrétny tím"** v admin Event-Links meta box. Admin zadá kód tímu + (voliteľne) hráča, klikne „Generuj" → JS volá REST `/eventkviz/v1/link-token` a vygeneruje **opaque `?t=...` linky** pre všetky kvízy (vrátane mapquiz sub-kvízov). Každý link má vlastné „Kopírovať" tlačidlo.
- Plain šablóny v sekcii 3 (s `team=TEAM&user=USER` placeholdermi) ostávajú — pre batch ručnú replacement scenarios. Backwards compat zachovaný.

### Added (security — opaque link tokens proti URL podvodom)
- Quiz URLs sa už negenerujú v plain forme `?akcia=velka-noc&team=TEAM&user=USER&mq=0f0ab8`, ale ako opaque token `?t=<base64+HMAC>` (cca 80 znakov). Hráč v URL nevidí team/user/akcia/mq — útočník nedokáže fabricate vlastný token bez prelomenia HMAC secretu.
- **Helper class** `Eventkviz_Link_Token` (`includes/class-eventkviz-link-token.php`) — `encode()` / `decode()` / `build_url()`. Krátke aliasy (a/t/u/m) + URL-safe base64 bez padding + HMAC-SHA256 podpis (10 znakov, dosť pre brute-force resistance).
- **Init hook** (priorita 4) dekóduje `?t=...` a namapuje na `$_GET[akcia/team/user/mq]` — zvyšok plugin kódu ostáva nezmenený.
- **REST endpoint** `/wp-json/eventkviz/v1/link-token` — JS link generátory (show_team_links, show_link_to_quiz) ho volajú async pri kliknutí na „Start" tlačidlo aby získali tokenized URL. Pri REST chybe fallback na plain URL.
- **Retry buttons** vo všetkých kvízoch (music/movies/knowledge/sudoku/mapa) cez `build_retry_url()` automaticky generujú tokenized URL.
- **GeoChallenge return URL ostáva plain** — link do externej GC appky musí byť dekódovateľný GC-om, ten nemá náš secret. Plus `gc_id/gc_cp/gc_return` v retry context tiež ostávajú plain (musia byť čitateľné mimo nášho pluginu).
- **Backwards compat:** staré plain `?akcia=...&team=...&user=...` linky stále fungujú (žiadny mandatory redirect). Bookmark z prv vytvoreného linku sa nepokazí.
- **Secret key** auto-generated pri plugin activate cez `wp_generate_password(64)` do `wp_options` (key `eventkviz_link_secret`). Lazy init pre už-bežiace installs.

### Added (mapquiz — šablóna „Vodné nádrže a jazerá SR")
- 11 najznámejších slovenských vodných plôch — mix umelých priehrad + prírodných plies/sopečných jazier:
  - **Priehrady:** Oravská priehrada, Liptovská Mara, Zemplínska šírava, Domaša (Veľká), Sĺňava, Ružín, Nosická priehrada, Gabčíkovo (Hrušovská zdrž)
  - **Tatry plesá:** Štrbské pleso, Popradské pleso
  - **Vihorlatské sopečné jazero:** Morské oko
- Fetch z OSM Overpass podľa explicitných OSM IDs (presnejšie než name match — vyhne sa duplicit „Morské oko" / „Malé Morské oko"). Pre plesá menšia simplifikácia (n=2) aby si zachovali aspoň základný tvar viditeľný pri SK zoom-e.
- Nový dataset `sk-waterbodies` v registry (area, slovakia, singular „vodnú plochu"), bundle 65 KB.
- Fetch skript: `tools/fetch-sk-waterbodies.py` (idempotentný). Seed skript: `tools/seed-sk-waterbodies.php`.

### Added (mapquiz — vlastné kreslenie polygónov / línií v admin editore)
- **Veľká feature** — admin si môže nakresliť vlastné polygony (area mód) alebo línie (line mód) priamo v admin editore, bez programátora. Doteraz boli polygony/línie možné len cez pre-bundle datasety (pohoria SR, štáty EU…).
- **Toggle „Zdroj features"** v admin editor: `Bundle dataset` (z registry) vs `Vlastné kreslenie`. Pre vlastné sa zobrazí Leaflet.draw mapa s toolbarom (nakresliť polygon/líniu, upraviť vertexy drag-and-drop, vymazať).
- Po nakreslení sa otvorí prompt na názov feature. Pri uložení sa features ukladajú do postmeta `_mapquiz_custom_features` (GeoJSON FeatureCollection). Pool features `_mapquiz_feature_pool` sa pri custom sourci automaticky synchronizuje so všetkými nakreslenými.
- **Edit polish (fáza 2):** drag vertexov, pridanie / odobratie vertexu (cez Leaflet.draw edit tool), vymazanie features (cez delete tool alebo cez ✕ v zozname), premenovanie cez ✎ v zozname.
- **Validácia** v save handler (`sanitize_custom_features()`): GeoJSON štruktúra, polygon ≥3 vertexy, line ≥2 vertexy, sanitizácia názvov. Nevalid features sa odstránia bez chyby.
- **Player JS** podporuje obe sources — pre custom nefetch-uje bundle súbor, ale použije inline `window.ekMapaCustomFeatures` z PHP (passnuté pri renderi container-u).
- Nový postmeta: `_mapquiz_features_source` (`bundle`|`custom`), `_mapquiz_custom_features` (GeoJSON JSON).
- Nový registry helper `Eventkviz_MapQuiz_Datasets::feature_names_for_template()` — vráti pool podľa source (bundle file vs custom postmeta).
- Bundle: `public/vendor/leaflet.draw/` — Leaflet.draw v1.0.4 (MIT licensed), local copy (žiadny CDN runtime dep).

### Fixed (scoring info — centering + margin-bottom)
- Theme alebo Elementor agresívne CSS pre `p` (text-align: left) prepisovalo moje `.ek-scoring-info` rules — text bol zarovnaný vľavo, nie na strede stránky. Wrap z `<p>` na `<div>` + vyššia specificity (`body .ek-scoring-info`, `.ek-quiz .ek-scoring-info`) s `!important` na auto margin. Zväčšený margin-bottom z 28px na 32px aby bol viac oddelený od ďalšieho obsahu (tries banner / sumár bodov).

### Fixed (Pohoria Európy — polygony prerobené ručne pre geo presnosť)
- Natural Earth `ne_10m_geography_regions_polys` má pre niektoré pohoria iba **schematické polygóny** (Tatry = úzky obdĺžnik, Sudety = 3 nesúvislé kúsky, Kaukaz nezobrazený v EU viewport-e). Pre kvíz to bolo nepoužiteľné — hráč napríklad nemohol klepnúť na Kaukaz lebo nebol vidieť.
- Nahradené **ručne definovanými polygónmi** pre 12 top európskych pohorí (Alpy, Karpaty, Pyreneje, Apeniny, Škandinávske vrchy, Tatry, Sudety, Balkán, Dinárske vrchy, Južné Karpaty, Kavkaz, Ural). Polygony sú jednoduché 8-13 bodové aproximácie reálnej rozlohy — geograficky presné + dostatočne veľká plocha na klik.
- Tatry teraz pokrývajú celý hrebeň (Vysoké, Západné, Belianske), nie len úzky pásik. Kaukaz je viditeľný v EU viewport-e.
- Pribudli **Apeniny** (Taliansko) + **Škandinávske vrchy** ktoré v NE neboli; odstránené Malý Kaukaz (slabá geo signál) a Vogézy (príliš malé).
- Pool template „Pohoria Európy" (ID 2111) automaticky aktualizovaný na nový set 12 features.
- Build skript `tools/build-europe-mountains.py` (idempotentný, regeneruje GeoJSON z hardcoded polygons).

### Fixed (kvízy — scoring info čistejšie + lepší kontrast fail/success boxov)
- Mapa scoring info: odstránené zátvorky s vysvetľujúcimi príkladmi („(rieky, železnice…)", „(štát, pohorie, národný park…)") — názov šablóny už hráčovi povie o čo ide, opakovať to v scoring info nedávalo zmysel.
- `.ek-scoring-info` má teraz väčší `margin-bottom: 28px` — predtým bol natlačený na nasledujúce elementy.
- `.ek-quiz-message--success` a `--fail` boxy: solid pozadie + tmavá farba textu (zelená/oranžová) — zaručená čitateľnosť na ľubovoľnom Elementor background (predtým rgba s alpha 0.12 = na fialovom hero pozadí biele písmo zanikalo, „Je potrebné dosiahnuť aspoň 400 bodov…" bolo nečitateľné).
- Inline override štýlov pre mapquiz fail box odstránený — používa globálny CSS.

### Changed (všetky kvízy — human-friendly „scoring info" text nad formulárom + v evale)
- Pre **všetky druhy kvízov** (music / movies / knowledge / sudoku / mapa) sa zobrazí krátky informačný text v ľudskej reči — vysvetľuje za koľko bodov je čo, koľko ich treba na kód a koľko pokusov má hráč. Text je prispôsobený typu kvízu:
  - **Music:** rôzne body za interpreta vs skladbu vs obe spolu k tej istej ukážke.
  - **Movies:** body za správny film vs film priradený k správnej ukážke.
  - **Knowledge:** body za správnu odpoveď + počet otázok.
  - **Sudoku:** rôzne body podľa obťažnosti (ľahká/stredná/ťažká).
  - **Mapa:** varianty pre pin (podľa vzdialenosti — čím bližšie, tým viac), area (klik na plochu — binárne) a line (klik na líniu — binárne).
- Veta o **kóde** sa prispôsobuje: ak `min_body_na_postup > 0` → „Na získanie kódu musíš dosiahnuť aspoň X bodov"; ak = 0 → „Skús získať čo najviac bodov".
- Veta o **pokusoch** sa prispôsobuje: pred kvízom „Máš X pokusov", v evale „Zostáva ti X pokusov" / „Zostávajú ti X pokusy" / „Zostáva ti 1 pokus" / „Toto bol tvoj posledný pokus" — slovenská gramatika 1 / 2-4 / 5+.
- V evale je text v minulom čase („hádal si", „odpovedal si"…).
- Helper `Eventkviz_Quiz_Class::render_scoring_info()` v parent class — jeden formátovač pre všetky kvízy.
- Predchádzajúci verbose box „Ako sa kvíz vyhodnocuje" (s tiers + percentami) v mapquiz evale **odstránený** — nahradený novým friendly textom.

### Added (mapový kvíz — 4 nové šablóny + scoring info box)
- **Pohoria Európy** (area, 12 pohorí z Natural Earth: Alpy, Karpaty, Pyreneje, Tatry, Sudety, Vogézy, Balkán, Dinárske vrchy, Kavkaz, Malý Kaukaz, Ural, Južné Karpaty)
- **Top rieky sveta** (line, 17 riek z NE: Amazonka, Níl, Jang-c’-ťiang, Mississippi, Jenisej, Ob, Lena, Mekong, Volga, Dunaj, Ganga, Niger, Kongo, Paraná, Amur, Mackenzie, Žltá rieka)
- **Top vrcholy sveta** (pin, 15 vrcholov: Everest, K2, Kančendžonga, Aconcagua, Denali, Kilimandžáro, Elbrus, Vinson, Puncak Jaya, Mont Blanc, Matterhorn, Fudži, Mt. Kenya, Mt. Cook, Pico de Orizaba) — Wikipedia fotky.
- **Sopky sveta** (pin, 15 sopiek: Vezuv, Etna, Stromboli, Fudži, Krakatoa, Mt. St. Helens, Kīlauea, Mauna Loa, Yellowstone, Popocatépetl, Eyjafjallajökull, Pinatubo, Cotopaxi, Erebus, Tambora) — Wikipedia fotky.
- World pin templates používajú **world-scale score tiers** (200/500/1000/2000 km) namiesto SR defaults (5/10/20/40 km) — pri svetových vzdialenostiach by tradičné tiers znamenali vždy 0 b.
- Fetch scripty: `tools/fetch-europe-mountains.py` (z Natural Earth `ne_10m_geography_regions_polys`), `tools/fetch-world-rivers.py` (z NE `ne_10m_rivers_lake_centerlines`).
- Seed scripty: `tools/seed-world-peaks.php`, `tools/seed-world-volcanoes.php`, `tools/seed-eu-mountains-world-rivers.php` — všetky idempotentné.

### Added (mapový kvíz — info box „Ako sa kvíz vyhodnocuje" v evale)
- Pred sumarizáciou bodov sa zobrazí vysvetlovací box: max body, počet úloh, rozdelenie, scoring metóda. Pre `pin` mode vypíše tiers s presnými percentami + bodmi (napr. „0–5 km = 100 % = 20 b"). Pre `area`/`line` upozornenie že hodnotenie je binárne. Pomáha hráčovi pochopiť výpočet skóre.

### Fixed (mapový kvíz — chýbal language switcher na desktope)
- `eventkviz_is_eventkviz_page()` (detektor stránok ktoré dostanú floating jazykový prepínač + `eventkviz-page` body class) mal hardcoded zoznam shortcodov, ale mapquiz shortcody `mapa_form_dynamic` + `eval_mapa_quiz_dynamic` v ňom chýbali. Mapquiz stránky nedostávali language switcher renderovaný v `wp_footer`. Doplnené.

### Changed (mapový kvíz — sidebar a eval review čistejšie pre PIN mode)
- Form mode sidebar pre PIN mode už nezobrazuje `description` — bol to spoiler (popis lokality prezrádzal čo hľadať). Zachovaný `hint` (krátka indícia) + `photo` (môže byť súčasťou úlohy „nájdi miesto na obrázku").
- Eval review pre PIN mode: odstránený nadbytočný „🎯 Správna lokalita: <názov>" textový blok — názov je už v hlavičke úlohy aj v sidebare. Zachovaný popis + foto ako vzdelávací box.
- Sidebar review pre PIN mode: predpona „Vzdialenosť: " pred číslo km — predtým „26.31 km · 31 b" bez kontextu, teraz „Vzdialenosť: 26.31 km · 31 b".

### Fixed (mapový kvíz — underline na zoom +/- tlačidlách)
- Theme CSS aplikoval `text-decoration: underline` na `<a>` tagy v contenti, čo postihlo aj Leaflet `+` / `–` zoom ikony. Pridaný `text-decoration: none !important` v hover aj default state.

### Changed (mapový kvíz — výraznejšie zoom controls aj pre mini-mapy)
- Default biele Leaflet zoom `+`/`–` tlačidlá nad svetlým fillom mapy prakticky zanikali. Plugin už mal dark styling pre hlavnú mapu — teraz rovnaký tmavomodrý štýl aj pre mini-mapy v review móde (menšie 26px vs hlavná mapa 32px). Pridaný subtle box-shadow + disabled state (jemne tlmené keď je dosiahnutý max/min zoom).

### Changed (mapový kvíz — mini-mapa interaktívna + lepší default zoom pre SR pin)
- Mini-mapa v review móde má teraz povolený zoom (`+`/`–` controls, double-click, pinch), drag a box-zoom. Hráč si môže dozoom-nuť pre detail miesta. `scrollWheelZoom` zostáva vypnutý — aby kolieskom myši hráč scrolloval stránku, nezasekol sa pri prejazde cez mapu.
- Pin mode default bbox je teraz proporcionálny k regiónu: SR/ČR ±1°/±1.5° (tesný zoom, vidno cca 30 % SR), EU/svet ±5°/±6° (vidno okolité štáty). Predtým bolo univerzálnych ±5°/±6° — pre Slovensko príliš zoom-out.

### Added (mapový kvíz — Národné parky SR migrácia z pin na area)
- Šablóna „Národné parky SR" prepnutá z `pin` (jedna súradnica = stredisko parku) na `area` (polygon kompletného územia parku). Národný park je oblasť, nie miesto.
- Nový dataset `sk-national-parks` (9 SK NP polygónov, stiahnutých z OSM Overpass cez `tools/fetch-sk-national-parks.py`, simplified `n=6`, výsledný bundle 107 KB).
- Hráč teraz klikne na polygon parku ako pri Pohoria SR / Štáty Európy. Binárne hodnotenie (správny park = full body, iný = 0).

### Added (mapový kvíz — mini-mapa: aj nesprávny guess ako červený polygón/línia)
- Mini-mapa pre wrong answer doteraz ukazovala iba zelený správny štát — hráč nevidel kde sám klikol. Teraz sa renderuje aj **červený polygón** (area mód) alebo **červená dashed línia** (line mód) hráčovho výberu, zoom-uje fit-Bounds nad oboma. Hráč vidí súčasne „kde si trafil = červený" a „kde malo byť = zelený".
- Mini-mapa EU labely — zmenšené na 7px + nowrap, aby pri zoom na región/štát nebol crowd.

### Fixed (mapový kvíz — mini-mapa zoom na konkrétnu feature)
- Mini-mapa pre nesprávnu odpoveď v review fitla celý región (kontinent Európy alebo SR bounding box), takže pri malom štáte (Srbsko, Slovinsko, …) alebo riečke bola feature ledva vidno. Teraz mini-mapa po načítaní fit-ne bounds samotnej feature s paddingom 16px a `maxZoom: 7` — dostatočne blízko aby bolo vidno detail, ale stále s nejakým geo kontextom. Pre pin mode auto-bbox ±1.2° okolo súradnice.
- ResizeObserver pri zmene kontajnera tiež refit-uje na feature bounds (predtým by re-set-ol pohľad na celý kontinent).

### Changed (mapový kvíz — area polygon base color zo zelenej na šedú)
- V area móde (štáty, pohoria, NP, …) mali polygóny default zelený fill, čo v review móde kolidovalo so semantikou: zelená = správna odpoveď, červená = nesprávna, oranžová = vybraté v form móde. Base je teraz **neutrálna šedo-modrá** (`#cfd8dc` fill, `#78909c` border), hover **svetlomodrá** (`#90caf9` fill, `#1565c0` border). Zelená/červená/oranžová sú vyhradené pre stavové signály.

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
