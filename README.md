# OMS

Aplicatie Laravel pentru planificarea meselor de santier: zile, congregatii, feluri principale, ciorbe, portii estimate, costuri, alergeni, rapoarte si fise personalizate pentru fiecare congregatie.

## Cerinte

- PHP 8.2 sau mai nou, cu extensiile `pdo_mysql`, `mbstring`, `xml`, `ctype`, `json`, `openssl` si `zip`.
- Composer 2.
- MySQL sau MariaDB.

## Instalare locala

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Aplicatia este disponibila la `http://127.0.0.1:8000/admin`.

## Deploy pe Hostico pentru modulia.app

1. In cPanel, creeaza o baza MySQL si un utilizator dedicat. Acorda-i drepturi doar pentru baza OMS.
2. Configureaza domeniul `modulia.app` cu Document Root spre `/home/UTILIZATOR/oms/public`.
3. Cloneaza repository-ul sau incarca proiectul in `/home/UTILIZATOR/oms`. Nu incarca `.env`, `vendor`, `node_modules` sau baza SQLite locala.
4. Creeaza `/home/UTILIZATOR/oms/.env` din `.env.example`, completeaza datele MySQL si pastreaza `APP_ENV=production`, `APP_DEBUG=false` si `APP_URL=https://modulia.app`.
5. In terminalul cPanel, din directorul proiectului, ruleaza:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

6. Exporturile CSV/XLSX au nevoie de coada Laravel. Daca Hostico permite worker persistent/Supervisor, ruleaza `php artisan queue:work`. Altfel seteaza `QUEUE_CONNECTION=sync` in `.env` pentru exporturi mici.

## Actualizare dupa GitHub

```bash
cd /home/UTILIZATOR/oms
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

## Verificare

```bash
php artisan test
```

Planul functional este in [plan.md](plan.md), iar progresul dezvoltarii in [progress.md](progress.md).