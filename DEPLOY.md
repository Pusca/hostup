# Deploy su hosting condiviso (DirectAdmin / cPanel)

Il dominio serve da `~/domains/hostup.it/public_html`, ma Laravel deve essere
servito dalla sottocartella `public/`. Si risolve con un `.htaccess` che
"incanala" tutte le richieste dentro `public/` (e blocca i file sensibili).

L'app vive direttamente in `public_html` (clonata da git). Gli asset front-end
(`public/build`) sono **versionati** perché il server non ha Node/npm.

## 1. `.htaccess` nella root di `public_html`

Crea `~/domains/hostup.it/public_html/.htaccess` con:

```apache
RewriteEngine On

# Blocca l'accesso diretto a file sensibili
RewriteRule ^\.env - [F,L]
RewriteRule ^\.git(/|$) - [F,L]
RewriteRule ^(composer\.(json|lock)|package(-lock)?\.json|artisan)$ - [F,L]

# Incanala tutte le richieste dentro la cartella public/ di Laravel
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]
```

(La `public/.htaccess` di Laravel gestisce poi il front controller.)

## 2. File `.env`

Crea `~/domains/hostup.it/public_html/.env` (vedi `.env.example`). Minimo:

```dotenv
APP_NAME=HostUp
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://hostup.it
APP_LOCALE=it

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=__nome_db__
DB_USERNAME=__utente_db__
DB_PASSWORD=__password_db__

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Stripe (chiavi LIVE o TEST). Vuoto = modalità demo.
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Crea il database MySQL dal pannello (MySQL Management) e metti qui le credenziali.

## 3. Comandi (dalla cartella `public_html`)

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed         # crea schema + admin + canali + servizi
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

Admin iniziale: **admin@hostup.it** / **password** (cambiala subito).

> Gli asset sono già compilati e versionati: niente `npm` sul server.

## 4. Cron per la sincronizzazione iCal

Dal pannello aggiungi un cron job:

```
* * * * * cd ~/domains/hostup.it/public_html && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Foto immobili

Le foto in `storage/app/public/properties` non sono versionate: caricale
dall'admin del sito, oppure trasferiscile via FTP nella stessa cartella.

## Aggiornamenti successivi

```bash
cd ~/domains/hostup.it/public_html
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```
