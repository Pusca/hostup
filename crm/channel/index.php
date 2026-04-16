<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  try {
    if ($action === 'save_client') {
      $clientId = cm_save_client($_POST);
      cm_flash_set('success', 'Cliente salvato (#' . $clientId . ').');
      cm_redirect(cm_base_url('index.php'));
    }

    if ($action === 'save_property') {
      $propertyId = cm_save_property($_POST);
      cm_flash_set('success', 'Immobile salvato (#' . $propertyId . ').');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    if ($action === 'sync_all') {
      $results = cm_sync_all_ical_connections();
      $okCount = count(array_filter($results, static fn(array $row): bool => ($row['status'] ?? '') === 'ok'));
      $errorCount = count($results) - $okCount;
      cm_flash_set('success', 'Sync completato. OK: ' . $okCount . ', errori: ' . $errorCount . '.');
      cm_redirect(cm_base_url('index.php'));
    }

    throw new RuntimeException('Azione non valida.');
  } catch (Throwable $e) {
    cm_flash_set('error', $e->getMessage());
    cm_redirect(cm_base_url('index.php'));
  }
}

$editClientId = (int)($_GET['edit_client'] ?? 0);
$editClient = $editClientId > 0 ? cm_client($editClientId) : null;
$stats = cm_dashboard_stats();
$clients = cm_clients();
$properties = cm_properties();
$bookings = cm_recent_bookings(20);
$flash = cm_flash_get();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp Channel Manager</title>
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/crm.css">
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/channel.css">
</head>
<body class="crm-bg">
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div class="bn">HostUp <span>Channel</span></div>
      </div>
      <div class="right">
        <a class="btn" href="<?= cm_h(CRM_BASE_URL) ?>/index.php">CRM</a>
        <div class="who"><?= cm_h($u['name']) ?></div>
        <a class="btn" href="<?= cm_h(CRM_BASE_URL) ?>/logout.php">Esci</a>
      </div>
    </div>
  </header>

  <main class="wrap">
    <section class="head">
      <div>
        <h1>Channel Manager</h1>
        <p>Gestione clienti, immobili, canali e calendario unico. Questo MVP copre prenotazione diretta, import/export iCal e pannello operativo interno.</p>
      </div>
      <div class="actions">
        <form method="post">
          <input type="hidden" name="action" value="sync_all">
          <button class="btn" type="submit">Sync tutti i feed iCal</button>
        </form>
      </div>
    </section>

    <?php if ($flash): ?>
      <div class="cm-alert <?= cm_h($flash['type']) ?>"><?= cm_h($flash['message']) ?></div>
    <?php endif; ?>

    <section class="cm-stats">
      <article class="cm-stat">
        <div class="cm-stat-label">Clienti</div>
        <div class="cm-stat-value"><?= cm_h($stats['clients']) ?></div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Immobili</div>
        <div class="cm-stat-value"><?= cm_h($stats['properties']) ?></div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Pubblicati</div>
        <div class="cm-stat-value"><?= cm_h($stats['published_properties']) ?></div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Prenotazioni attive</div>
        <div class="cm-stat-value"><?= cm_h($stats['active_bookings']) ?></div>
      </article>
    </section>

    <section class="cm-grid">
      <article class="box cm-panel">
        <div class="boxTitle"><?= $editClient ? 'Modifica cliente' : 'Nuovo cliente' ?></div>
        <form method="post" class="cm-form">
          <input type="hidden" name="action" value="save_client">
          <?php if ($editClient): ?>
            <input type="hidden" name="id" value="<?= (int)$editClient['id'] ?>">
          <?php endif; ?>
          <label>Nome referente</label>
          <input name="name" required placeholder="Mario Rossi" value="<?= cm_h($editClient['name'] ?? '') ?>" />
          <label>Ragione sociale</label>
          <input name="company_name" placeholder="Rossi Hospitality SRL" value="<?= cm_h($editClient['company_name'] ?? '') ?>" />
          <label>Email</label>
          <input name="email" type="email" placeholder="info@cliente.it" value="<?= cm_h($editClient['email'] ?? '') ?>" />
          <label>Telefono</label>
          <input name="phone" placeholder="+39..." value="<?= cm_h($editClient['phone'] ?? '') ?>" />
          <label>P.IVA</label>
          <input name="vat_number" placeholder="IT..." value="<?= cm_h($editClient['vat_number'] ?? '') ?>" />
          <label>Note</label>
          <textarea name="notes" class="textarea" placeholder="Vincoli operativi, note contrattuali, SLA..."><?= cm_h($editClient['notes'] ?? '') ?></textarea>
          <button class="btn-primary" type="submit"><?= $editClient ? 'Aggiorna cliente' : 'Salva cliente' ?></button>
          <?php if ($editClient): ?>
            <a class="btn" href="<?= cm_h(cm_base_url('index.php')) ?>">Nuovo cliente</a>
          <?php endif; ?>
        </form>
      </article>

      <article class="box cm-panel">
        <div class="boxTitle">Nuovo immobile</div>
        <?php if (!$clients): ?>
          <p>Prima crea almeno un cliente proprietario.</p>
        <?php else: ?>
          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="save_property">
            <label>Cliente</label>
            <select name="client_id" class="select" required>
              <option value="">Seleziona cliente</option>
              <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client['id'] ?>"><?= cm_h($client['name']) ?></option>
              <?php endforeach; ?>
            </select>

            <div class="cm-form-split">
              <div>
                <label>Nome immobile</label>
                <input name="name" required placeholder="Appartamento Duomo" />
              </div>
              <div>
                <label>Slug URL</label>
                <input name="slug" placeholder="appartamento-duomo" />
              </div>
            </div>

            <label>Descrizione</label>
            <textarea name="description" class="textarea" placeholder="Descrizione breve per booking engine e portali."></textarea>

            <div class="cm-form-split">
              <div>
                <label>Indirizzo</label>
                <input name="address_line1" placeholder="Via Roma 10" />
              </div>
              <div>
                <label>Città</label>
                <input name="city" placeholder="Roma" />
              </div>
            </div>

            <div class="cm-form-third">
              <div>
                <label>Regione</label>
                <input name="region" placeholder="Lazio" />
              </div>
              <div>
                <label>Paese</label>
                <input name="country_code" value="IT" maxlength="2" />
              </div>
              <div>
                <label>Timezone</label>
                <input name="timezone_name" value="Europe/Rome" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Camere</label>
                <input name="bedrooms" type="number" min="0" value="1" />
              </div>
              <div>
                <label>Bagni</label>
                <input name="bathrooms" type="number" min="0.5" step="0.5" value="1" />
              </div>
              <div>
                <label>Letti</label>
                <input name="beds" type="number" min="1" value="1" />
              </div>
              <div>
                <label>Ospiti max</label>
                <input name="max_guests" type="number" min="1" value="2" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Notti minime</label>
                <input name="min_nights" type="number" min="1" value="1" />
              </div>
              <div>
                <label>Check-in da</label>
                <input name="checkin_from" type="time" value="15:00" />
              </div>
              <div>
                <label>Check-in fino</label>
                <input name="checkin_until" type="time" value="20:00" />
              </div>
              <div>
                <label>Check-out entro</label>
                <input name="checkout_until" type="time" value="10:00" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Prezzo base/notte</label>
                <input name="base_price" type="number" min="0" step="0.01" value="0" />
              </div>
              <div>
                <label>Pulizie</label>
                <input name="cleaning_fee" type="number" min="0" step="0.01" value="0" />
              </div>
              <div>
                <label>Valuta</label>
                <input name="currency" value="EUR" maxlength="3" />
              </div>
              <div>
                <label>Notice ore</label>
                <input name="booking_notice_hours" type="number" min="0" value="24" />
              </div>
            </div>

            <label class="cm-check"><input type="checkbox" name="direct_booking_enabled" value="1" checked> Abilita prenotazione diretta</label>
            <label class="cm-check"><input type="checkbox" name="published" value="1"> Pubblica nel booking engine</label>
            <button class="btn-primary" type="submit">Salva immobile</button>
          </form>
        <?php endif; ?>
      </article>
    </section>

    <section class="box cm-panel" style="margin-top:18px;">
      <div class="tableTop">
        <div class="tt">Clienti gestiti</div>
        <div class="hint">Modifica anagrafica e monitora il numero di immobili associati.</div>
      </div>
      <div class="tableWrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Azienda</th>
              <th>Contatti</th>
              <th>Immobili</th>
              <th>Azione</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $client): ?>
              <tr>
                <td><?= cm_h($client['name']) ?></td>
                <td><?= cm_h($client['company_name'] ?: '-') ?></td>
                <td>
                  <?= cm_h($client['email'] ?: '-') ?>
                  <div class="cm-muted"><?= cm_h($client['phone'] ?: '-') ?></div>
                </td>
                <td><?= (int)$client['property_count'] ?></td>
                <td><a class="btn" href="<?= cm_h(cm_base_url('index.php') . '?edit_client=' . (int)$client['id']) ?>">Modifica</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$clients): ?>
              <tr><td colspan="5">Nessun cliente presente.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="box cm-panel">
      <div class="tableTop">
        <div class="tt">Immobili gestiti</div>
        <div class="hint">Apri il dettaglio per calendario, sync canali, blocchi manuali e URL pubblici.</div>
      </div>
      <div class="tableWrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Immobile</th>
              <th>Cliente</th>
              <th>Luogo</th>
              <th>Capienza</th>
              <th>Canale diretto</th>
              <th>Attive</th>
              <th>Azioni</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($properties as $property): ?>
              <tr>
                <td><?= cm_h($property['name']) ?></td>
                <td><?= cm_h($property['client_name']) ?></td>
                <td><?= cm_h(trim(($property['city'] ?: '') . ' ' . ($property['region'] ? '(' . $property['region'] . ')' : ''))) ?></td>
                <td><?= (int)$property['max_guests'] ?> ospiti / <?= (int)$property['beds'] ?> letti</td>
                <td><?= (int)$property['published'] ? 'Pubblicato' : 'Bozza' ?></td>
                <td><?= (int)$property['active_booking_count'] ?></td>
                <td class="cm-actions-cell">
                  <a class="btn" href="<?= cm_h(cm_base_url('property.php') . '?id=' . (int)$property['id']) ?>">Gestisci</a>
                  <a class="btn" target="_blank" rel="noopener" href="<?= cm_h(cm_direct_booking_url($property)) ?>">Pagina diretta</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$properties): ?>
              <tr><td colspan="7">Nessun immobile configurato.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="box cm-panel" style="margin-top:18px;">
      <div class="tableTop">
        <div class="tt">Prenotazioni recenti</div>
        <div class="hint">Calendario unico consolidato da direct booking, blocchi manuali e feed esterni.</div>
      </div>
      <div class="tableWrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Immobile</th>
              <th>Canale</th>
              <th>Ospite / blocco</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Stato</th>
              <th>Totale</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
              <tr>
                <td><?= cm_h($booking['property_name']) ?></td>
                <td><?= cm_h(cm_channel_label((string)$booking['source_channel'])) ?></td>
                <td><?= cm_h($booking['guest_name'] ?: ($booking['summary'] ?: '-')) ?></td>
                <td><?= cm_h(cm_fmt_date($booking['checkin_date'])) ?></td>
                <td><?= cm_h(cm_fmt_date($booking['checkout_date'])) ?></td>
                <td><?= cm_h(cm_booking_status_label((string)$booking['status'])) ?></td>
                <td><?= cm_h(cm_fmt_money((float)$booking['total_amount'], (string)$booking['currency'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
              <tr><td colspan="7">Nessuna prenotazione presente.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
