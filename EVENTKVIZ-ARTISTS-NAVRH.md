# Importované — 100 slovenských spevákov a speváčok (29.5.2026)


**STAV: ✅ IMPORTOVANÉ 29.5.2026** — všetkých 100 mien je v `pmgonijet_cct_artists` (`_ID` 2737–2836). Tento súbor je teraz archív čo bolo pridané.
**Účel:** Rozšírenie autocomplete poolu *sólo* spevákov v hudobnom kvíze. Tu je 100 mien **čisto slovenských** interpretov (sólo + zopár legendárnych frontmanov skupín, kde sú dlhodobo známi pod vlastným menom).

**Postup:**
1. **Maroš tento súbor prejde** — pri menách ktoré nechce, vymaže riadok alebo doplní `❌`.
2. Sporné mená (diakritický variant, prezývka vs. plné meno) môže priamo **upraviť**.
3. Keď je súbor pre Maroša OK → povie mi „importuj" a vložím všetky **✅ ponechané** mená do `pmgonijet_cct_artists` jediným SQL INSERT.

**Pravidlo:** všetky tieto mená sú **úplne nové** voči DB (kontroloval som proti `pmgonijet_cct_artists`).

---

## Klasici 60.–70. roky

- [x] Karol Duchoň
- [x] Eva Kostolányiová
- [x] Eva Sepešiová
- [x] Anka Repková
- [x] Jana Kocianová
- [x] Marcela Laiferová
- [x] Dušan Grúň
- [x] Karol Konárik
- [x] Karol Polák
- [x] Janko Lehotský

## Pop / rock 80.–90. roky

- [x] Marika Gombitová
- [x] Pavol Habera
- [x] Pavol Hammel
- [x] Vašo Patejdl
- [x] Jožo Ráž
- [x] Robo Grigorov
- [x] Maťo Ďurinda
- [x] Ivan Tásler
- [x] Igor Timko
- [x] Beáta Dubasová
- [x] Robo Mikla
- [x] Soňa Skoncová
- [x] Janka Hospodárová
- [x] Daniel Heriban
- [x] Peter Bič
- [x] Marián Geišberg
- [x] Robo Šebek
- [x] Tomáš Tarr
- [x] Andy Hryc
- [x] Boby Krištofovič

## Pop 2000.–2010.

- [x] Zuzana Smatanová
- [x] Katka Knechtová
- [x] Jana Kirschner
- [x] Sisa Sklovska
- [x] Mária Čírová
- [x] Dara Rolins
- [x] Tina (Tatiana Okapcová)
- [x] Kristína Peláková
- [x] Tomáš Bezdeda
- [x] Adam Ďurica
- [x] Sima Martausová
- [x] Celeste Buckingham
- [x] Lina Mayer
- [x] Helena Krajčiová
- [x] Robo Opatovský
- [x] Otto Weiter
- [x] Roman Méry
- [x] Mária Podhradská
- [x] Adriana Bartošová
- [x] Petra Polnišová

## Folk / spirituál / world

- [x] Hana Hegerová
- [x] Janka Guzová
- [x] Marián Čekovský
- [x] Ondrej Kandráč
- [x] Mária Mračnová
- [x] Sláva Štochlová
- [x] Vlasta Mudríková
- [x] Štefan Skrúcaný
- [x] Pavol Tonkovič
- [x] Karol Pádivý

## Šansón / muzikál

- [x] Zuzana Mauréry
- [x] Mariana Ďurianová
- [x] Sisa Lelkes Sklovska
- [x] Eva Pavlíková
- [x] Andrea Somorovská

## Hip-hop / rap / urban

- [x] Patrik Vrbovský (Rytmus)
- [x] Vec
- [x] Tono S
- [x] Strapo
- [x] Separ
- [x] Ego
- [x] Majk Spirit
- [x] Kali
- [x] Peter Pann
- [x] DJ Wich
- [x] Igor Kmeťo
- [x] Sajfa
- [x] Boy Wonder
- [x] Moja Reč

## Rock / indie / alt

- [x] Peter Lipa 
- [x] Juraj Benetin 
- [x] Yxo 
- [x] Maťo Greško 
- [x] Pavol Bystrický
- [x] Karol Komenda
- [x] Adam Fenix

## Mladšia generácia / 2015+

- [x] Karmen Pál-Baláž
- [x] Karin Mičkanin Šibík
- [x] Aless (Alexandra Hrabovská)
- [x] Robert Janíček
- [x] Wilda
- [x] Sebastian
- [x] Sára Berkešová
- [x] Diana Hopta
- [x] Annet X
- [x] Lenny Ibizarre

## Country / folklór

- [x] Vladimír Smetana
- [x] Karol Černý
- [x] Janko Hraška
- [x] Marián Bango

---

**Po Marošovej úprave:** dám SQL `INSERT INTO pmgonijet_cct_artists (cct_status, artist, cct_created) VALUES (…)` pre všetky povolené.
