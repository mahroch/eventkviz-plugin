# STATUS — EventKviz (aktualizuj po každej väčšej zmene)

> Resume snapshot ("kde sme skončili"). Trvalé pravidlá/konvencie/rituály → **CLAUDE.md** (načítaj ho tiež).
> Detailná chronologická história → **CHANGELOG.md** (95 KB+, najnovšie navrchu pod `[Unreleased]`).
> Globálne pravidlá → `~/.claude/CLAUDE.md`.

## Aktuálny stav (over `git log --oneline -10` a `eventkviz.php` verziu pri každom otvorení — toto je len snapshot k 2026-07-16)

- **Verzia:** 1.24.2 (posledné feat: individuálny login mód, štatistika export CSV/PDF, QR pre mapové kvízy).
- **Git:** `main`, up to date s `origin/main`, working tree čistý. Posledné 4 commity (2026-07-16):
  - **5522647** docs: rozšírenie CLAUDE.md (STATUS.md pointer, DB CLI prístup, SemVer pravidlá, deploy ritual, produktové kontrakty)
  - **9099d52** chore(loadtest): MQ hrady-sr→rieky-eu, smoke scenár preskakuje think-time
  - **4055417** chore: STATUS.md + deploy-theme-only.sh pridané do gitu, deploy/backups/prod-theme-*/ pridané do .gitignore (rollback snapshoty, netreba v repo)

## ✅ Hotové, žiadne otvorené otázky

- **RankMath SEO** nasadené na PROD 2026-06-14 (organization JSON-LD, per-page meta, OG image, sitemap, breadcrumbs). Analytics modul zámerne VYPNUTÝ (GSC/GA sa pozerá priamo v Google) — zjednodušuje EK na čistý „full DB overwrite local→prod" model bez prod-only výnimiek. Runbook: `deploy/SEO-DB-STATE.md`.
- **Georena rebrand cross-sell** (theme, GeoChallenge→Georena) nasadené na PROD 2026-06-12 (theme-only deploy, backup `prod-theme-2026-06-12-235941`). Odkazy idú cez **www.georena.sk** (apex bez www zahadzoval cestu — stav DNS/Cloudflare migrácie sleduje GC/marketing agent, nie EK).
- **Vizuálny refresh eventkviz.sk** (ponuka/home/kontakt — Sora font, farebné ikonové dlaždice, Georena sekcia sunset paleta) — theme-only, prod.
- **Full DB overwrite runbook** (local→prod) reálne overený 2026-06-15 (BENCONT event). Postup funguje 1:1, pozri CLAUDE.md deploy ritual.

## Otvorené

- **B6 — Pohoria SR, chýba Považský Inovec + ďalšie** (jediná zvyšná položka z bývalého EVENTKVIZ-TODO.md, zmazaného 16.7. pri repo cleanupe). Aktuálne v datasete 16 pohorí (Biele Karpaty, Branisko, Levočské vrchy, Malá Fatra, Malé Karpaty, Nízke Tatry, Poľana, Slanské vrchy, Slovenský kras, Strážovské vrchy, Tribeč, Veľká Fatra, Vihorlat, Vysoké Tatry, Západné Tatry, Štiavnické vrchy). Navrhnutý rozšírenie (čaká Marošovo schválenie, analogicky ako B5 rieky): Považský Inovec, Vtáčnik, Žiar, Kremnické vrchy, Krupinská planina, Cerová vrchovina, Volovské vrchy, Čierna hora, Bachureň, Spišská Magura, Pieniny, Čergov, Bukovské vrchy, Vihorlatské vrchy, Javorníky. Beskydy — treba overiť, či polygón má SR časť (väčšina v ČR/PL). **Blokuje:** reálne GeoJSON polygóny (OSM Overpass, rovnaký postup ako pri B5 rieky — nástroje v `tools/`). Postup: Maroš OK na zdroj → import → update `_mapquiz_feature_pool` (pozri CLAUDE.md gotcha) → Chrome MCP test.

Žiadny iný rozrobený feature/fix k 2026-07-16. Repo hygiena (necommitnuté loadtest/CLAUDE.md zmeny, untracked backup priečinky) je vyriešená commitmi vyššie. Pri ďalšom otvorení tejto session over najprv git log + CHANGELOG `[Unreleased]` sekciu — ak niekto medzitým (iná session) niečo pridal, toto je zastarané.

## Ďalší krok

**B6 pohoria** — počkať na Marošovo schválenie zoznamu vyššie, potom import + feature pool update. Inak žiadny konkrétny krok — čakaj na Marošov pokyn. Ak sa spýta "kde sme skončili", zhrň verziu + git stav + posledné ✅ hotové položky + B6 vyššie.
