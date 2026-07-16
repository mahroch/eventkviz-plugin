# EventKviz plugin — pokyny pre Claude

> 📍 **Kde sme skončili → [STATUS.md](STATUS.md)** (resume snapshot). Načítaj hneď na začiatku session.
> Globálne pravidlá (formátovanie, PROD posvätnosť, atď.) → `~/.claude/CLAUDE.md`, platia vždy navyše k tomuto súboru.

## Formátovanie výstupu — ŽIADNY inline code, VŽDY bold (NAJVYŠŠIA PRIORITA)

NIKDY nepoužívať inline code (text v spätných apostrofoch / backtickoch) v odpovediach používateľovi (Maroš). V jeho termináli sa renderuje **svetlomodrým písmom, ktoré je nečitateľné** — opakovane ho to frustruje.

VŽDY namiesto toho **tučné písmo (bold)** — pre názvy súborov, funkcie, premenné, hooky, meta kľúče, hodnoty, príkazy, čokoľvek, čo by inak išlo do backtickov. Default markdown návyk používať backticky pre kód MUSÍŠ vedome potlačiť. Pred každou odpoveďou over, že neobsahuje spätné apostrofy okrem viacriadkových code-fence blokov (tie sú OK — renderujú sa čitateľne).

Toto prebíja default štýl. Platí pre hlavné odpovede aj reporty subagentov.

## Projekt
EventKviz = WordPress plugin (MAMP localhost → deploy na eventkviz.sk prod). SemVer, verzia na 2 miestach v eventkviz.php (hlavička + EVENKVIZ_VERSION define) + CHANGELOG.md. Deploy local → prod cez deploy/PLAYBOOK.md (deploy.sh) — prepisuje CELÚ prod DB local verziou; vždy predeploy changelist + explicit OK pred --yes. EK je zdroj dát pre GeoChallenge ek-quiz (REST export /wp-json/eventkviz/v1/export/<type>).

**Lokál:** WP DB `eventkviz`, prefix `pmgoni` BEZ podčiarkovníka (tabuľky `pmgoniposts`, `pmgonipostmeta`, `pmgonijet_cct_results` atď.). URL `http://localhost:8888/eventkviz` (paralelný `eventkviz2/` priečinok ignoruj — nesúvisí). PHP beží na MAMP default 8.3.30 (žiadny per-folder override, na rozdiel od `sunrise/` ktorý beží na 7.4 — detail v [[reference_mamp_php_per_folder]] v home memory, ak by niekedy bolo treba).

**Lokálny DB CLI prístup** (overenie eventov/CCT dát):
- mysql client: **/Applications/MAMP/Library/bin/mysql80/bin/mysql** (binárky `mysql57`/`mysql80` v `Library/bin/` sú ADRESÁRE, nie spustiteľné — treba `/bin/mysql` vnútri)
- socket: **/Applications/MAMP/tmp/mysql/mysql.sock**, user **root**, pass **root**
- príklad: `/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -S /Applications/MAMP/tmp/mysql/mysql.sock eventkviz -N -e "SQL"` (filtruj `grep -v Warning` kvôli password-on-CLI warningu)
- per-quiz settings eventu žijú v `pmgonipostmeta` ako `event_<typ>_<kľúč>` (napr. `event_movies_pocet_otazok_v_sete`); uložené question sety + výsledky v `pmgonijet_cct_results` (stĺpce `akcia`, `user`, `team`, `quiz_type`, `question_set`).

## SemVer — striktné pravidlá
- **PATCH** (1.2.0→1.2.1): dizajn, refactor, bugfix, polish, security fix, hardcoded→premenné. **Všetko vizuálne aj keď je veľká zmena.**
- **MINOR** (1.2.0→1.3.0): **len nová funkcionalita** — nový shortcode, nová admin voľba, feature ktorú user vidí ako novú.
- **MAJOR** (1.x→2.0): breaking change (DB schéma, odstránenie shortcode/CPT, nekompatibilné public API).
- Verziu bumpni raz za pracovnú session/logický blok, nie po každom commite. UI redizajn nebumpuje MINOR (v minulosti sme za jednu session bumpli 1.1.0→1.4.1, absurdne rýchlo).

**CHANGELOG.md:** nové zmeny pod `## [Unreleased]`, pri bumpe presuň do `## [X.Y.Z] - YYYY-MM-DD`. Max 1 riadok/zmenu (~10-15 slov), píš čo user VIDÍ nie ako sa to interne volá, žiadne CSS triedy/hex farby/file paths v bežných záznamoch (tie patria do commit message).

## Git workflow — pre-flight check + auto-push (ZÁKON, nie odporúčanie)
**Pred prvou zmenou v session:** `git fetch` + `git status` + `git log origin/main..HEAD` + `git log HEAD..origin/main`. Ak origin vpredu → `git pull` PRED opravou (plugin sa vyvíja aj z inej session/stroja). Ak nečistý stav (necommitnuté zmeny / lokálne commity mimo origin / origin vpredu) → **STOP, oznám Marošovi**, nech rozhodne — nikdy nezačínaj novú prácu nad nečistou bázou.

