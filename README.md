# HostUp — Channel Manager & Prenotazione Diretta

Applicativo per la gestione di immobili in affitto breve: vetrina pubblica con
prenotazione diretta + backend channel manager con sincronizzazione iCal verso
le OTA (Airbnb, Booking, ...).

Stack: **Laravel 13**, **MySQL**, **Tailwind CSS v4 / Vite**, **Stripe**.

## Componenti

- **Sito pubblico** — home vetrina foto-centrica, schede immobile, preventivo e
  prenotazione diretta con pagamento Stripe.
- **Admin** (`/admin`) — gestione immobili, foto (upload + drag reorder),
  servizi, prezzi, calendario e canali iCal.
- **Calendario master** (`availability`) — unica sorgente di verità: ogni
  prenotazione (diretta o da OTA) aggiorna il master.
- **Sync iCal** — import dei calendari OTA + export del feed `.ics` per ogni
  immobile; comando schedulato ogni 15 minuti.

## Setup locale

```bash
composer install
npm install
cp .env.example .env        # configura DB MySQL + (opzionale) chiavi Stripe
php artisan key:generate
php artisan migrate --seed   # crea schema + dati demo + admin
php artisan storage:link
npm run build                # oppure: npm run dev
php artisan serve
```

Admin demo: **admin@hostup.it** / **password**

## Sincronizzazione iCal

- **Export** (noi → OTA): incolla in Airbnb/Booking il link `/ical/{token}.ics`
  di ogni immobile (lo trovi nell'admin).
- **Import** (OTA → noi): inserisci nell'admin gli URL iCal di Airbnb/Booking;
  vengono importati ogni 15 minuti.

Cron in produzione (una sola riga):

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Pagamenti (Stripe)

Imposta in `.env`: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
Senza chiavi l'app gira in **modalità demo** (conferma simulata, nessun
addebito reale).

> Le foto degli immobili in `storage/app/public/properties` NON sono versionate.
