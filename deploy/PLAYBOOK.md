# 🚀 Eventkviz Deploy Playbook

**Trigger phrase:** „**deploy local na prod**" (alebo skratka „**deploy**")

Keď Maroš povie „deploy local na prod", Claude vie čo robiť bez nutnosti opakovať info nižšie.

---

## Čo Claude vie a má uložené (nemusíš opakovať)

| Vec | Hodnota | Kde |
|---|---|---|
| Hosting | Websupport.sk | constant |
| SSH host | `shell.r4.websupport.sk` | `.env` |
| SSH user | `uid3020168` | `.env` |
| MySQL host | `db.r4.websupport.sk:3306` | `.env` |
| MySQL DB / user / pass | `eventkviz2` / `eventkviz2` / `Pn9DfIUBMA` | `.env` |
| Prod WP root | `/data/c/5/c56ccf41.../eventkviz.sk/web` | `.env` |
| Prod plugin path | `…/wp-content/plugins/eventkviz` (git repo, sync s GitHub) | `.env` |
| Local MAMP | `/Applications/MAMP/htdocs/eventkviz` | constant |
| Active theme | `hello-theme-child-master` (na obidvoch) | known |
| Snippet ID 45 | „Stanovistovka - tlac diplomy" — vyžaduje `get_stylesheet_directory()` (NIE local path) | fixed |

---

## Workflow pri každom „deploy local na prod"

### Krok 1 (TY) — Aktivuj shell konzolu (60 sec)

Websupport admin → **Pokročilá konfigurácia → Konzola** → klik **„Aktivovať"** (free, 1h TTL).

Po aktivácii sa zobrazia **nové credentials** ktoré sa menia každú aktiváciu:
- **SSH Port** (napr. 29740)
- **SSH Heslo** (napr. 5258daa825)

SSH **host** (`shell.r4.websupport.sk`) a **user** (`uid3020168`) sú stable, nemenia sa.

### Krok 2 (TY) — Pošli mi nové credentials

Stačí krátka správa:
```
Port: 29740
Heslo: 5258daa825
```

(Alebo: pošli mi screenshot Websupport admin konzoly — vyextrahujem si sám.)

### Krok 3 (JA) — Update .env + pre-deploy check

Updatnem `deploy/.env` s novými credentials, spustím `discovery.sh` pre overenie:
- SSH connect
- WP-CLI dostupný
- MySQL prod
- Git sync (local = GitHub main)
- Lokálne MAMP MySQL beží
- Žiadne aktívne Code Snippets s lokálnym `require_once` path

Ak niečo nesedí, poviem ti.

### Krok 4 (JA) — Real deploy

`bash deploy/deploy.sh --yes` — automaticky:
1. Backup prod DB → `deploy/backups/prod-db-<TS>.sql.gz` (rollback ready)
2. Dump local DB
3. Upload na prod (SCP, malý súbor)
4. `wp db import` (prepíše prod DB)
5. `wp search-replace` URL `localhost:8888/eventkviz` → `eventkviz.sk`
6. `rsync` uploads (delta sync, len changed files)
7. `git pull` plugin code z GitHub
8. `wp cache flush` + `wp rewrite flush`

**Typický čas: ~11 sekúnd** (druhý a ďalšie deploys; prvý môže byť dlhší kvôli rsync inital sync).

### Krok 5 (TY) — Otestuj prod

`https://eventkviz.sk` — pozri či všetko funguje. Ak nie, **rollback**:

```bash
bash deploy/rollback.sh latest
```

---

## Pred-deploy checklist (pre teba)

Pred tým ako povieš „deploy":

- [ ] Všetky lokálne zmeny v `.git` commit-nuté a push-nuté na GitHub `mahroch/eventkviz-plugin/main`
  - (Ak nie, deploy zlyhá v pre-flight check)
- [ ] Code Snippets v local DB ktoré majú `require_once '/Applications/MAMP/...'` sú buď deaktivované alebo prepísané na `get_stylesheet_directory() . '/path'`
  - (Aktívny snippet s local path → fatal error na prod = WP rozbité)
- [ ] Local MAMP server beží (`http://localhost:8888/eventkviz/` odpovedá HTTP 200)
- [ ] Shell konzola na Websupporte aktivovaná (≤ 1h)

---

## Štandardné odpovede / signály

| Ty povieš | Ja robím |
|---|---|
| „deploy local na prod" alebo „deploy" | full deploy podľa workflow vyššie |
| „rollback" | `bash deploy/rollback.sh latest` |
| „prečo zlyhalo" | ukážem log z deploy + diagnostikujem |
| „skús bez uploads" | `bash deploy/deploy.sh --yes --skip-uploads` |
| „dry-run" | `bash deploy/deploy.sh --dry-run` |

---

## Časté problémy + riešenia

### „Connection refused" pri SSH
SSH konzola už exspirovala (1h TTL minula). Aktivuj znova (krok 1).

### „Plugin má uncommitted zmeny"
Pre-flight check zabraňuje deploy keď local plugin nie je sync s GitHub-om. Stačí:
```bash
cd /Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz
git add -A && git commit -m "deploy: doladenia" && git push
```

### „Fatal error: Failed opening required …MAMP…"
Aktívny Code Snippet má hardcoded local path. Pre-flight check by ho mal zachytiť — ak nie, dočasne deaktivuj snippet alebo prepíš path.

### „rsync: command not found" / weird flags error
macOS má `openrsync` (Apple) namiesto GNU rsync. Niektoré flagy nepodporuje. Skript je už nakonfigurovaný kompatibilne (`-avz --partial`).

### Po deploy je prod rozbitý (white screen)
```bash
bash /Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/deploy/rollback.sh latest
```

---

## Pre Claude: kde mám info na disku

- **Repo:** `mahroch/eventkviz-plugin` (GitHub)
- **Local plugin path:** `/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz`
- **Deploy scripts:** `deploy/deploy.sh`, `deploy/rollback.sh`, `deploy/discovery.sh`
- **Credentials:** `deploy/.env` (gitignored — nikdy nie v repo)
- **Backupy:** `deploy/backups/prod-db-*.sql.gz` (gitignored)
- **MAMP PHP CLI:** `/Applications/MAMP/bin/php/php8.2.0/bin/php`
- **MAMP MySQL CLI:** `/Applications/MAMP/Library/bin/mysql` + `mysqldump`

---

## TODO — future improvements

- [ ] **Prémiová shell konzola na Websupporte (1€/mes)** — bez 1h TTL by ušetrila krok 1 (manuálnu aktiváciu pri každom deployi)
- [ ] **SSH key auth** namiesto password — bezpečnejšie + nemusí sa každú aktiváciu nové heslo passovať
- [ ] **GitHub Actions CI** pre auto-deploy pri push to main — plne automatizované, žiadny user interaction
