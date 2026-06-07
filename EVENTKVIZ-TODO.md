---
pdf_version: 0.1
pdf_updated_at: 2026-06-05 18:02 UTC
pdf_filename: EVENTKVIZ-TODO-v0.1.pdf
---

# EventKviz — TODO / backlog

Aktuálna verzia: **v1.18.17** · Posledná aktualizácia: 2026-05-30

---

## 🔒 BLOCKED — čaká externé GeoJSON dáta

### B6. Pohoria SR — chýba Inovec + ďalšie
**Aktuálne v datasete (16):** Biele Karpaty, Branisko, Levočské vrchy, Malá Fatra, Malé Karpaty, Nízke Tatry, Poľana, Slanské vrchy, Slovenský kras, Strážovské vrchy, Tribeč, Veľká Fatra, Vihorlat, Vysoké Tatry, Západné Tatry, Štiavnické vrchy.

**Maroš pýta:** Považský Inovec chýba. Beskydy — overiť či polygon má SR časť (väčšina v ČR/PL).

**Návrh na schválenie:** Považský Inovec, Vtáčnik, Žiar, Kremnické vrchy, Krupinská planina, Cerová vrchovina, Volovské vrchy, Čierna hora, Bachureň, Spišská Magura, Pieniny, Čergov, Bukovské vrchy, Vihorlatské vrchy, Javorníky.

**Blokuje:** reálne GeoJSON polygóny — analogicky ako B5.

---

## ✅ ARCHÍV — hotové

| # | Úloha | Stav |
|---|---|---|
| **A1** | „Späť na linky s kvízmi" zachová meno tímu (auto-skip startup ak údaje v URL) | CHANGELOG v1.18.8 |
| **A2** | Startup karta vertikálne centrovaná na desktope / fullscreene | CHANGELOG v1.18.9 |
| **A3** | Mapy line/area — hover nad vybranou plochou/líniou ukáže názov úlohy | CHANGELOG v1.18.10 |
| **A4** | Hub — zelený badge „✓ X b" pri kvízoch, ktoré tím už absolvoval | CHANGELOG v1.18.11 |
| **B1** | Rieky SR — Ondava + Topľa už nie sú vizuálne rozdelené na východe (segmenty spojené konektormi) | CHANGELOG v1.18.16 |
| **B5** | Rieky SR — dataset rozšírený z 13 na 28 riek (Tier 1+2): Laborec, Latorica, Torysa, Orava, Kysuca, Bodva, Belá, Rimava, Turiec, Žitava, Myjava, Uh, Cirocha, Rajčianka, Slatina. Pool template Rieky SR (post 1977) updatnutý na 27 riek (bez Popradu — zachovaná predošlá voľba). Otestované v MAMP — 4 z 10 random tasks boli nové rieky. | CHANGELOG v1.18.17 |
| **B2 RNB Soul** | Pesnička `_ID=5618` v `cct_songs` premenovaná „ R n′B Soul" → „RNB Soul" | DB 29.5. |
| **B2 Bambuľka** | Otázka `post_id=526` „Bambuľka - Prekvapenie" presunutá do koša (post_status=trash). Maroš 30.5. Voliteľné dočistenie ručne: artist `_ID=2726` v `cct_artists` + attachment `post_id=527` ešte v DB | DB 30.5. (Maroš ručne) |
| **B3** | Audio kvíz — masívna várka ~100 nových interpretov v `cct_artists` (29.5.): Habera, Hammel, Patejdl, Gombitová, Ďurica, Karol Duchoň, Kostolányiová, Sepešiová, Repková, Kocianová, Laiferová, Grúň, Lehotský, Ráž, Grigorov, Ďurinda, Tásler, Timko, Dubasová, Smatanová, Knechtová, Kirschner, Sklovska, Čírová, Rolins, Tina, Peláková, Bezdeda, Martausová, Buckingham, Mayer, Krajčiová, Opatovský, Weiter, Méry, Podhradská, Bartošová, Polnišová, Hegerová, Guzová, Čekovský, Kandráč, … + raperi (Rytmus, Vec, Strapo, Separ, Ego, Majk Spirit, Kali, Peter Pann, DJ Wich, Igor Kmeťo, Sajfa, Boy Wonder, Moja Reč, Peter Lipa) a ďalší | DB 29.5. |
| **C1 dataset** | Mapový kvíz — dataset **Okresy SR** (79 polygónov geoBoundaries ADM2 → SK mená) registrovaný v `class-eventkviz-mapquiz-datasets.php` | CHANGELOG v1.18.13 |
| **C1 template** | V admine vytvorený `mapquiz_template` „Okresy SR" (post ID 2140, publish) | Admin (30.5. alebo skôr) |
| **C2** | Štatistika — filter pre jeden tím / hráča so zlatým zvýraznením | CHANGELOG v1.18.12 |
| **bonus 1.18.14** | Hub — sumár bodov + button „📊 Pozri celú štatistiku" | CHANGELOG v1.18.14 |
| **C2 dotiahnutie** | Štatistika filter `?team=` / `?user=` zobrazí **iba** daný tím/hráča (nie celý rebríček) | CHANGELOG v1.18.15 |

---

## Návrh poradia

V backlogu je už len 1 blocked úloha:
1. **B6 pohoria** — analogicky ako B5 rieky: Maroš povie OK na pridanie z OSM Overpass, navrhnem Tier 1+2 → schválenie → import.
