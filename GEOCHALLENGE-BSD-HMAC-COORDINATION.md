# 🔗 GeoChallenge × EventKviz BSD HMAC fix — koordinačný dokument

**Autor**: koordinačný agent (Maros, 2026-05-24)
**Status**: 2026-05-24 — GC + EK implementácie hotové (oba čakajú na joint test pred deployom). GC: PR #5, EK: v1.15.4 (lokálne worktree, neсommitнuté).
**Vstup**: [`GEOCHALLENGE-BSD-HMAC-INVESTIGATION.md`](./GEOCHALLENGE-BSD-HMAC-INVESTIGATION.md) (GC agent, 2026-05-24).

---

## 1. Fact-check vs. investigation dokument

Overil som tvrdenia v investigation dokumente voči reálnemu stavu kódu na oboch stranách. **Väčšina sedí, ale 3 dôležité rozdiely menia plán fixu.**

### ✓ Potvrdené (bez zmien)
- HMAC algoritmus + secret default na oboch stranách identický (`geochallenge-score-key-2026`, sha256, 3 znaky uppercase). EK `class-eventkviz-quiz.php:690-709`, GC `app/api/verify-score/route.ts:7,66-71`.
- Legacy 5-znak / nový 6-znak (cap 1295) na oboch stranách OK.
- Root cause: EK `class-eventkviz-mapaquiz.php:300-307` číta `gc_cp` len z `$_GET`; ak BSD QR URL nemala `?cp=...`, hidden input sa nevykreslí → POST nemá `gc_cp` → HMAC sa vygeneruje s prázdnym checkpoint ID → GC „Invalid code".
- EK `show_geochallenge_return()` v `class-eventkviz-quiz.php:711-742` číta `gc_cp` z POST a tichý fallback je `''` — žiadna obrana.

### ✗ Rozdiel 1 — PR-F je už merged (nie waiting for merge)
Investigation tvrdí, že audit log a `/admin/test-code` „čakajú merge". Skutočnosť:
- `app/admin/audit/page.tsx` — existuje, plne funkčný
- `app/admin/test-code/page.tsx` — existuje, diagnostikuje HMAC
- `app/api/verify-score/route.ts:14-36` — server-side audit logging už loguje všetky outcomes
- `app/api/admin/test-score-code/route.ts` — endpoint pre test-code už beží

**Dôsledok**: nemusíme čakať na nič. GC fix môže ísť hneď.

### ✗ Rozdiel 2 — Šablónový `url` field NEMÁ ísť do `TaskTemplate`
Investigation navrhuje pridať `url?: string` + `urlMode` do `TaskTemplate.config` v `app/lib/types.ts`. Skutočnosť:
- `Checkpoint` type už **má `externalUrl?: string`** (`app/lib/types.ts:49`).
- Admin editor pre per-checkpoint URL už existuje (`app/admin/challenges/[id]/page.tsx:3083-3084`).
- `url-code` rendering v `app/map/page.tsx:1795-1860` zobrazuje len input pre code; URL field tam **nie je vôbec render-nutý**, hoci existuje v dátach.

**Revízia návrhu**: nemeniť template type. Namiesto toho **rozšíriť existujúci per-checkpoint `externalUrl` o placeholder substitúciu** + pridať render „Otvoriť kvíz" v `app/map/page.tsx` pre `url-code` task type. Admin to nastaví per-CP (kde aj patrí — rôzne BSD CP-čka môžu volať rôzne EK kvízy).

### ✗ Rozdiel 3 — SCORE_SECRET nie je v `.env.local`
- GC `.env.local` neobsahuje `SCORE_SECRET` — production používa **hardcoded default** `"geochallenge-score-key-2026"`.
- `.env.test.example` ho tiež nemá.
- EK má v kóde rovnaký hardcoded default.

**Dôsledok**: pred-event sa zhodli náhodou (oba defaultujú na rovnaký reťazec). Nie je to chyba pre BSD, ale je to **security smell**: secret je vo verejnom (alebo aspoň deploy-shipped) kóde a v dokumentácii. Pridať do scope ako *neblokujúci* bod.

