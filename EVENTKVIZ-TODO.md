# EventKviz — TODO / backlog

Odložené veci. Hotové presúvať do CHANGELOG.md s príslušnou verziou.
Položky vyznačené **(?)** majú otvorené otázky pre špecifikáciu.

---

## A. UI / UX fixy (krátke, jasné)

### ~~A1. Klik „Späť na linky s kvízmi" zmaže meno tímu a všetko~~
**Hotové vo v1.18.8** — hub stránka pri load-e auto-skip-uje startup formulár ak sú údaje predvyplnené z URL → linky sa zobrazia hneď.

### ~~A2. Fullscreen browser mód na desktope — startup karta necentrovaná vertikálne~~
**Hotové vo v1.18.9** — `.ek-startup` má `min-height: 100vh` (+ `100dvh`).

### ~~A4. Označiť v hub-prehľade kvízy, ktoré tím už absolvoval~~ — **HOTOVÉ v1.18.11**
**Stav:** open · **Zapísané:** 2026-05-29
V hub stránke (`/eventkviz-vstup/?akcia=X&team=Y`) pri zobrazení kariet jednotlivých kvízov (Hudobný / Filmový / Vedomostný / Sudoku / Mapový sub-kvízy) ukázať vizuálny indikátor pri tých, ktoré daný tím už **aspoň raz absolvoval** (napr. zelená fajka v rohu karty, badge „Hraný" / „X b", alebo zmena vzhľadu karty).
**Dáta:** ide cez `pmgonijet_cct_results` — pre daný `akcia` + `team` (alebo `user`) zistiť, ktoré `quiz_type` (a pri mapách `mq` slug z `question_set`) majú aspoň jeden záznam. Najlepšie odovzdať do JS pri renderovaní hub stránky.
**Otvorené:**
- Forma indikácie — ikona ✓, badge „Hraný", percento, počet bodov, niečo iné?
- Mali by sme ukazovať aj **najlepšie skóre** tímu na danom kvíze? (motivačné — vidí čo už má, môže ho prekonať pri ďalšom pokuse)
- Pre režim hráčov (`identifikacia_kodom_usera`) analogicky.

### ~~A3. Mapový kvíz (line/area) — hover nad vybranou plochou má ukazovať názov úlohy~~ — **HOTOVÉ v1.18.10**
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

### B1. Rieky SR — jedna rieka rozdelená na východe ⏳ vyžaduje identifikáciu
**Stav:** vyžaduje Marošov screenshot · **Zapísané:** 2026-05-29
**Analýza GeoJSON (29.5.):** rieky na východe sú už **MultiLineString** s veľa partmi (Ondava 37, Topľa 15, Bodrog 5) — to je technicky správne (hlavná rieka + prítoky), ale ak Maroš vidí vizuálny gap medzi nesúvislými kúskami, treba presnú identifikáciu.
**Maroš:** pošli screenshot kde to vidno (zoom na rozdelenú časť) — z toho určím konkrétnu rieku a opravím geometriu (spojiť segmenty alebo doplniť chýbajúci úsek).

### B2. Hudobný kvíz — premenovať „rnb soul" → „RNB Soul" ✅ + Bambuľka (čaká rozhodnutie)
**Stav:** čiastočne · **Zapísané:** 2026-05-29
- ✅ **RNB Soul** — pesnička `_ID=5618` premenovaná z " R n′B Soul" → "RNB Soul" (29.5.).
- ⏳ **Bambuľka** (`_ID=2726` v cct_artists) — je referencovaná otázkou `post_id=526` „Bambuľka - Prekvapenie" (audio kvíz). Delete artist by rozbil otázku. Treba rozhodnúť:
  1. **Zmazať aj otázku 526 + relations + media attachment 527.**
  2. **Nahradiť artist v otázke 526** za reálneho speváka (akého?).
  3. **Premenovať Bambuľka** na meno reálneho speváka (ak je Bambuľka pseudonym).

