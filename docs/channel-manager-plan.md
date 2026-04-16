# Piano Channel Manager HostUp

## Obiettivo
Realizzare un channel manager proprietario per gestire:
- più clienti proprietari
- più immobili per cliente
- booking engine diretto
- calendario unico consolidato
- sincronizzazione con Airbnb e Booking.com

## Architettura consigliata
### Livello 1: Core interno
- anagrafica clienti
- anagrafica immobili
- calendario unico
- prenotazioni dirette
- blocchi manuali
- motore disponibilità

### Livello 2: Connettori canale
- iCal import/export per MVP operativo
- adapter API native per Booking.com
- adapter API native per Airbnb
- log di sincronizzazione

### Livello 3: Funnel e operatività
- pagina prenotazione diretta
- gestione richieste in stato `pending`
- eventuale conferma manuale o automazione con pagamento
- in futuro CRM lead e prenotazioni nello stesso backoffice

## Fasi di realizzazione
### Fase 1 - MVP operativo
- schema database dedicato al channel manager
- dashboard amministrativa
- CRUD clienti e immobili
- calendario unico interno
- pagina pubblica di prenotazione diretta
- export iCal per ogni immobile
- import iCal da Booking/Airbnb o altri portali
- cron di sincronizzazione

### Fase 2 - Hardening prodotto
- policy cancellazioni
- vincoli soggiorno minimo/massimo
- fees, tasse, extra ospiti
- notifiche email
- audit log operazioni
- permessi utenti e ruoli

### Fase 3 - Integrazioni native
- Booking.com Reservations / Availability / Rates API tramite onboarding partner
- Airbnb software connectivity con mapping listing e sync dedicato
- riconciliazione modifiche/cancellazioni real time
- gestione errori e retry queue

### Fase 4 - Monetizzazione e automazioni
- pagamento online
- coupon e offerte
- regole tariffarie
- upsell e servizi extra
- documenti ospiti e check-in online

## Modello dati introdotto
- `cm_clients`: anagrafica proprietari/clienti
- `cm_properties`: immobili e configurazione prenotabile
- `cm_channel_connections`: collegamenti ai canali esterni
- `cm_bookings`: prenotazioni unificate e blocchi manuali
- `cm_sync_logs`: storico sincronizzazioni

## Decisioni chiave
- ogni immobile ha uno `slug` pubblico e un token iCal dedicato
- il calendario unico è la fonte di verità interna
- il booking engine diretto scrive nello stesso calendario dei canali
- il primo rilascio usa iCal come baseline affidabile e veloce da attivare
- le API native restano previste ma non vengono simulate senza accessi ufficiali

## Limiti noti del primo rilascio
- niente pagamento online
- niente pricing avanzato multistagione
- niente foto/galleria o contenuti marketing evoluti
- niente webhook real time dai portali
- sync iCal dipendente dalla frequenza del cron

## Setup operativo previsto
1. Creare cliente
2. Creare immobile
3. Pubblicare immobile nel booking engine diretto
4. Copiare URL iCal export verso i portali
5. Inserire negli immobili gli URL iCal import di Airbnb/Booking
6. Eseguire `php crm/channel/cron_sync.php` via cron
7. Monitorare le prenotazioni unificate dal pannello