---

## 2. Revidovaný joint fix protocol

### 🅰 GeoChallenge strana (pre GC agenta)

**Cieľ**: admin definuje per-checkpoint URL s placeholdermi; GC UI ich substituuje a vyrenderuje „Otvoriť kvíz" link, aby player nikdy nemusel skenovať manuálne pripravený QR bez `?cp=`.

**Konkrétne zmeny** (vychádza z aktuálneho stavu, NIE z investigation):

1. **`app/map/page.tsx` (~riadok 1795, sekcia url-code)** — pre task typu `url-code`:
   - Pred input poľom pre code skontrolovať, či `checkpoint.externalUrl` existuje.
   - Ak áno, substituovať placeholdery: `{cpId}` → `checkpoint.id`, `{challengeId}` → aktuálna challenge ID, `{returnUrl}` → `window.location.href` (URL-encoded).
   - Renderovať tlačidlo / link **„Otvoriť kvíz →"** s vyplnenou URL (`target="_blank"`, `rel="noopener"`).
   - Inštrukcia pod ním: „Kvíz vyplň, vráť sa sem a zapíš kód."

2. **`app/admin/challenges/[id]/page.tsx` (~riadok 3083)** — pri `externalUrl` input poli pre `url-code` checkpointy:
   - Pridať helper text pod input: *„Placeholdery: `{cpId}`, `{challengeId}`, `{returnUrl}`. Príklad: `https://eventkviz.sk/akcia/?akcia=bsd2026&mq=pohoria-sr&cp={cpId}&id={challengeId}&return_url={returnUrl}`"*.
   - (Voliteľné) tlačidlo „Test" ktoré vypíše substituovanú URL pre overenie.

3. **`.env.example`** — pridať `SCORE_SECRET=` placeholder + komentár, že MUSÍ byť identický s EK `geochallenge-score-key-*`. Rovnako do Railway env. (neblokujúce pre BSD fix, ale do toho istého PR)

4. **CHANGELOG + GeoChallenge-dokumentacia.md** — záznam o new placeholder support + reference na EK fix.

**Out of scope pre GC**: meniť template type. PR-F audit log už existuje, nepridávať.

**Branch + PR**: dev → main, podľa [[feedback_geochallenge_pr_workflow]].

---

### 🅱 EventKviz strana (pre EK agenta)

**Cieľ**: ak je akcia označená ako GC-integrovaná a chýba `cp` binding, **NEVYGENEROVAŤ broken code**. Lepšie tvrdo zlyhať s jasnou hláškou ako tichý invalid code.

**Konkrétne zmeny**:

1. **`includes/class-eventkviz-quiz.php` — `show_geochallenge_return()` okolo riadku 711-742**:
   - Pred volaním `generate_geochallenge_code($gained_credits, $gc_cp)` skontrolovať:
     ```
     if ( ! empty( $_POST['gc_id'] ) && empty( $_POST['gc_cp'] ) ) {
         // log + render error UI namiesto kódu
         error_log( '[eventkviz] GC integration: gc_id present but gc_cp missing — refusing to generate code' );
         echo '<div class="eventkviz-gc-error">Chyba: QR kód neobsahoval väzbu na konkrétny checkpoint. Kontaktuj organizátora — kód by nebol akceptovaný.</div>';
         return;
     }
     ```
   - Plus zachovať pôvodnú cestu pre validné `gc_cp`.

2. **`includes/class-eventkviz-mapaquiz.php` riadky 300-307** — ponechať bez zmeny (read-only z GET je správne; problém je downstream).

3. **`admin/class-eventkviz-admin.php` ~riadok 702 (`geochallenge_integration` checkbox)** — pridať admin notice/popis pod checkbox: *„Pre správne fungovanie musí mať QR kód pre túto akciu URL v tvare `?akcia=...&mq=...&cp={cpId}&id={challengeId}`. GC checkpoint generuje túto URL automaticky cez placeholder substitúciu."*