**Po KAŽDOM logickom celku zmien** (dokončený fix/feature/refactor, wording cleanup po feedbacku) → **okamžite `git commit` + `git push origin main` BEZ vyžiadania**. Nikdy nečakaj na „commitni to"/„je to commitnuté?" od Maroša — to je už signál zlyhania tohto pravidla. CHANGELOG.md sa aktualizuje v TOM ISTOM commite ako kód. Verzia v `eventkviz.php` (2 miesta) sa bumpne v releasing commite balíka. Conventional commits (`feat:`/`fix:`/`refactor:`/`security:`/`chore:`) v angličtine, telo môže byť po slovensky. Deštruktívne git operácie (force push, rebase publikovanej histórie, push mimo main) → vždy sa opýtať.

## Po každom fixe/featúre — povinný checklist
1. **CHANGELOG.md** — záznam s verziou (SemVer) + popis + sekcia (Fixed/Added/Changed/Coordination).
2. **Version bump** v `eventkviz.php` na 2 miestach (Plugin header + `EVENKVIZ_VERSION` konštanta).
3. **Dokumentácia** — `EVENTKVIZ-MAPY-DOKUMENTACIA.md` (mapquiz-related) alebo iná relevantná — aktualizuj flow popisy/kontrakty/edge cases.
4. **Test/regression** — plugin NEMÁ formálny test framework (žiadny PHPUnit). Zapíš **manuálny test scenár** (kroky + expected) do `EVENTKVIZ-MAPY-DOKUMENTACIA.md` ako regression checklist. `php -l <file>` syntax check je MANDATORY pred deploy (lint, nie regression).
5. Pred deploy: povinný predeploy changelist + Marošovo explicit OK (pozri „Deploy ritual" nižšie).

## Deploy ritual — trigger „deploy"/„daj na prod"/„deploynes"
Deploy = z lokálneho MAMP na prod **eventkviz.sk** (Websupport). Runbook: **deploy/PLAYBOOK.md**. SSH key auth (`deploy/.env`: `PROD_SSH_KEY=~/.ssh/eventkviz_deploy`, `PROD_SSH_PORT`) — žiadne heslo v příkaze, žiadna 1h konzola aktivácia potrebná, spúšťam priamo.

**🛑 Krok 1 — predeploy changelist povinný, VŽDY, pred `deploy.sh --yes`:** pošli Marošovi presný zoznam — plugin commity (git pull na prod), DB import diff (`deploy.sh` robí FULL DB replace prod←local — Elementor data, page templates, post content, JetEngine CCT, WP options všetko sa prepíše), rsync súbory (uploads/themes/plugins delta), backup + rollback príkaz (`bash deploy/rollback.sh <TS>`).

**Krok 2 — čakaj na explicit OK** („yes"/„áno"/„rob"/„deploynes"/„daj na prod" — NIE „skús"/„dobre znie"/holé „ok").

**Krok 3 — `bash deploy/deploy.sh --yes`** (voliteľne `--dry-run` prv). ~11s ak SSH konzola žije.

**Krok 4 — post-deploy verify:** `curl -sI https://eventkviz.sk` (žiadny 5xx) + `curl -s ... | grep "<nový obsah>"` (fix je tam). Nahlás timestamp + rollback príkaz.

**Why prísne:** raz som `deploy.sh --yes` spustil hneď po „deploy" bez changelistu → prepísal Marošovi Elementor canvas template + dáta na inej stránke priamo na prode. Musel to sám opravovať.

**🆕 THEME-ONLY variant** (`deploy/deploy-theme-only.sh`) — pre čisto theme/marketing zmeny (PHP/CSS v `hello-theme-child-master`, žiadna DB zmena): zálohuje pôvodné prod súbory (`deploy/backups/prod-theme-<TS>/`), scp len vymenované súbory, `wp cache flush` — ŽIADNY DB import/git pull/pre-flight git blok (uncommitted plugin súbory nevadia). Uprav pole `FILES=()` v skripte pri ďalšej theme-only zmene.

**Gotcha:** Snippet ID 45 v `pmgonisnippets` („Stanovistovka - tlač diplomy") používal hardcoded local path — opravené na `get_stylesheet_directory()`. Ak sa niekedy importuje starý backup, tento fix sa môže stratiť a deploy zlyhá — pre-deploy check v `deploy.sh` na to chytá.

## Produktové kontrakty (nemeniť bez konzultácie s Marošom)

**DEMO event sa NIKDY nemaže.** Medzi eventmi (post_type `eventkviz_event`) existuje event **DEMO** — aktívne používaný na ukážky klientom. Pri akomkoľvek upratovaní test eventov (LOADTEST, Auto Draft) DEMO vždy vynechaj. Pri full overwrite local→prod sa DEMO prenáša — to je správne, treba ho zachovať.

**Mapový kvíz — „Pri opakovaní označ správnosť" (`mark_correctness_on_retry`)**, zadanie Maroša 2026-05-27: pri opakovaní (retry) sa **správne** určené features obnovia ZELENÉ + zamknuté (nedajú sa prepísať) + hover ukáže názov; **nesprávne** sa NEobnovia vôbec (žiadna farba/fajka, user háda znova bez nápovedy). Červená sa pri retry zámerne NEpoužíva (na rozdiel od finálneho vyhodnotenia, kde je zelená aj červená) — dôvod: červená by prezradila polohu správnej odpovede. Implementácia v `public/js/eventkviz-mapa-form.js` (`restorePrevReview`, `applyFeatureStyle`, `bindCorrectTooltips`, `renderTaskList`), PHP posiela `mapaN_feature`+`prev_mapaN_correct`. Plný popis + test plán: `EVENTKVIZ-MAPY-DOKUMENTACIA.md` → „Opakovanie: označ správnosť".

**Mapový kvíz — feature pool sync (KRITICKÝ gotcha pri rozšírení datasetu):** pridanie nových features do bundleovaného GeoJSON (`public/data/regions/sk-*.geojson`) NESTAČÍ samo o sebe — každý `mapquiz_template` post má meta `_mapquiz_feature_pool` (JSON zoznam názvov), z ktorého sa REÁLNE losuje do hry. Nové features v GeoJSON bez update poolu = nakreslia sa na mape, ale nikdy sa nevylosujú do úlohy. Postup: (1) po append do GeoJSON zisti `template_id` všetkých templates cez meta `_mapquiz_dataset_slug`, (2) rozšír/nahraď `_mapquiz_feature_pool` podľa nových names, (3) rešpektuj zámerne vyhodené features (nepridávaj naspäť bez dôvodu), (4) Chrome MCP test — over `window.ekMapaTasks` že nové features sú v random sete aj na mape, (5) spomeň v CHANGELOG+TODO že pool bol updatnutý, nielen GeoJSON.

## Deľba práce: marketingový Claude ↔ EK agent (dohoda 2026-06-14)
Aby si lane nešliapali a nebol zmätok kto čo robí:
- **Marketingové theme stránky** — **page-eventkviz-home.php / -ponuka.php / -kontakt.php** + **page-eventkviz-home.css** (copy, dizajn, vizuálne fixy, sekcie) = **robí priamo marketingový Claude** (zložka /Users/maros/claude/marketing). Bez EK subagenta. Po zmene: **CHANGELOG entry** (theme-only) + **bump CSS verzie** vo functions.php (cache-bust).
- **EK plugin** (kvízový/mapquiz engine, REST export, admin), **WP infra**, **SEO plugin (RankMath)**, **DEPLOY** = **EK agent** (toto je tvoja doména).
- **Deploy na prod (eventkviz.sk)** = VŽDY cez rituál (deploy/PLAYBOOK.md) + **Marošov explicit OK**. PROD ostáva Marošovo, nikto nedeployuje sám.
- Prekryv (SEO obsah, tematické stanovište): **marketing dodá copy/dizajn → EK agent spraví WP/plugin stranu.**
- Kto edituje theme súbory, zapíše to do CHANGELOG-u, nech druhá strana vie.

## Vzťah k GeoChallenge (GC)
GC (Next.js, `/Users/maros/claude/geochallenge/web`) je stále na starom skill+home-memory modeli (nie v per-projekt pilote) — plné detaily o **EK↔GC quiz sync architektúre** (batch sync, `ek_quiz_library`, REST export kontrakt) a **EK-konvencia paritnom pravidle** (GC pre ek-quiz/mapquiz-like features MUSÍ používať identické názvy/flagy/labely ako EK) ostávajú v home memory (`reference_ek_gc_quiz_sync`, `feedback_ek_conventions_in_gc`) — GC agent ich odtiaľ číta. Tu len skratka: **EK = editácia/zdroj pravdy pre kvízy, GC = runtime** (headless model). GC ťahá dáta na povel cez `POST /api/admin/sync-ek-quiz`, nie realtime. EK local je zdroj pravdy — deploy local→prod prepíše kompletné dáta na prod EK, GC sync musí s tým počítať (idempotentný upsert).

**EventKviz ≠ Kahoot** (časté nedorozumenie pri marketingu/pozicionovaní): self-paced, hráč na vlastnom tempe/zariadení, výsledky skryté do finálneho vyhodnotenia. GC je samostatná veľká terénna hra, EK kvízy sú v nej len 1 typ úlohy (typ `ek-quiz`) — nie sú to dve oddelené fázy eventu.
