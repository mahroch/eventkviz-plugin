# Návrh — rozšírenie autocomplete poolu spevákov

**Účel:** Maroš spomínal, že v hudobnom kvíze je málo *jednotlivých* spevákov ktorých autocomplete ponúka. Tento súbor je **návrh 100 najznámejších slovenských a českých sólo-spevákov** (s niekoľkými legendárnymi frontmanmi skupín) na zaradenie do `pmgonijet_cct_artists`.

**Postup:**
1. **Maroš tento súbor prejde** — pri menách ktoré tam **NEchce**, vymaže riadok alebo doplní `❌`.
2. Pri sporných (slovenský diakritický variant, prezývka vs. plné meno, …) môže meno **upraviť** priamo v súbore.
3. Pri kontroverzných (skupiny napriek tomu, že sa rátajú ako „sólo") môže pridať `(skupina)` alebo vymazať.
4. Keď je súbor pre Maroša OK → povie mi „importuj" a vložím všetky **✅ povolené** mená do DB jediným SQL INSERT.

**Pravidlo:** ÚPLNE nové mená. Tie, ktoré sú už v DB (kontroloval som proti exportu z `pmgonijet_cct_artists`), tu nie sú.

---

## SK — klasici 60.–80. roky

- [ ] Karol Duchoň
- [ ] Eva Kostolányiová
- [ ] Eva Sepešiová
- [ ] Anka Repková
- [ ] Jana Kocianová
- [ ] Janka Lehotská
- [ ] Marcela Laiferová
- [ ] Dušan Grúň
- [ ] Karol Konárik
- [ ] Karol Pádivý

## SK — pop/rock 80.–90. roky

- [ ] Marika Gombitová
- [ ] Pavol Habera
- [ ] Pavol Hammel
- [ ] Janko Lehotský
- [ ] Vašo Patejdl
- [ ] Jožo Ráž
- [ ] Robo Grigorov
- [ ] Maťo Ďurinda
- [ ] Ivan Tásler
- [ ] Igor Timko
- [ ] Yxo (Hex)
- [ ] Daniel Heriban
- [ ] Peter Bič

## SK — pop 2000.–2010.

- [ ] Tina (Tatiana Okapcová)
- [ ] Kristína Peláková
- [ ] Adam Ďurica
- [ ] Tomáš Bezdeda
- [ ] Sima Martausová
- [ ] Celeste Buckingham
- [ ] Sisa Sklovska
- [ ] Mária Čírová
- [ ] Dara Rolins
- [ ] Lina Mayer
- [ ] Robo Opatovský
- [ ] Otto Weiter
- [ ] Roman Méry
- [ ] Helena Krajčiová
- [ ] Mária Podhradská

## SK — folk / world / spirituál

- [ ] Hana Hegerová
- [ ] Janka Guzová
- [ ] Sláva Štochlová
- [ ] Zuzana Smatanová
- [ ] Katka Knechtová
- [ ] Jana Kirschner
- [ ] Beáta Dubasová
- [ ] Zuzana Mauréry
- [ ] Marián Čekovský
- [ ] Ondrej Kandráč

## SK — hip-hop / urban

- [ ] Patrik Vrbovský (Rytmus)
- [ ] Vec
- [ ] Tono S
- [ ] Strapo
- [ ] Separ
- [ ] Ego
- [ ] Majk Spirit
- [ ] Mirka Partlová

## CZ — klasici

- [ ] Karel Gott
- [ ] Helena Vondráčková
- [ ] Hana Zagorová
- [ ] Václav Neckář
- [ ] Karel Kryl
- [ ] Karel Plíhal
- [ ] Marie Rottrová
- [ ] Eva Pilarová
- [ ] Petra Janů
- [ ] Naďa Urbánková
- [ ] Pavel Bobek
- [ ] Petr Spálený
- [ ] Karel Černoch
- [ ] Yvonne Přenosilová
- [ ] Jitka Zelenková
- [ ] Vladimír Mišík
- [ ] Petr Hapka

## CZ — pop/rock 80.–2000.

- [ ] Lucie Bílá
- [ ] Iveta Bartošová
- [ ] Jiří Korn
- [ ] Daniel Hůlka
- [ ] Daniel Landa
- [ ] Petr Janda
- [ ] Aleš Brichta
- [ ] Vlasta Redl
- [ ] Jaromír Nohavica
- [ ] Jarek Nohavica (alias?)
- [ ] Janek Ledecký
- [ ] Tomáš Klus
- [ ] Pokáč

## CZ — mladšia generácia

- [ ] Marek Ztracený
- [ ] Adam Mišík
- [ ] Vojtěch Dyk
- [ ] Ben Cristovao
- [ ] Aneta Langerová
- [ ] Ewa Farna
- [ ] Mirai Navrátil
- [ ] Calin
- [ ] Pavel Callta

## SK + CZ — folkové / etno / svetová sféra

- [ ] Iva Bittová
- [ ] Vlasta Třešňák
- [ ] Petr Skoumal
- [ ] Karel Vepřek

## Iní (zvážiť — niektorí sú prevažne na hraniciach žánru)

- [ ] Marián Geišberg
- [ ] Marián Lapšanský (klavirista — možno preč)
- [ ] Robo Papp
- [ ] Anna K (CZ)
- [ ] Olga Lounová (CZ)

---

**Po Marošovej úprave:** dám SQL `INSERT INTO pmgonijet_cct_artists (cct_status, artist, cct_created) VALUES (…)` pre všetky povolené.