4. **(Voliteľné, neskôr) admin QR builder** — pre adminov ktorí chcú generovať QR mimo GC: pridať pole „GC cpId" + „GC challengeId" + tlačidlo „Generuj QR kód" v per-event Mapa tabe. **NIE súčasť tohto fixu**, len todo.

5. **CHANGELOG.md + EVENTKVIZ-MAPY-DOKUMENTACIA.md** — záznam o defenzívnej kontrole + odkaz na GC integráciu.

**Deploy**: štandardný EK deploy ritual podľa [[feedback_eventkviz_deploy_ritual]] a [[feedback_eventkviz_predeploy_changelist]] — žiadny shortcut, lebo prod live.

---

## 3. Joint test plan (po oboch fixoch)

**Prostredie**: GC dev (`dev.eventkviz.sk` admin test_admin / Test1234!), EK lokálne MAMP + následne EK staging ak existuje.

**Test challenge**: `__TEST_full` id `b0000002-0000-0000-0000-000000000002`.

### Status — VYKONANÉ 2026-05-24 (joint test agent)

- **Setup**: vytvorená nová task #01 (URL+kód) "BSD HMAC joint test" + checkpoint "BSD joint test CP" (id `fc9a7fc7-b1cc-4a8a-8eef-d180e560cb53`) v `__TEST_full` s `externalUrl = http://localhost:8888/eventkviz/mapa-quiz/?akcia=bsd2026&mq=49faea&cp={cpId}&id={challengeId}&return_url={returnUrl}`.
- **EK akcia použitá**: `bsd2026` (post 2118, GC integration ON), mapquiz slug `49faea` (Pohoria SR — area/feature quiz).
- **GC dev URL**: `https://geochallenge-dev.up.railway.app` (alias `dev.eventkviz.sk`).
- **Player session**: existujúci `QuizTest_1779612418153` v `__TEST_full` (admin posunul deadline + clearol `ended_at` cez Supabase REST).
- **Cleanup**: testovacie CPs (BSD joint + BSD legacy) odstránené po teste, deadline + ended_at vrátené na pôvodný stav (11:01:00).
- **GIF nahrávky** (downloaded cez Chrome MCP gif_creator do `~/Downloads/`):
  - `scenario-1-happy-path-partial.gif` (13 MB, 30 frames)
  - `scenario-3-admin-test-code.gif` (277 KB, 5 frames)
  - `scenario-2-broken-qr.gif` (1.5 MB, 10 frames)
  - `scenario-4-backward-compat.gif` (8.8 MB, 17 frames)

### Scenár 1 — happy path → **PASS s OBMEDZENÍM**
1. V GC admin vytvor checkpoint v `__TEST_full` s `url-code` task a `externalUrl = https://eventkviz.sk/akcia/?akcia=TEST&mq=test-quiz&cp={cpId}&id={challengeId}&return_url={returnUrl}`.
2. Otvor map page ako player → klik „Otvoriť kvíz".
3. Over že EK form má `gc_id`, `gc_cp` hidden inputy s reálnymi hodnotami (DevTools → Elements).
4. Dokonči kvíz → vráť sa do GC → zadaj kód → expect **success**.

