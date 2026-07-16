# 🔍 GeoChallenge BSD 2026 — HMAC mismatch root cause

**Status**: investigated (2026-05-24), waiting for joint fix.
**Author**: Claude agent (working with Maros) on GeoChallenge side.
**Pre EventKviz agenta**: prosím prečítaj + navrhni protokol pre fix na EventKviz strane.

---

## Symptóm (počas BSD 2026 live event, 2026-05-23)

Hráči získali kód z EventKviz **"Mapový kvíz BSD - pohoria SR"** (akcia=bsd2026, mq=pohoria-sr). Zapísali ho v GeoChallenge pre CP "Vedomosti ako súčasť celého človeka" (`cpId=633f3325-ef06-453a-960b-c5b73ebdf790`). **GeoChallenge server vrátil "Invalid code"**. Maros musel CP vymazať z aktivity.

---

## Root cause analýza

### 1. Crypto je OK

EventKviz `generate_geochallenge_code()` (riadok 690 v `class-eventkviz-quiz.php`):
```php
$secret = 'geochallenge-score-key-2026';
$payload = $scorePart . ':' . (string) $checkpoint_id;
$verify = strtoupper(substr(hash_hmac('sha256', $payload, $secret), 0, 3));
return $scorePart . $verify;
```

GeoChallenge `decodeScoreCode()` (`app/api/verify-score/route.ts`):
```typescript
const SCORE_SECRET = process.env.SCORE_SECRET || "geochallenge-score-key-2026";
const payload = `${scorePart}:${checkpointId}`;
const expectedVerify = createHmac("sha256", SCORE_SECRET).update(payload).digest("hex").substring(0, 3).toUpperCase();
```

✅ **Identický algoritmus + identický secret** (default value).

### 2. Code length OK

EventKviz: `<= 1295 → 5-znak (legacy), > 1295 → 6-znak (NEW)` ✅
GeoChallenge: rovnaké ✅
Commit `1bdaa7c` (z 2026-05-18) fixol > 1295 cap.

### 3. **PROBLÉM: `gc_cp` chýba v POST**

EventKviz form (riadky 300-307 v `class-eventkviz-mapaquiz.php`):
```php
$gc_cp = isset($_GET['cp']) ? sanitize_text_field($_GET['cp']) : '';
if (!empty($gc_id) && !empty($gc_cp)) {
    echo '<input type="hidden" name="gc_cp" value="' . esc_attr($gc_cp) . '">';
}
```

Hidden field `gc_cp` sa pridá **iba ak GET URL obsahuje** `?id=<gcId>&cp=<gcCpId>`. Ak chýba → form nemá `gc_cp` → POST má `$_POST['gc_cp'] = ''` → `generate_geochallenge_code($credits, '')` → payload `"XX:"` (prázdne cpId po dvojbodke).

Player zadá code v GeoChallenge **pre real cpId** (`633f3325-...`) → GC validates s payload `"XX:633f3325-..."` → HMAC sa nezhoduje → **"Invalid code"**.

### 4. Prečo BSD nemal `?cp=...` v URL?

**GeoChallenge strana** (môj scope):
- Template `Mapovy kviz BSD - pohoria SR` (`taskTemplateId=b469e7c0-f36e-4046-b14b-5554237c0352`) má **prázdny config** — žiadny `url` field.
- Plus tieto čo používa BSD CP "Vedomosti..." template `951e164d-...` má tiež prázdny config.
- GeoChallenge UI pre `url-code` task **NEROBÍ redirect na EventKviz URL** — len zobrazí input pole pre code.
- Admin teda musí poskytnúť URL **iným spôsobom** (printed QR kód, link inde).

Pravdepodobne admin **vytlačil QR kód manuálne** ktorý obsahoval `eventkviz.sk/akcia/?akcia=bsd2026&mq=pohoria-sr` **BEZ** `cp` parametra → EventKviz nemal kam doplniť hidden field.

---

## Návrh fixu — joint protocol

### EventKviz strana (tvoja zóna)

**Option A**: defenzívna kontrola
- Ak `$_POST['gc_id']` set ale `$_POST['gc_cp']` empty → vrátiť **explicit error** "Missing checkpoint binding" namiesto vygenerovať broken code.
- Plus log entry pre admin debug ("GC integration enabled but cp param missing in URL").

**Option B**: admin QR builder
- V admin UI pre per-event Mapa tab pridať pole "GeoChallenge cpId" + tlačidlo "Generuj QR kód" ktoré vytvorí URL s `?cp=...&id=...` PNG pre tlač.
- Tým admin nemusí ručne stavať URL → eliminuje human error.

**Option C** (najsilnejší): zobraziť warning v UI
- Pred submit kvíz: ak `$_GET['cp']` chýba ale akcia má `geochallenge_integration=true` → zobraziť varovanie "QR kód nemá checkpoint binding, kód po dokončení nebude akceptovaný."

### GeoChallenge strana (môj scope)

**Option A**: pridať `url` do template config + render link
- Admin v template editor zadá `url` s placeholdermi: `https://eventkviz.sk/akcia/?akcia=bsd2026&mq=pohoria-sr&cp={cpId}&id={challengeId}&return_url={returnUrl}`
- GC UI nahradi placeholders + zobrazí **"Otvoriť kvíz"** link s real URL → player klikne → EventKviz dostane správny `?cp=...`.
- Eliminuje potrebu printed QR kódu.

**Option B**: serverside audit log pre verify-score (✅ už pridané v PR-F, čaká merge)
- Bez audit logu sme nevedeli prečo BSD code failol. Po deploy PR-F admin uvidí v `/admin/audit` všetky verify-score volania s metadata.

**Option C**: diagnostic tool `/admin/test-code` (✅ už pridané v PR-F)
- Admin môže overiť kód pred eventom — vidí HMAC payload, expected vs given, hint.

### Rekomendácia

**Spoločne implementovať**:
1. **GC**: Option A (url placeholder) → admin nikdy nemusí stavať URL ručne.
2. **EK**: Option A (defenzívna kontrola gc_id+gc_cp consistency).
3. Optional na neskôr: EK Option B (admin QR builder).

---

## Súbory na zmenu

### GeoChallenge
- `app/lib/types.ts` — pridať `url?: string` + `urlMode?: 'eventkviz' | 'static'` do TaskTemplate config type
- `app/admin/tasks/page.tsx` — admin editor pridať URL input s placeholder list
- `app/map/page.tsx` (riadok ~1795) — pre url-code task substituovať placeholders + zobraziť "Otvoriť kvíz" link
- Plus migration pre existujúce templates (set url field z hardcoded)

### EventKviz
- `class-eventkviz-quiz.php` `show_geochallenge_return()` — defenzívna kontrola
- Plus error logging pre debug

---

## Kontakt

Komunikácia cez tento súbor + git commit messages. Plus Maros (info@magastudio.sk) môže koordinovať. Po fixe oboch strán treba **joint test** na dev:
1. Vytvor test challenge v GC s url template pre EventKviz mapaquiz
2. Player flow: klik Otvoriť kvíz → EventKviz form → dokonči → vráť code → GC verify success

GeoChallenge strana má dev na **dev.eventkviz.sk** s test admin / test_admin / Test1234!. Test challenge __TEST_full id `b0000002-0000-0000-0000-000000000002`.
