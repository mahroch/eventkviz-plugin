# EventKviz — TODO / backlog

Odložené veci na vyriešenie. Pridávaj nové na začiatok, hotové presúvaj do CHANGELOG.md s príslušnou verziou.

---

## Fullscreen browser mód na desktope — nepekný layout (vertikálne necentrované)

**Stav:** open · **Zapísané:** 2026-05-29

Pri fullscreen prehliadači na desktope (a pravdepodobne aj v normálne vysokom okne) **startup karta** „Pripravte sa na kvíz" sedí v hornej polovici obrazovky a pod ňou ostáva veľký prázdny fialový pruh. Malo by byť **vycentrované aj horizontálne aj vertikálne** — karta v strede viewportu.

**Pravdepodobná príčina:** `.ek-startup` má `min-height: 70vh` (v `public/css/eventkviz.css`). Pri vysokom okne / fullscreene 70vh nestačí a obal nezaplní celú výšku, takže `align-items: center` centruje len v rámci tých 70vh.

**Návrh fixu:** prepnúť `min-height` na `100vh` (alebo `100dvh` kvôli mobilom). Skontrolovať, či to nerozbije obal na malých obrazovkách / v Elementor kontajneri (lebo `.ek-startup` môže byť vložený v Elementor sekcii s vlastnou výškou).

---

## Štatistika pre jeden konkrétny tím

**Stav:** open · **Zapísané:** 2026-05-29

Potrebujeme vedieť zobraziť **štatistiku len pre jeden vybraný tím** (nie celý event so všetkými tímami). Use-case: napr. po skončení eventu poslať každému tímu link s jeho výsledkami / pozícia tímu medzi ostatnými + jeho body po kvízoch.

**Otvorené otázky pre špecifikáciu:**
- Ako sa tím vyberá? URL param `?team=<slug>`, shortcode atribút `[statistika team="budmerici"]`, dropdown v UI?
- Čo presne ukázať: len rebríček s týmto tímom zvýrazneným + body po kvízoch jeho tímu? Alebo všetko ostatné skryť?
- Aj pre režim „identifikácia hráčom" — analógia pre jedného hráča (`?user=...`)?

**Kde to žije:** `includes/class-eventkviz-statistika.php` (po redizajne v1.18.7) — `build_stats()` agreguje cez všetky entity, treba pridať filter.
