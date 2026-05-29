# EventKviz — TODO / backlog

Odložené veci. Hotové presúvať do CHANGELOG.md s príslušnou verziou.
Položky vyznačené **(?)** majú otvorené otázky pre špecifikáciu.

---

## A. UI / UX fixy (krátke, jasné)

### ~~A1. Klik „Späť na linky s kvízmi" zmaže meno tímu a všetko~~
**Hotové vo v1.18.8** — hub stránka pri load-e auto-skip-uje startup formulár ak sú údaje predvyplnené z URL → linky sa zobrazia hneď.

### ~~A2. Fullscreen browser mód na desktope — startup karta necentrovaná vertikálne~~
**Hotové vo v1.18.9** — `.ek-startup` má `min-height: 100vh` (+ `100dvh`).

### A4. Označiť v hub-prehľade kvízy, ktoré tím už absolvoval
**Stav:** open · **Zapísané:** 2026-05-29
V hub stránke (`/eventkviz-vstup/?akcia=X&team=Y`) pri zobrazení kariet jednotlivých kvízov (Hudobný / Filmový / Vedomostný / Sudoku / Mapový sub-kvízy) ukázať vizuálny indikátor pri tých, ktoré daný tím už **aspoň raz absolvoval** (napr. zelená fajka v rohu karty, badge „Hraný" / „X b", alebo zmena vzhľadu karty).
**Dáta:** ide cez `pmgonijet_cct_results` — pre daný `akcia` + `team` (alebo `user`) zistiť, ktoré `quiz_type` (a pri mapách `mq` slug z `question_set`) majú aspoň jeden záznam. Najlepšie odovzdať do JS pri renderovaní hub stránky.
**Otvorené:**
- Forma indikácie — ikona ✓, badge „Hraný", percento, počet bodov, niečo iné?
- Mali by sme ukazovať aj **najlepšie skóre** tímu na danom kvíze? (motivačné — vidí čo už má, môže ho prekonať pri ďalšom pokuse)
- Pre režim hráčov (`identifikacia_kodom_usera`) analogicky.

### A3. Mapový kvíz (line/area) — hover nad vybranou plochou má ukazovať názov úlohy
**Stav:** open · **Zapísané:** 2026-05-29
Vo formulári, počas hrania (pred submit), keď hráč klikne na nejakú oblasť / líniu (priradí ju k aktívnej úlohe → ofarbí sa oranžovo), pri hover myšou nad **vybranou** featurou má vyskočiť tooltip s **názvom úlohy** (napr. `Vihorlat`), nie skutočným názvom feature (napr. `Malá Fatra`) — to by bola nápoveda správnosti.

**V pinoch už funguje:** `placeMarker` v `public/js/eventkviz-mapa-form.js` bindne `marker.bindTooltip(tasks[taskIdx].name, ...)` (názov úlohy) → hráč vidí, čo na pin priradil.

**Pre line/area chýba ekvivalent.** Treba doplniť:
- `onFeaturePick` (po `taskMarkers[currentTaskIdx] = { feature: featureName }`) → `layer.bindTooltip(tasks[currentTaskIdx].name, { sticky: true })`
- `unpickFeature` → `layer.unbindTooltip()` (alebo z mapy hľadať príslušný layer cez `featureLayer.eachLayer` a nájsť ten s `feature.properties.name === picked.feature`)
- Pri load existujúcich pickov v `restorePrevReview` (opakovanie) — analogicky bindnúť, ale **pozor:** pre správne určené pri opakovaní (mark_correctness) už `bindCorrectTooltips` bindne **skutočný názov feature** — tam je to OK (Maroš to chcel — vie, že to má správne). Pre nesprávne / nové výbery sa skutočný názov nesmie ukázať.

**Bez konfliktu s feedback_eventkviz_map_retry_correctness** — tá špecifikácia bola o tom, čo sa zobrazuje vo VYHODNOTENÍ/OPAKOVANÍ, nie o hover-tooltipoch vo formulári.

---

## B. Obsahové úpravy v existujúcich dátach (DB / GeoJSON)

### B1. Rieky SR — jedna rieka rozdelená na východe (?)
**Stav:** open · **Zapísané:** 2026-05-29
Niektorá rieka v mapovej šablóne **Rieky SR** je v GeoJSON rozdelená na dva kúsky → vyzerá ako dva objekty.
**Otvorená otázka:** ktorá rieka konkrétne? Spojiť dva segmenty do jedného `MultiLineString` / `LineString` v `public/data/regions/sk-rivers*.geojson`.

### B2. Hudobný kvíz — premenovať „rnb soul" → „RNB Soul", odstrániť „Bambulka"
**Stav:** open · **Zapísané:** 2026-05-29
- Pesnička s názvom „rnb soul" má byť **„RNB Soul"** (lower → správne kapitalizácia).
- Interpret/skladba **„Bambulka"** je mätúca (nie je to spevák/speváčka).
**Kde:** WP CCT `pmgonijet_cct_songs` (skladby), `pmgonijet_cct_artists` (interpreti). Vyhľadať záznamy → upraviť/odstrániť cez WP admin alebo SQL.

