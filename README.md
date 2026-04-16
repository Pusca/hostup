# hostup
Gestione affitti brevi con CRM lead e MVP di channel manager.

## Moduli
- landing e raccolta lead
- CRM interno in `crm/`
- channel manager MVP in `crm/channel/`
- booking engine diretto in `booking.php`

## Setup locale rapido
1. Avvia MySQL locale, ad esempio con XAMPP.
2. Esegui `php crm/install.php --admin-host=127.0.0.1`
3. Verifica o configura le env:
   `HOSTUP_DB_HOST`, `HOSTUP_DB_PORT`, `HOSTUP_DB_NAME`, `HOSTUP_DB_USER`, `HOSTUP_DB_PASS`
4. Apri `/crm/register.php` per creare l'utente iniziale oppure usa la login se l'utente esiste già.
5. Accedi a `/crm/channel/index.php` per gestire clienti, immobili, calendario unico e feed iCal.

## Sync canali
- export iCal immobile: `/crm/channel/ical.php?token=...`
- cron import iCal: `php crm/channel/cron_sync.php`

## Note
- il primo rilascio usa iCal come base affidabile per Booking/Airbnb
- le API native dei portali vanno aggiunte in una fase successiva, sopra la struttura già introdotta