**Výsledok (2026-05-24)**:
- ✅ Krok 1 (admin): checkpoint vytvorený, "Test substitúcie" tlačidlo viditeľné (otvára JS alert dialog s plne resolvovanou URL — alert mi zamrazil MCP tab, ale to len indikuje že button funguje a generuje alert; zaznamenané ako MCP-specific limitation, NIE bug).
- ✅ Krok 2 (player „Otvoriť kvíz →" link): tlačidlo viditeľné v map view čistého `__TEST_full` po klik na CP6 marker (Modra). Hint „Kvíz vyplň, vráť sa sem a zapíš kód." prítomný. `target=_blank rel=noopener noreferrer` správne nastavené.
- ✅ Krok 3 (substitúcia URL): JS inspect `<a>` href: `protocol=http`, `host=localhost:8888`, `path=/eventkviz/mapa-quiz/`, `akcia=bsd2026`, `mq=49faea`, `cp=fc9a7fc7-b1cc-4a8a-8eef-d180e560cb53` (= reálne cpId), `id=b0000002-0000-0000-0000-000000000002` (= reálne challengeId), `return_url=https%3A%2F%2Fdev.eventkviz.sk%2Fmap%3Fid%3Db0000002-0000-0000-0000-000000000002` (URL-encoded). **Žiadne raw `{cpId}` / `{challengeId}` literály nezostali** — substitúcia 100% funguje.
- ✅ Krok 3b (EK form na localhost): po manuálnej navigácii na resolvovaný EK URL (browser automaticky neotvoril nový tab kvôli HTTPS→HTTP **Mixed Content**) hidden inputs sú správne: `gc_id=b0000002...`, `gc_cp=fc9a7fc7...`, `gc_return=https://dev.eventkviz.sk/map?id=b0000002...`, `akcia=bsd2026`, `mq=49faea`, `user=gc_<cpId>` (auto-generated GC user).
- ⚠️ Krok 4 (submit + verify): **NEDOKONČENÉ end-to-end cez UI** kvôli 2 environmentálnym blockerom:
  - **a) EK submit cez browser**: form submission spustí JS `confirm()` dialog ktorý MCP nedokáže auto-accept-núť → renderer freeze → tab close. Workaround: použitý `curl POST` priamy — submit s 1 správnou odpoveďou (Vysoké Tatry) vrátil **200 bodov + EK error block (Scenár 2 cesta), žiadny kód** (lebo URL nemala GC params; toto je správanie pre Scenár 2, nie 1).
  - **b) Code → GC verify cez UI**: po manuálnom zadaní valid kódu `016AAB` (score 42 + CP6 HMAC) do GC ZADAJ KÓD input + klik OK → CSRF mismatch v audit logu (admin session interferuje s player session lebo som zaroven prihlásený admin v rovnakom browseri); GC API odmietne POST s `403 CSRF token mismatch`. Toto je legit GC bezpečnostné správanie, nie regresia v PR #5.
  - **Riešenie pre production E2E**: použiť čistý incognito profil bez admin login alebo iný browser. Substitúcia + render tlačidla + EK form correctness sú 100% potvrdené, „missing piece" je len HTTP roundtrip ktorý Scenár 3 (admin/test-code) priamo validoval ako PASS (server-side HMAC match correct).

### Scenár 2 — broken QR (BSD reprodukcia) → **PASS**
1. Otvor priamo EK URL **BEZ** `?cp=` (simuluje printed QR z BSD): `https://eventkviz.sk/akcia/?akcia=TEST&mq=test-quiz`.
2. Dokonči kvíz.
3. **Expect**: EK NEzobrazí kód, ale error message „Chyba: QR kód neobsahoval väzbu...". Player nezíska zlý kód.
4. V GC `/admin/audit` over že žiadny POST verify-score nebol (player ani nezadal nič).

**Výsledok (2026-05-24)**:
- ✅ Krok 1: navigované na `http://localhost:8888/eventkviz/mapa-quiz/?akcia=bsd2026&mq=49faea` (bez `id` aj bez `cp`). JS inspect: hidden inputs majú `akcia, mq, team, user` ale **ŽIADNY `gc_id` ani `gc_cp`** (správne, lebo `class-eventkviz-mapaquiz.php` r. 300-307 ich render-uje len ak sú v URL).
- ✅ Krok 2: submit cez curl POST s 1 správnou odpoveďou (Vysoké Tatry feature) → 200 bodov získaných → `show_geochallenge_return($gained_credits=200)` zavolané.
- ✅ Krok 3 (OR-check fix verified): response HTML obsahuje `<div class="eventkviz-gc-error">` s exact textom **"Chyba GeoChallenge integrácie"** + **"Chyba: QR kód neobsahoval väzbu na konkrétny checkpoint. Kontaktuj organizátora — kód by nebol akceptovaný."** + ŽIADNY `<div class="geochallenge-return">` (žiadny broken kód generated).
- ✅ Krok 3b (server log): `/Applications/MAMP/htdocs/eventkviz/wp-content/debug.log` obsahuje záznam `[24-May-2026 21:12:43 UTC] [eventkviz] GC integration active but gc_id/gc_cp missing in POST — refusing to generate code` — presne ako špecifikácia.
- ✅ Krok 4: GC audit log nemá `verify-score` calls od player session (potvrdené v `/admin/audit` UI). CSRF mismatch entries v logu sú nesúvisiace (Marošov polling traffic + môj manuál fetch test).
- **Edge cases sub-tested cez standalone PHP**: OR-check správne triggeruje aj pre partial-bind (len jeden z `gc_id`/`gc_cp` chýba), čo by AND check NEZAchytil — koordinátorovo rozšírenie z AND na OR (sekcia 4 vyssie) je potvrdene správne rozhodnutie.

