# Návrh — 100 najznámejších slovenských spevákov a speváčok

**Účel:** Rozšírenie autocomplete poolu *sólo* spevákov v hudobnom kvíze. Tu je 100 mien **čisto slovenských** interpretov (sólo + zopár legendárnych frontmanov skupín, kde sú dlhodobo známi pod vlastným menom).

**Postup:**
1. **Maroš tento súbor prejde** — pri menách ktoré nechce, vymaže riadok alebo doplní `❌`.
2. Sporné mená (diakritický variant, prezývka vs. plné meno) môže priamo **upraviť**.
3. Keď je súbor pre Maroša OK → povie mi „importuj" a vložím všetky **✅ ponechané** mená do `pmgonijet_cct_artists` jediným SQL INSERT.

**Pravidlo:** všetky tieto mená sú **úplne nové** voči DB (kontroloval som proti `pmgonijet_cct_artists`).

---

## Klasici 60.–70. roky

- [ ] Karol Duchoň
- [ ] Eva Kostolányiová
- [ ] Eva Sepešiová
- [ ] Anka Repková
- [ ] Jana Kocianová
- [ ] Marcela Laiferová
- [ ] Dušan Grúň
- [ ] Karol Konárik
- [ ] Karol Polák
- [ ] Janko Lehotský

## Pop / rock 80.–90. roky

- [ ] Marika Gombitová
- [ ] Pavol Habera
- [ ] Pavol Hammel
- [ ] Vašo Patejdl
- [ ] Jožo Ráž
- [ ] Robo Grigorov
- [ ] Maťo Ďurinda
- [ ] Ivan Tásler
- [ ] Igor Timko
- [ ] Beáta Dubasová
- [ ] Robo Mikla
- [ ] Soňa Skoncová
- [ ] Janka Hospodárová
- [ ] Daniel Heriban
- [ ] Peter Bič
- [ ] Marián Geišberg
- [ ] Robo Šebek
- [ ] Tomáš Tarr
- [ ] Andy Hryc
- [ ] Boby Krištofovič

## Pop 2000.–2010.

- [ ] Zuzana Smatanová
- [ ] Katka Knechtová
- [ ] Jana Kirschner
- [ ] Sisa Sklovska
- [ ] Mária Čírová
- [ ] Dara Rolins
- [ ] Tina (Tatiana Okapcová)
- [ ] Kristína Peláková
- [ ] Tomáš Bezdeda
- [ ] Adam Ďurica
- [ ] Sima Martausová
- [ ] Celeste Buckingham
- [ ] Lina Mayer
- [ ] Helena Krajčiová
- [ ] Robo Opatovský
- [ ] Otto Weiter
- [ ] Roman Méry
- [ ] Mária Podhradská
- [ ] Adriana Bartošová
- [ ] Petra Polnišová

## Folk / spirituál / world

- [ ] Hana Hegerová
- [ ] Janka Guzová
- [ ] Marián Čekovský
- [ ] Ondrej Kandráč
- [ ] Mária Mračnová
- [ ] Sláva Štochlová
- [ ] Vlasta Mudríková
- [ ] Štefan Skrúcaný
- [ ] Pavol Tonkovič
- [ ] Karol Pádivý

## Šansón / muzikál

- [ ] Zuzana Mauréry
- [ ] Mariana Ďurianová
- [ ] Sisa Lelkes Sklovska
- [ ] Eva Pavlíková
- [ ] Andrea Somorovská

## Hip-hop / rap / urban

- [ ] Patrik Vrbovský (Rytmus)
- [ ] Vec
- [ ] Tono S
- [ ] Strapo
- [ ] Separ
- [ ] Ego
- [ ] Majk Spirit
- [ ] Kali
- [ ] Peter Pann
- [ ] DJ Wich
- [ ] Igor Kmeťo
- [ ] Sajfa
- [ ] Boy Wonder
- [ ] Moja Reč

## Rock / indie / alt

- [ ] Peter Lipa (jazz/blues)
- [ ] Juraj Benetin (Korben Dallas)
- [ ] Yxo (Hex)
- [ ] Maťo Greško (Para)
- [ ] Pavol Bystrický
- [ ] Karol Komenda
- [ ] Adam Fenix

## Mladšia generácia / 2015+

- [ ] Karmen Pál-Baláž
- [ ] Karin Mičkanin Šibík
- [ ] Aless (Alexandra Hrabovská)
- [ ] Robert Janíček
- [ ] Wilda
- [ ] Sebastian
- [ ] Sára Berkešová
- [ ] Diana Hopta
- [ ] Annet X
- [ ] Lenny Ibizarre

## Country / folklór

- [ ] Vladimír Smetana
- [ ] Karol Černý
- [ ] Janko Hraška
- [ ] Marián Bango

---

**Po Marošovej úprave:** dám SQL `INSERT INTO pmgonijet_cct_artists (cct_status, artist, cct_created) VALUES (…)` pre všetky povolené.