### B3. Pridať interpretov do audio kvíze
**Stav:** open · **Zapísané:** 2026-05-29
Aktuálne málo jednotlivých spevákov. Maroš navrhuje pridať: **Habera, Hamel, Ptejdl, Gombitová, Ďurica, Tomeček, Čmorík, …**
**Postup:** pridať do `pmgonijet_cct_artists` cez admin alebo SQL/import, prepojiť s existujúcimi `questions-audio` CPT (kde má každá otázka súbor + správneho artistu/song).
**Otvorené:** Maroš zatiaľ návrh zoznamu — môže ešte dopĺňať. Treba aj otázky (audio súbory) k novým interpretom, alebo len rozšíriť autocomplete pool?

### ~~B4. Hrady SR — odstrániť detailný popis polohy~~
**Stav:** Maroš si urobí sám cez admin (poznámka pre seba, nepotrebuje implementáciu).

### B5. Rieky SR — pridať ďalšie (menšie) rieky 🔒 BLOCKED externé dáta
**Stav:** blocked · **Zapísané:** 2026-05-29
**Aktuálne v datasete (13):** Bodrog, Dunaj, Dunajec, Hornád, Hron, Ipeľ, Morava, Nitra, Ondava, Poprad, Slaná, Topľa, Váh.
**Maroš navrhuje pridať:** Belá, Laborec + návrh ďalších menších.
**Návrh ďalších (na schválenie):** Latorica, Uh, Cirocha, Sekčov, Torysa, Olšava, Kysuca, Orava, Vlára, Myjava, Rajčianka, Turiec, Revúca, Štiavnica, Žitava, Slatina, Krupinica, Rimava, Muráň.
**Blokuje:** potrebujem reálne GeoJSON polylines pre nové rieky — buď z OSM Overpass API, alebo data.gov.sk, alebo Marošov vlastný GeoJSON. Po získaní → append do `public/data/regions/sk-rivers.geojson`.

### B6. Pohoria SR — chýba Inovec + ďalšie 🔒 BLOCKED externé dáta
**Stav:** blocked · **Zapísané:** 2026-05-29
**Aktuálne v datasete (16):** Biele Karpaty, Branisko, Levočské vrchy, Malá Fatra, Malé Karpaty, Nízke Tatry, Poľana, Slanské vrchy, Slovenský kras, Strážovské vrchy, Tribeč, Veľká Fatra, Vihorlat, Vysoké Tatry, Západné Tatry, Štiavnické vrchy.
**Maroš pýta:** Inovec (Považský) chýba. Beskydy — overiť či polygon má SR časť (väčšina je v ČR/PL).
**Návrh ďalších (na schválenie):** Považský Inovec, Vtáčnik, Žiar, Kremnické vrchy, Krupinská planina, Cerová vrchovina, Volovské vrchy, Čierna hora, Bachureň, Spišská Magura, Pieniny, Čergov, Bukovské vrchy, Vihorlatské vrchy, Javorníky.
**Blokuje:** potrebujem reálne GeoJSON polygóny — analogicky ako B5.

---

## C. Nové funkcionality

### ~~C1. Polygóny — okresy SR ako nová mapová šablóna~~ — **DATASET HOTOVÝ v1.18.13**
**Stav:** dataset registrovaný (79 okresov, geoBoundaries ADM2 → ručne mapované SK mená). Maroš teraz môže v admine vytvoriť `mapquiz_template` typu „plocha" s datasetom **„Okresy SR"** — bude fungovať identicky ako Pohoria/Národné parky/Vodné nádrže.
**Stav:** open · **Zapísané:** 2026-05-29
Pridať novú šablónu typu **area** (polygón) — určovanie **okresov Slovenska**.
**Postup:**
1. GeoJSON okresov SR (Natural Earth, alebo data.gov.sk admin levels).
2. Pridať dataset do `class-eventkviz-mapquiz-datasets.php` (slug, label, geojson path, singular).
3. Vytvoriť template (`mapquiz_template` CPT) typu `area` s týmto datasetom.
**Otvorené:** všetky okresy (79) alebo výber? Body za správnu odpoveď?

### ~~C2. Štatistika len pre jeden konkrétny tím~~ — **HOTOVÉ v1.18.12**
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
