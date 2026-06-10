# EventKviz load test (opakovateľný)

Záťažový test prod webu (eventkviz.sk) cez [k6](https://k6.io). Simuluje reálnych
hráčov: vstupná stránka + stránky kvízov + **ťažké assety** (audio hudobného,
video filmového, geojson mapového). Per-typ metriky → uvidíš, ktorý typ je najťažší.

Cieľ: koľko súčasných hráčov web znesie + kde je strop. Postavené tak, aby sa
**dalo opakovať bez ďalšej tvorby testov** — len zmeníš `config.env` a spustíš `run.sh`.

---

## 0) Prerekvizity (raz)

```bash
brew install k6
```

## 1) Dedikovaný test event (raz) — DÔLEŽITÉ

Testuj **proti dedikovanému eventu**, nie reálnemu — načítanie stránok kvízu zapíše
do DB `question_set` pre fiktívnych `lt`-userov.

Vytvor event so slugom **`loadtest`** (alebo zmeň `AKCIA` v `config.env`) s aktívnymi
typmi, ktoré chceš testovať (music/movies/knowledge/sudoku + mapový sub-kvíz).
Do `config.env` daj jeho `MQ` (slug mapového sub-kvízu z `event_mapa_quizzes`).
*(Claude ti ho vie pripraviť — povedz.)*

## 2) Konfigurácia

Uprav `config.env` (URL, `AKCIA`, `MQ`, `TYPES`, `VUS`). Žiadne tajomstvá, je commitnuté.

## 3) Spustenie

```bash
bash run.sh smoke               # VŽDY najprv — 1 VU, overí že skript + endpointy fungujú (~0 záťaž)
bash run.sh realistic 50        # 50 hráčov s think-time (reálna akcia)
bash run.sh burst 50            # 50 naraz (najhorší prípad — koniec časovaného kola)
bash run.sh ramp-to-break       # 25→50→100→200 kým nezačne padať (zistí skutočný strop)
```

Výsledky sa ukladajú do `results/<scenár>-<vus>vu-<timestamp>.txt` (+ `.json`).

> **Môže to spustiť aj Claude** cez svoj Bash (po `brew install k6`). Smoke pustí
> kedykoľvek; plný load len keď povieš „pusti load test" v tichom okne.

---

## ⚠️ Bezpečnostné pravidlá

- **Len v tichom okne — NIKDY počas živej akcie.** `burst`/`ramp-to-break` vie web
  dočasne zaťažiť.
- Začni **smoke**, potom **realistic**, až potom **burst** / **ramp-to-break**.
- Počas behu **Claude sleduje prod error log cez SSH** (`tail` na logoch) — hneď vidno
  502/503/saturáciu.
- Ramp je postupný; ak error rate vyskočí, **Ctrl+C** test zruší.

## 📊 Ako čítať výsledky

- `http_req_duration p(95)/p(99)` — odozva; prahy: p95 < 2 s, p99 < 5 s.
- `http_req_failed` — chybovosť; prah < 2 %. Skoč na to, ak rastie.
- **5xx / timeouty** = saturácia (FPM workeri / DB). Pri akom počte VU začnú = tvoj strop.
- Metriky sú **tagované per endpoint** (`hub`, `music_page`, `music_asset`, `mapa_geojson`…)
  → v summary vidíš, ktorý typ ťahá najviac (čakaj, že audio/video assety budú najťažšie).

## 🧹 Upratanie po teste

Test zanechá `question_set` (a prípadne výsledky) pre `lt`-userov v test evente.
Zmaž ich (lokálne pred deployom, alebo priamo na prod cez WP-CLI):

```sql
DELETE FROM <prefix>jet_cct_results WHERE akcia = 'loadtest' AND user LIKE 'lt%';
```
*(Claude to vie spraviť cez SSH/WP-CLI — povedz „uprac load test dáta".)*

---

## Rozšírenie (v2, voliteľné)

Tento harness robí **read + asset** záťaž (dominantná časť reálnej záťaže + presne
audio/video/mapy, ktoré ťa zaujímajú). Plný flow s **POST eval** (zápis skóre do DB)
sa dá doplniť: GET stránky → vyparsovať `set`/`set_sig` → POST na eval endpoint
(`/audio-quiz-dynamic-evaluation/` atď.). Pridá DB-write záťaž; povedz, ak to treba.