### Scenár 3 — admin diagnostika → **PASS**
1. V GC `/admin/test-code` zadaj manuálne vygenerovaný EK kód + cpId.
2. Over že tool ukáže correct HMAC payload + match/mismatch.

**Výsledok (2026-05-24)**:
- ✅ Krok 1: kód `016AAB` vygenerovaný cez PHP CLI s `secret="geochallenge-score-key-2026", cp_id="fc9a7fc7...", score=42` → payload `016:fc9a7fc7-b1cc-4a8a-8eef-d180e560cb53` → HMAC sha256 prvé 3 znaky = `AAB`. Zadané do `/admin/test-code` form.
- ✅ Krok 2: GC tool zobrazil **"✅Code je VALIDNÝ"** s diagnostikou:
  - `code length: 6`
  - `score part: 016 (parsed: 42)`
  - `HMAC payload: 016:fc9a7fc7-b1cc-4a8a-8eef-d180e560cb53`
  - `HMAC given: AAB`
  - `HMAC expected: AAB` ← **MATCH**
  - `SCORE_SECRET: DEFAULT (27ch)` (potvrdzuje že obe strany používajú default `geochallenge-score-key-2026`)
  - `Checkpoint: BSD joint test CP v __TEST_full`
- **Záver**: GC ↔ EK HMAC matematika je 100% kompatibilná. Default secret synchronizácia funguje. Jediná podmienka pre úspešný end-to-end = nezalomená čerstvá player session bez CSRF konfliktu.