### B3. Pridať interpretov do audio kvíze
**Stav:** open · **Zapísané:** 2026-05-29
Aktuálne málo jednotlivých spevákov. Maroš navrhuje pridať: **Habera, Hamel, Ptejdl, Gombitová, Ďurica, Tomeček, Čmorík, …**
**Postup:** pridať do `pmgonijet_cct_artists` cez admin alebo SQL/import, prepojiť s existujúcimi `questions-audio` CPT (kde má každá otázka súbor + správneho artistu/song).
**Otvorené:** Maroš zatiaľ návrh zoznamu — môže ešte dopĺňať. Treba aj otázky (audio súbory) k novým interpretom, alebo len rozšíriť autocomplete pool?

### ~~B4. Hrady SR — odstrániť detailný popis polohy~~
**Stav:** Maroš si urobí sám cez admin (poznámka pre seba, nepotrebuje implementáciu).

### B5. Rieky SR — pridať ďalšie (menšie) rieky
**Stav:** open · **Zapísané:** 2026-05-29
Maroš navrhuje pridať: **Belá, Laborec, …** + chce, aby som navrhol ďalšie menšie zmysluplné rieky.
**Návrh ďalších slovenských riek (na schválenie):** Latorica, Uh, Ondava (už je?), Cirocha, Sekčov, Torysa, Olšava, Kysuca, Orava, Vlára, Myjava, Rajčianka, Turiec, Revúca, Štiavnica, Žitava, Lehota, Roňava, Slatina, Krupinica, Ipeľ, Rimava, Slaná (už je?), Muráň, Hron-pritoky.
**Postup:** zdroj GeoJSON pre rieky (Natural Earth / OpenStreetMap river polylines), pridať feature do `public/data/regions/sk-rivers.geojson` alebo dataset registry.

### B6. Pohoria SR — chýba Inovec, otázka Beskydy + ďalšie menšie
**Stav:** open · **Zapísané:** 2026-05-29
- **Inovec** v šablóne nie je — pridať.
- **Beskydy** sú na hranici, je polygon na SR? Overiť.
- Ďalšie menšie pohoria — Maroš sa pýta čo ešte by zmyslelo pridať.
**Návrh ďalších pohorí (na schválenie):** Považský Inovec, Tribeč, Vtáčnik, Žiar, Kremnické vrchy, Štiavnické vrchy (už je?), Krupinská planina, Cerová vrchovina, Volovské vrchy, Čierna hora, Branisko, Levočské vrchy, Bachureň, Spišská Magura, Pieniny, Čergov, Bukovské vrchy, Vihorlatské vrchy, Slanské vrchy, Javorníky, Malé Karpaty (už je?), Biele Karpaty (už je?).

---

## C. Nové funkcionality

### C1. Polygóny — okresy SR ako nová mapová šablóna
**Stav:** open · **Zapísané:** 2026-05-29
Pridať novú šablónu typu **area** (polygón) — určovanie **okresov Slovenska**.
**Postup:**
1. GeoJSON okresov SR (Natural Earth, alebo data.gov.sk admin levels).
2. Pridať dataset do `class-eventkviz-mapquiz-datasets.php` (slug, label, geojson path, singular).
3. Vytvoriť template (`mapquiz_template` CPT) typu `area` s týmto datasetom.
**Otvorené:** všetky okresy (79) alebo výber? Body za správnu odpoveď?

### C2. Štatistika len pre jeden konkrétny tím
**Stav:** open · **Zapísané:** 2026-05-29
Zobrazenie štatistiky len pre jeden tím (po-eventový link tímu).
**Otvorené:**
- Selektor: `?team=<slug>` URL param, shortcode atribút `[statistika team="..."]`, alebo dropdown v UI?
- Čo ukázať: rebríček s týmto tímom zvýrazneným + jeho body po kvízoch? Alebo všetko iné skryť?
- Analógia pre režim hráčov (`?user=...`)?

---

## Návrh poradia (na diskusiu)

1. ~~**A1** (v1.18.8)~~ + ~~**A2** (v1.18.9)~~ — hotové
2. **A3** — hover-tooltip s názvom úlohy nad vybranou plochou/líniou (analógia pinov)
3. **A4** — označenie absolvovaných kvízov v hub-prehľade tímu
4. **B2** — rýchle obsahové úpravy v DB (RNB Soul + Bambulka)
5. **B3, B5, B6** — pridávanie obsahu (interpreti, rieky, pohoria) — schválenie zoznamov
6. **B1** — vyžaduje identifikáciu konkrétnej rozdelenej rieky na východe
7. **C2** — štatistika pre jeden tím (po dohode špecifikácie)
8. **C1** — okresy ako nová šablóna (najväčšia úloha)
