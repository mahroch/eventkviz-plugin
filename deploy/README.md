# EventKviz deploy — local MAMP → Websupport prod

Plne automatizovaný deploy z lokálneho MAMP na produkciu **eventkviz.sk**.

## Čo to robí

1. **Backup prod stavu** (DB + uploads) → `deploy/backups/` na localhost (rollback ready)
2. **Dump local** DB + uploads
3. **Upload** na prod cez SSH/SCP
4. **Restore prod DB** z lokálneho dumpu (drop & recreate)
5. **URL replace** `http://localhost:8888/eventkviz` → `https://eventkviz.sk` (cez WP-CLI, serialization-safe)
6. **Extract uploads** (fotky pinov a pod.)
7. **Git pull** plugin code z GitHub `mahroch/eventkviz-plugin`
8. **Flush** cache + rewrite rules

Trvanie: ~3-5 min/deploy.

## Setup (jednorázový)

### 1. Aktivuj shell konzolu na Websupporte

Websupport admin → **Pokročilá konfigurácia → Konzola** → klik **„Aktivovať"** pri „Základná shell konzola" (free, 256 MB RAM, 1h session — pre náš deploy bohato stačí).

Po aktivácii ti Websupport ukáže SSH host/port/user/heslo.

### 2. Skopíruj `.env.example` → `.env` a vyplň

```bash
cd deploy
cp .env.example .env
nano .env
```

Vyplň:
- `PROD_SSH_*` — z bodu 1
- `PROD_DB_*` — z Websupport admin → Pokročilá konfigurácia → Databázy → Detail
- `PROD_WP_PATH` — necháš zatiaľ prázdne; `discovery.sh` ti to nájde

### 3. Spusti discovery — overí prostredie

```bash
bash deploy/discovery.sh
```

Outputuje:
- ✅ SSH funguje
- WP-CLI dostupný / chýba (ak chýba, ukáže install command)
- ✅ MySQL prod funguje
- Nájde `wp-config.php` na prod a navrhne `PROD_WP_PATH`
- ✅ Local MAMP MySQL funguje

Doplň zistené paths do `.env`.

### 4. Inštaluj sshpass (ak používaš password auth)

```bash
brew install hudochenkov/sshpass/sshpass
```

(Alternatíva: nastav SSH key — `ssh-keygen` + `ssh-copy-id`, lepšie pre bezpečnosť.)

### 5. (Voliteľne) Inštaluj WP-CLI na prod

```bash
ssh user@eventkviz.sk -p PORT
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
exit
```

Bez WP-CLI funguje fallback raw SQL URL replace (ale môže pokaziť serialized data v option-och — riziko).

### 6. (Jednorázovo) Clone plugin repo na prod

Ak plugin folder na prod **nie je git repo**, `git pull` zlyhá. Treba ho jednorazovo nahradiť cez clone:

```bash
ssh user@eventkviz.sk -p PORT
cd /path/to/wp-content/plugins
mv eventkviz eventkviz-backup
git clone https://github.com/mahroch/eventkviz-plugin.git eventkviz
exit
```

Pri ďalšom deploy už stačí `git pull`.

## Deploy

### Real deploy

```bash
bash deploy/deploy.sh
```

Zobrazí confirm prompt po backup-e, čaká na `yes`.

### Dry-run (otestuj že všetko prejde bez reálnych zmien)

```bash
bash deploy/deploy.sh --dry-run
```

### Skip confirm (automation)

```bash
bash deploy/deploy.sh --yes
```

## Rollback

Backupy sa ukladajú do `deploy/backups/` ako:
- `prod-db-2026-05-18-141522.sql.gz`
- `prod-uploads-2026-05-18-141522.tar.gz`

```bash
bash deploy/rollback.sh                    # zoznam dostupných backup-ov
bash deploy/rollback.sh latest             # posledný backup
bash deploy/rollback.sh 2026-05-18-141522  # konkrétny timestamp
```

## Bezpečnostné poznámky

- ⚠ `deploy/.env` obsahuje **prod credentials** — je v `.gitignore`. **NIKDY** ho neommit-uj.
- ⚠ Backupy v `deploy/backups/*.sql.gz` obsahujú **prod data** — sú v `.gitignore`.
- ⚠ `deploy.sh` má `--yes` flag pre automation. Pre prvé runs **vždy** používaj interaktívny mód aby si videl backup paths a mohol rollback.

## Limity Websupport free shell

- **1h session** — deploy trvá 3-5 min, OK
- **256 MB RAM** — pre `mysqldump` + `mysql import` malých DB (eventkviz typicky <10 MB) bohato
- **Žiadny cron** — náš deploy nepotrebuje cron, spúšťa sa ručne z lokálu

Ak budeš v budúcnosti chcieť cron joby na prod (napr. auto-cleanup), zaplať za **Prémiovú shell konzolu** (1€/mesiac).

## Troubleshooting

**`sshpass: command not found`**
```bash
brew install hudochenkov/sshpass/sshpass
```

**`Permission denied (publickey)`**
SSH key auth zlyhal. Buď použij heslo cez `PROD_SSH_PASS` v `.env`, alebo nahraj svoj public key na prod cez `ssh-copy-id`.

**`Host key verification failed`**
Prvý SSH connect — script má `StrictHostKeyChecking=accept-new` takže by si mal akceptovať automaticky.

**`mysqldump: command not found`**
MAMP MySQL binárky sú v `/Applications/MAMP/Library/bin/`. Skript ho hľadá tam; ak používaš iný MAMP path, nastav v `.env`:
```env
LOCAL_MYSQL_BIN=/Applications/MAMP/Library/bin/mysql
LOCAL_MYSQLDUMP_BIN=/Applications/MAMP/Library/bin/mysqldump
```

**Po deployi sa stránka rozbije (white screen, fatal error)**
```bash
bash deploy/rollback.sh latest
```

**WP-CLI: `Error establishing a database connection`**
WP na prod má v `wp-config.php` iné DB credentials. WP-CLI funguje cez wp-config — netreba mu nič passovať.