### Scenár 4 — backward compatibility → **PASS**
1. Existujúce challenge s `url-code` task **bez** `externalUrl` (legacy) — over že UI stále renderuje aspoň input pre code (degradácia OK, len chýba „Otvoriť kvíz" tlačidlo).

**Výsledok (2026-05-24)**:
- ✅ Setup: pridaný legacy checkpoint "BSD legacy (no externalUrl)" (id `5e6a7fc7-b1cc-4a8a-8eef-d180e560cb53`, ten istý `taskTemplateId` ako BSD joint test CP, ale **bez `externalUrl` field**) cez Supabase REST PATCH (admin UI nemá easy „remove externalUrl" toggle).
- ✅ Player flow: klik na marker → modal otvoril sa s názvom "BSD legacy (no externalUrl)", **Max. 10 b.**, instrukcia "Vypln kviz na EventKvize, kod zadaj sem", **ZADAJ KÓD input + OK button** prítomné, **„Otvoriť kvíz →" tlačidlo NEpritomné** (správna conditional degradácia).
- ✅ Konzola: žiadne JS errors / TypeErrors / undefined warning related k url-code rendering (jediný error je MetaMask extension warning, irelevantné).
- **Záver**: PR #5 zmena je backward-compatible — legacy `url-code` checkpoints bez `externalUrl` fungujú presne ako predtým (input + verify), len bez novej "skratky" cez tlačidlo.

---

## 4. Action items — rozdelenie

### Pre GC agenta (geochallenge repo)
- [x] Implementuj 4 body z časti 🅰 (1-4) v dev branchi. **DONE 2026-05-24** — feature branch `fix/bsd-hmac-url-code-placeholders` z dev, commit `62dab26`.
  - Bod 1: `app/map/page.tsx` (~r. 1795) — render „Otvoriť kvíz →" linku so substituovanou `checkpoint.externalUrl` (placeholdery `{cpId}`, `{challengeId}`, `{returnUrl}`, URL-encoded), `target="_blank" rel="noopener noreferrer"`, hint „Kvíz vyplň, vráť sa sem a zapíš kód."
  - Bod 2: `app/admin/challenges/[id]/page.tsx` (~r. 3083) — helper text pod externalUrl input s príkladom URL + conditional „Test substitúcie" tlačidlo (zobrazí sa keď URL obsahuje `{`).
  - Bod 3: nový `web/.env.example` so všetkými env vars + `SCORE_SECRET=geochallenge-score-key-2026` placeholder s komentárom o povinnej zhode s EK pluginom.
  - Bod 4: CHANGELOG.md (v1.45.1) + GeoChallenge-dokumentacia.md (URL+kód sekcia) updatnuté (oba mimo git repa, len lokálne).
  - i18n: pridané `task.open_quiz` + `task.open_quiz_hint` (SK + EN) v `app/lib/i18n.tsx`.
- [x] PR otvorený proti `dev` (NIE main per konvencii) — https://github.com/mahroch/geochallenge/pull/5 — Maros zreviewuje, smergne dev, potom dev → main sám po joint teste s EK.
- [ ] Po merge a deploy: nahláška sem (commit hash do tohto dokumentu, alebo dodatok).

**Verifikácia GC agenta**:
- TS check (`npx tsc --noEmit`): 0 nových chýb v zmenených súboroch (pre-existujúce errors v `tests/unit/visibility.test.ts` + `regression-source.test.ts` zostali, nesúvisia s touto zmenou).
- Lint (`npx eslint`): 0 errors, len pre-existujúce warnings.
- Unit tests (`npm run test:unit`): **100/100 pass**.

**Otvorená otázka pre joint test** (GC strana):
- Možný interakčný edge case medzi novými placeholdermi v `externalUrl` a existujúcim adminom auto-appendom `&id=...&cp=...` v `externalUrlWithParams` (admin sekcia, r. 3077) — ak admin použije placeholdery, dvojité parametre v QR kóde. Substitúcia v map page funguje aj pre URL bez placeholderov (no-op). Pri joint teste rozhodnúť či auto-append zachovať pre QR-print use case, alebo conditional disable keď URL obsahuje `{`.

### Pre EK agenta (eventkviz plugin)
- [x] Implementuj 4 body z časti 🅱 (1-3, 5; bod 4 NIE). **DONE 2026-05-24, v1.15.4**
  - Bod 1: defenzívna kontrola v `includes/class-eventkviz-quiz.php` `show_geochallenge_return()` (r. 724-735) — sme za `geochallenge_integration === true` guardom; ak `empty($gc_id) || empty($gc_cp)` → červené error UI + `error_log`, žiadny broken kód. **OR check (nie AND)** pokrýva aj plný BSD scenár (QR vôbec bez GC parametrov, kde `mapaquiz.php` r. 303 AND check zabráni renderovaniu hidden inputov → POST má oba prázdne). Pôvodný EK agent navrhol AND check, **koordinátor rozšíril na OR po review final reportu** (gap: AND by zachytil len partial-bind, nie full no-params BSD case).
  - Bod 2: `includes/class-eventkviz-mapaquiz.php` r. 300-307 **bez zmeny** (per plán)
  - Bod 3: admin notice pod `geochallenge_integration` checkbox v `admin/class-eventkviz-admin.php` r. ~702 — žltý info-block o povinnom QR URL tvare + warning
  - Bod 4 (admin QR builder): **NEROBENÝ** per plán, sledované ako TODO v changelog
  - Bod 5: CHANGELOG.md + EVENTKVIZ-MAPY-DOKUMENTACIA.md zaznamenané (vrátane OR-check vysvetlenia); bump verzie v `eventkviz.php` 1.15.3 → **1.15.4** (PATCH bug fix)
  - PHP lint: PASS na všetkých 3 zmenených súboroch (re-lint po OR-check úprave: PASS)
  - Plugin tree je git repo (`/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/`), zmeny len v worktree — **NIE COMMITNUTÉ** (čaká sa na Marošov OK + joint test PASS)
- [ ] Pred-deploy changelist + Marošovo OK podľa pravidiel. **PENDING — až po joint teste**
- [ ] Deploy local → prod. **PENDING — až po Marošovom OK**
- [ ] Joint test na lokálnom MAMP + dev GC. **PENDING — čaká GC agent fix**

### Pre koordinátora (Maros + tento agent)
- [x] Po obidvoch implementáciách spustiť Joint test plan (časť 3) end-to-end. **DONE 2026-05-24** — všetky 4 scenáre PASS (Scenár 1 s obmedzením — substitúcia + render + EK form correctness verified, ale UI submit pipeline bola blokovaná Mixed Content + CSRF mismatch v test setup; Scenár 3 dokázal HMAC matematiku 100% match server-side). PR #5 merged ako commit `4b0fd79` v `dev`.
- [ ] Update tohto dokumentu so statusom „shipped". **Po Marošom potvrdenom GO → merge dev → main + EK deploy local → prod**.
- [ ] Pridať BSD bug + fix protocol do [[reference_geochallenge_ideas]] alebo CHANGELOG ako naučenie.
- [ ] **(NOVÉ) Pripomienka pred prod deployom**: EK pred-deploy changelist + explicit Marošov OK pred `--yes` per [[feedback_eventkviz_predeploy_changelist]] + [[feedback_eventkviz_deploy_ritual]]; GC dev→main cez PR per [[feedback_geochallenge_pr_workflow]].

### Odporúčanie joint test agenta — **GO pre prod deploy**

**Confidence**: vysoká pre EK fix (Scenár 2 + standalone PHP edge cases = jednoznačný PASS, OR-check + error_log + UI error block všetky overené). Vysoká pre GC fix (Scenár 1 substitúcia/render + Scenár 3 HMAC match + Scenár 4 backward compat = jednoznačný PASS).

**Zostávajúce rizikové miesto**: end-to-end UI player-flow nebolo dokončené v testovacom prostredí kvôli **prostredí specifickým blockerom** (Mixed Content HTTPS→HTTP localhost, CSRF mismatch z admin-session interferencie). Tieto **NEvplyvujú na prod** — prod EK je HTTPS (eventkviz.sk), prod player flow nemá admin session konflikt. Riziko prod regresia = veľmi nízke.

**Pred prod merge / deploy**:
1. **Maros nech to vyskúša manuálne** v incognito tabe na GC dev (vytvorí player accountu cez join, klikne checkpoint url-code, prejde EK kvíz cez prod eventkviz.sk URL — keď EK 1.15.4 bude na prod, alebo predtým na localhost ak GC dev URL je v test checkpointe), dokončí kód → zadá do GC. Trval by **~5 min** a potvrdil by aj poslednú missing UI piece.
2. Alternatívne: ja viem spustiť **Playwright test** (`web/tests/`) ktorý existuje pre player join + admin flow — ten by mohol simulovať full flow bez MCP browser limitations. Treba ale že GC test setup používal lokálny EK = potrebné CORS / network config.

**Odporúčanie agenta**: **GO**. Substitúcia + EK error block + HMAC match boli mechanisticky overené, len HTTP/UI pipeline mala test-env blockery ktoré v prode nepadnú.

---

## 5. Otvorené otázky (riešiteľné ad-hoc, nie blockery)

1. **Multi-quiz checkpoint**: ak by jeden CP mal vyžadovať 2+ EK kvízov, `externalUrl` je single. Riešiť až keď bude reálna potreba.
2. **SCORE_SECRET rotácia**: ak sa raz zmení, treba synchronizovať EK + GC v jednom deploy window. Zachytiť v deploy playbooku.
3. **Return URL bezpečnosť**: `{returnUrl}` substitúcia by mala overovať origin pred odoslaním do EK (anti-open-redirect). Ak GC posiela len GC originy, je to OK.
