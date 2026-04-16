<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$propertyId = (int)($_GET['id'] ?? 0);
$property = $propertyId > 0 ? cm_property($propertyId) : null;

if (!$property) {
  http_response_code(404);
  echo 'Immobile non trovato.';
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  try {
    if ($action === 'save_property') {
      cm_save_property(array_merge($_POST, ['id' => $propertyId]), $_FILES);
      cm_flash_set('success', 'Immobile aggiornato.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    if ($action === 'save_connection') {
      cm_save_connection(array_merge($_POST, ['property_id' => $propertyId]));
      cm_flash_set('success', 'Connessione canale salvata.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    if ($action === 'sync_connection') {
      $result = cm_sync_ical_connection((int)($_POST['connection_id'] ?? 0));
      cm_flash_set('success', $result['message']);
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    if ($action === 'create_block') {
      cm_create_manual_block(array_merge($_POST, ['property_id' => $propertyId]));
      cm_flash_set('success', 'Blocco manuale creato.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    if ($action === 'update_booking_status') {
      cm_update_booking_status((int)($_POST['booking_id'] ?? 0), (string)($_POST['status'] ?? ''));
      cm_flash_set('success', 'Stato prenotazione aggiornato.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
    }

    throw new RuntimeException('Azione non valida.');
  } catch (Throwable $e) {
    cm_flash_set('error', $e->getMessage());
    cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId);
  }
}

$property = cm_property($propertyId);
$connections = cm_property_connections($propertyId);
$bookings = cm_recent_bookings(100, $propertyId);
$occupancy = cm_property_occupancy($propertyId, 60);
$flash = cm_flash_get();
$statusOptions = cm_booking_statuses();
$channelOptions = cm_channels();
$clients = cm_clients();
$directUrl = cm_direct_booking_url($property);
$icalUrl = cm_ical_export_url($property);
$galleryImages = cm_lines($property['gallery_images'] ?? null);
$heroPreview = cm_primary_image($property);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= cm_h($property['name']) ?> | HostUp Channel</title>
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/crm.css">
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/channel.css">
</head>
<body class="crm-bg cm-admin">
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div class="bn">HostUp <span>Channel</span></div>
      </div>
      <div class="right">
        <a class="btn" href="<?= cm_h(cm_base_url('index.php')) ?>">Dashboard</a>
        <a class="btn" href="<?= cm_h(CRM_BASE_URL) ?>/index.php">CRM</a>
        <a class="btn" href="<?= cm_h(CRM_BASE_URL) ?>/logout.php">Esci</a>
      </div>
    </div>
  </header>

  <main class="wrap cm-shell">
    <section class="cm-page-header cm-page-header--property">
      <div class="cm-page-copy">
        <div class="cm-eyebrow">Workspace immobile</div>
        <h1><?= cm_h($property['name']) ?></h1>
        <p><?= cm_h($property['client_name']) ?><?php if ($property['city']): ?> - <?= cm_h($property['city']) ?><?php endif; ?></p>
        <div class="cm-chip-row">
          <span class="cm-chip"><?= (int)$property['max_guests'] ?> ospiti</span>
          <span class="cm-chip"><?= (int)$property['beds'] ?> letti</span>
          <span class="cm-chip"><?= (int)$property['published'] ? 'Pubblicato' : 'Bozza' ?></span>
        </div>
      </div>
      <div class="cm-page-actions">
        <a class="btn-primary" target="_blank" rel="noopener" href="<?= cm_h($directUrl) ?>">Apri pagina diretta</a>
        <a class="btn" target="_blank" rel="noopener" href="<?= cm_h($icalUrl) ?>">Apri feed iCal</a>
      </div>
    </section>

    <?php if ($heroPreview): ?>
      <section class="cm-property-banner box cm-panel">
        <img src="<?= cm_h($heroPreview) ?>" alt="<?= cm_h($property['name']) ?>">
        <div class="cm-property-banner-copy">
          <div class="cm-eyebrow">Anteprima pubblica</div>
          <h2><?= cm_h($property['name']) ?></h2>
          <p>Controlla dati operativi, contenuti media, calendario unico, canali collegati e stato delle prenotazioni da un'unica pagina.</p>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($flash): ?>
      <div class="cm-alert <?= cm_h($flash['type']) ?>"><?= cm_h($flash['message']) ?></div>
    <?php endif; ?>

    <nav class="cm-subnav">
      <a href="#scheda">Scheda</a>
      <a href="#calendario">Calendario</a>
      <a href="#canali">Canali</a>
      <a href="#prenotazioni">Prenotazioni</a>
    </nav>

    <section id="scheda" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Scheda</div>
          <h2>Dati operativi e contenuti pubblici</h2>
          <p>Qui configuri l'immobile per il backoffice e per la pagina di prenotazione diretta.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Modifica immobile</div>
          <div class="cm-form-note">Le sezioni media e contenuti aggiornano direttamente la scheda pubblica.</div>
          <form method="post" class="cm-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_property">
            <input type="hidden" name="id" value="<?= (int)$property['id'] ?>">

            <label>Cliente</label>
            <select name="client_id" class="select" required>
              <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client['id'] ?>"<?= (int)$client['id'] === (int)$property['client_id'] ? ' selected' : '' ?>><?= cm_h($client['name']) ?></option>
              <?php endforeach; ?>
            </select>

            <div class="cm-form-split">
              <div>
                <label>Nome immobile</label>
                <input name="name" required value="<?= cm_h($property['name']) ?>" />
              </div>
              <div>
                <label>Slug URL</label>
                <input name="slug" value="<?= cm_h($property['slug']) ?>" />
              </div>
            </div>

            <label>Descrizione</label>
            <textarea name="description" class="textarea"><?= cm_h($property['description'] ?? '') ?></textarea>

            <div class="cm-section-label">Media e pagina pubblica</div>

            <label>Immagine principale</label>
            <input name="hero_image_url" value="<?= cm_h($property['hero_image_url'] ?? '') ?>" placeholder="https://..." />
            <label>Carica immagine principale</label>
            <input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp,image/gif" />
            <?php if (!empty($property['hero_image_url'])): ?>
              <div class="cm-current-media">
                <img src="<?= cm_h((string)$property['hero_image_url']) ?>" alt="<?= cm_h($property['name']) ?>">
                <label class="cm-check"><input type="checkbox" name="remove_hero_image" value="1"> Rimuovi immagine principale attuale</label>
              </div>
            <?php endif; ?>

            <label>Galleria immagini</label>
            <textarea name="gallery_images" class="textarea" placeholder="Una URL per riga"><?= cm_h($property['gallery_images'] ?? '') ?></textarea>
            <label>Carica immagini galleria</label>
            <input type="file" name="gallery_image_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
            <?php if ($galleryImages): ?>
              <div class="cm-existing-gallery">
                <?php foreach ($galleryImages as $image): ?>
                  <label class="cm-media-thumb">
                    <img src="<?= cm_h($image) ?>" alt="<?= cm_h($property['name']) ?>">
                    <span class="cm-muted">Rimuovi</span>
                    <input type="checkbox" name="remove_gallery_images[]" value="<?= cm_h($image) ?>">
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="cm-form-split">
              <div>
                <label>Highlights pubblici</label>
                <textarea name="public_highlights" class="textarea" placeholder="Una riga per punto forte"><?= cm_h($property['public_highlights'] ?? '') ?></textarea>
              </div>
              <div>
                <label>Servizi / amenities</label>
                <textarea name="amenities" class="textarea" placeholder="Una riga per servizio"><?= cm_h($property['amenities'] ?? '') ?></textarea>
              </div>
            </div>

            <label>Indicazioni arrivo / accesso</label>
            <textarea name="arrival_instructions" class="textarea" placeholder="Parcheggio, punto d'incontro, citofono, self check-in..."><?= cm_h($property['arrival_instructions'] ?? '') ?></textarea>

            <div class="cm-form-split">
              <div>
                <label>Istruzioni check-in</label>
                <textarea name="checkin_instructions" class="textarea" placeholder="Documenti richiesti, fascia oraria, contatto di riferimento..."><?= cm_h($property['checkin_instructions'] ?? '') ?></textarea>
              </div>
              <div>
                <label>Istruzioni check-out</label>
                <textarea name="checkout_instructions" class="textarea" placeholder="Orario, chiavi, raccolta differenziata, ultime verifiche..."><?= cm_h($property['checkout_instructions'] ?? '') ?></textarea>
              </div>
            </div>

            <label>Regole della casa</label>
            <textarea name="house_rules" class="textarea" placeholder="Silenzio, animali, feste, fumo, ospiti extra..."><?= cm_h($property['house_rules'] ?? '') ?></textarea>

            <div class="cm-form-split">
              <div>
                <label>Contatto soggiorno</label>
                <input name="contact_name" value="<?= cm_h($property['contact_name'] ?? '') ?>" placeholder="Nome host / concierge" />
              </div>
              <div>
                <label>Telefono soggiorno</label>
                <input name="contact_phone" value="<?= cm_h($property['contact_phone'] ?? '') ?>" placeholder="+39..." />
              </div>
            </div>

            <div class="cm-section-label">Dati struttura</div>

            <div class="cm-form-split">
              <div>
                <label>Indirizzo</label>
                <input name="address_line1" value="<?= cm_h($property['address_line1'] ?? '') ?>" />
              </div>
              <div>
                <label>Citta</label>
                <input name="city" value="<?= cm_h($property['city'] ?? '') ?>" />
              </div>
            </div>

            <div class="cm-form-third">
              <div>
                <label>Regione</label>
                <input name="region" value="<?= cm_h($property['region'] ?? '') ?>" />
              </div>
              <div>
                <label>Paese</label>
                <input name="country_code" maxlength="2" value="<?= cm_h($property['country_code']) ?>" />
              </div>
              <div>
                <label>Timezone</label>
                <input name="timezone_name" value="<?= cm_h($property['timezone_name']) ?>" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Camere</label>
                <input name="bedrooms" type="number" min="0" value="<?= cm_h($property['bedrooms']) ?>" />
              </div>
              <div>
                <label>Bagni</label>
                <input name="bathrooms" type="number" min="0.5" step="0.5" value="<?= cm_h($property['bathrooms']) ?>" />
              </div>
              <div>
                <label>Letti</label>
                <input name="beds" type="number" min="1" value="<?= cm_h($property['beds']) ?>" />
              </div>
              <div>
                <label>Ospiti max</label>
                <input name="max_guests" type="number" min="1" value="<?= cm_h($property['max_guests']) ?>" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Notti minime</label>
                <input name="min_nights" type="number" min="1" value="<?= cm_h($property['min_nights']) ?>" />
              </div>
              <div>
                <label>Check-in da</label>
                <input name="checkin_from" type="time" value="<?= cm_h(substr((string)$property['checkin_from'], 0, 5)) ?>" />
              </div>
              <div>
                <label>Check-in fino</label>
                <input name="checkin_until" type="time" value="<?= cm_h(substr((string)$property['checkin_until'], 0, 5)) ?>" />
              </div>
              <div>
                <label>Check-out entro</label>
                <input name="checkout_until" type="time" value="<?= cm_h(substr((string)$property['checkout_until'], 0, 5)) ?>" />
              </div>
            </div>

            <div class="cm-form-grid4">
              <div>
                <label>Prezzo base/notte</label>
                <input name="base_price" type="number" min="0" step="0.01" value="<?= cm_h($property['base_price']) ?>" />
              </div>
              <div>
                <label>Pulizie</label>
                <input name="cleaning_fee" type="number" min="0" step="0.01" value="<?= cm_h($property['cleaning_fee']) ?>" />
              </div>
              <div>
                <label>Valuta</label>
                <input name="currency" maxlength="3" value="<?= cm_h($property['currency']) ?>" />
              </div>
              <div>
                <label>Notice ore</label>
                <input name="booking_notice_hours" type="number" min="0" value="<?= cm_h($property['booking_notice_hours']) ?>" />
              </div>
            </div>

            <label class="cm-check"><input type="checkbox" name="direct_booking_enabled" value="1"<?= (int)$property['direct_booking_enabled'] ? ' checked' : '' ?>> Abilita prenotazione diretta</label>
            <label class="cm-check"><input type="checkbox" name="published" value="1"<?= (int)$property['published'] ? ' checked' : '' ?>> Pubblica nel booking engine</label>
            <button class="btn-primary" type="submit">Aggiorna immobile</button>
          </form>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Azioni rapide</div>
          <div class="cm-form-note">Le operazioni usate piu spesso durante la gestione quotidiana.</div>

          <div class="cm-url-card">
            <div class="cm-url-title">URL booking diretto</div>
            <code><?= cm_h($directUrl) ?></code>
          </div>

          <div class="cm-url-card">
            <div class="cm-url-title">URL export iCal</div>
            <code><?= cm_h($icalUrl) ?></code>
          </div>

          <div class="cm-section-label">Blocca date manualmente</div>
          <div class="cm-form-note">Usa i blocchi per manutenzioni, soggiorni proprietario o date non vendibili.</div>
          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="create_block">
            <label>Motivo / etichetta</label>
            <input name="summary" placeholder="Manutenzione, owner stay, blackout..." />
            <div class="cm-form-split">
              <div>
                <label>Check-in</label>
                <input name="checkin_date" type="date" required />
              </div>
              <div>
                <label>Check-out</label>
                <input name="checkout_date" type="date" required />
              </div>
            </div>
            <button class="btn-primary" type="submit">Crea blocco</button>
          </form>
        </article>
      </section>
    </section>

    <section id="calendario" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Calendario</div>
          <h2>Occupazione prossimi 60 giorni</h2>
          <p>Vista rapida del calendario unico con disponibilita, blocchi manuali e prenotazioni importate.</p>
        </div>
      </div>
      <div class="box cm-panel">
        <div class="cm-occupancy-grid">
          <?php foreach ($occupancy as $day): ?>
            <div class="cm-day <?= $day['busy'] ? 'busy' : 'free' ?>">
              <div class="cm-day-top">
                <strong><?= cm_h($day['label']) ?></strong>
                <span><?= cm_h($day['weekday']) ?></span>
              </div>
              <div class="cm-day-status"><?= $day['busy'] ? cm_h(cm_channel_label((string)$day['channel'])) : 'Libero' ?></div>
              <div class="cm-day-note"><?= cm_h($day['booking_label']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="canali" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Connettivita</div>
          <h2>Canali esterni e note operative</h2>
          <p>Configura i collegamenti e controlla lo stato delle sincronizzazioni senza perdere il contesto dell'immobile.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Connessioni canali</div>
          <p>Per il primo rilascio il sync automatico usa iCal import/export. Le API native si innestano sopra questa struttura.</p>
          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="save_connection">
            <div class="cm-form-third">
              <div>
                <label>Canale</label>
                <select name="channel_code" class="select" required>
                  <option value="">Seleziona canale</option>
                  <?php foreach ($channelOptions as $key => $label): ?>
                    <?php if ($key === 'direct' || $key === 'manual') continue; ?>
                    <option value="<?= cm_h($key) ?>"><?= cm_h($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Modalita</label>
                <select name="sync_mode" class="select">
                  <option value="ical">iCal</option>
                  <option value="api">API</option>
                </select>
              </div>
              <div>
                <label>ID listing esterno</label>
                <input name="external_listing_id" placeholder="Listing ID del portale" />
              </div>
            </div>
            <label>Import URL iCal</label>
            <input name="import_url" placeholder="https://..." />
            <label class="cm-check"><input type="checkbox" name="enabled" value="1" checked> Connessione attiva</label>
            <button class="btn-primary" type="submit">Salva connessione</button>
          </form>

          <div class="cm-connection-list">
            <?php foreach ($connections as $connection): ?>
              <div class="cm-connection-card">
                <div>
                  <strong><?= cm_h(cm_channel_label((string)$connection['channel_code'])) ?></strong>
                  <div class="cm-muted"><?= cm_h(strtoupper((string)$connection['sync_mode'])) ?><?php if ($connection['external_listing_id']): ?> - ID <?= cm_h($connection['external_listing_id']) ?><?php endif; ?></div>
                  <div class="cm-muted">Ultimo sync: <?= $connection['last_sync_at'] ? cm_h(cm_fmt_datetime($connection['last_sync_at'])) : 'mai' ?></div>
                  <?php if ($connection['last_sync_message']): ?>
                    <div class="cm-muted"><?= cm_h($connection['last_sync_message']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="cm-actions-cell">
                  <form method="post">
                    <input type="hidden" name="action" value="sync_connection">
                    <input type="hidden" name="connection_id" value="<?= (int)$connection['id'] ?>">
                    <button class="btn" type="submit">Sync ora</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (!$connections): ?>
              <p>Nessun canale esterno collegato.</p>
            <?php endif; ?>
          </div>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Note operative</div>
          <div class="cm-kv"><strong>Booking diretto:</strong> la pagina pubblica crea prenotazioni `pending` nel calendario unico.</div>
          <div class="cm-kv"><strong>Sync iCal:</strong> utile per MVP o fallback. Booking e Airbnb API native richiedono onboarding dedicato.</div>
          <div class="cm-kv"><strong>Cron suggerito:</strong> `php crm/channel/cron_sync.php` ogni 5-10 minuti.</div>
          <div class="cm-kv"><strong>Feed da condividere ai portali:</strong> usa l'URL iCal di export di questo immobile.</div>
        </article>
      </section>
    </section>

    <section id="prenotazioni" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Movimenti</div>
          <h2>Prenotazioni e blocchi</h2>
          <p>Da qui aggiorni lo stato di richieste dirette, import da portali e blocchi manuali.</p>
        </div>
      </div>
      <div class="box cm-panel">
        <div class="tableWrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Canale</th>
                <th>Riferimento</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Stato</th>
                <th>Totale</th>
                <th>Azione</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td><?= cm_h(cm_channel_label((string)$booking['source_channel'])) ?></td>
                  <td>
                    <?= cm_h($booking['guest_name'] ?: ($booking['summary'] ?: '-')) ?>
                    <?php if ($booking['external_id']): ?>
                      <div class="cm-muted">Ref: <?= cm_h($booking['external_id']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= cm_h(cm_fmt_date($booking['checkin_date'])) ?></td>
                  <td><?= cm_h(cm_fmt_date($booking['checkout_date'])) ?></td>
                  <td><span class="cm-status-badge <?= $booking['status'] === 'confirmed' ? 'is-live' : ($booking['status'] === 'pending' ? 'is-warning' : 'is-draft') ?>"><?= cm_h(cm_booking_status_label((string)$booking['status'])) ?></span></td>
                  <td><?= cm_h(cm_fmt_money((float)$booking['total_amount'], (string)$booking['currency'])) ?></td>
                  <td>
                    <form method="post" class="cm-inline-form">
                      <input type="hidden" name="action" value="update_booking_status">
                      <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                      <select class="select" name="status">
                        <?php foreach ($statusOptions as $statusKey => $statusLabel): ?>
                          <option value="<?= cm_h($statusKey) ?>"<?= $statusKey === $booking['status'] ? ' selected' : '' ?>><?= cm_h($statusLabel) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn" type="submit">Aggiorna</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$bookings): ?>
                <tr><td colspan="7">Nessuna prenotazione presente.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
