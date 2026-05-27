# EventKviz plugin — pokyny pre Claude

## Formátovanie výstupu — ŽIADNY inline code, VŽDY bold (NAJVYŠŠIA PRIORITA)

NIKDY nepoužívať inline code (text v spätných apostrofoch / backtickoch) v odpovediach používateľovi (Maroš). V jeho termináli sa renderuje **svetlomodrým písmom, ktoré je nečitateľné** — opakovane ho to frustruje.

VŽDY namiesto toho **tučné písmo (bold)** — pre názvy súborov, funkcie, premenné, hooky, meta kľúče, hodnoty, príkazy, čokoľvek, čo by inak išlo do backtickov. Default markdown návyk používať backticky pre kód MUSÍŠ vedome potlačiť. Pred každou odpoveďou over, že neobsahuje spätné apostrofy okrem viacriadkových code-fence blokov (tie sú OK — renderujú sa čitateľne).

Toto prebíja default štýl. Platí pre hlavné odpovede aj reporty subagentov.

## Projekt
EventKviz = WordPress plugin (MAMP localhost → deploy na eventkviz.sk prod). SemVer, verzia na 2 miestach v eventkviz.php (hlavička + EVENKVIZ_VERSION define) + CHANGELOG.md. Deploy local → prod cez deploy/PLAYBOOK.md (deploy.sh) — prepisuje CELÚ prod DB local verziou; vždy predeploy changelist + explicit OK pred --yes. EK je zdroj dát pre GeoChallenge ek-quiz (REST export /wp-json/eventkviz/v1/export/<type>).
