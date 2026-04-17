<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function cm_property_task_booking_option_label(array $booking): string {
  $label = trim((string)($booking['guest_name'] ?: ($booking['summary'] ?: 'Prenotazione #' . (int)$booking['id'])));
  $dates = trim(cm_fmt_date((string)$booking['checkin_date']) . ' - ' . cm_fmt_date((string)$booking['checkout_date']));
  return $label . ' (' . $dates . ')';
}

function cm_property_update_context_label(array $update): string {
  $parts = [];
  $bookingLabel = trim((string)($update['booking_guest_name'] ?: ($update['booking_summary'] ?: '')));
  $taskLabel = trim((string)($update['task_title'] ?? ''));

  if ($bookingLabel !== '') {
    $parts[] = 'Prenotazione: ' . $bookingLabel;
  }
  if ($taskLabel !== '') {
    $parts[] = 'Task: ' . $taskLabel;
  }

  return implode(' - ', $parts);
}

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

    if ($action === 'save_task') {
      cm_save_task(array_merge($_POST, ['property_id' => $propertyId]), (int)$u['id']);
      cm_flash_set('success', 'Task operativa creata.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId . '#operativita');
    }

    if ($action === 'update_task_status') {
      cm_update_task_status((int)($_POST['task_id'] ?? 0), (string)($_POST['status'] ?? ''));
      cm_flash_set('success', 'Stato task aggiornato.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId . '#operativita');
    }

    if ($action === 'save_operational_update') {
      cm_save_operational_update(array_merge($_POST, ['property_id' => $propertyId]), (int)$u['id']);
      cm_flash_set('success', 'Aggiornamento operativo salvato.');
      cm_redirect(cm_base_url('property.php') . '?id=' . $propertyId . '#aggiornamenti');
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
$tasks = cm_property_tasks($propertyId, 30);
$taskSummary = cm_property_task_summary($propertyId);
$operationalUpdates = cm_property_operational_updates($propertyId, 20, false);
$flash = cm_flash_get();
$statusOptions = cm_booking_statuses();
$taskStatusOptions = cm_task_statuses();
$taskTypeOptions = cm_task_types();
$taskPriorityOptions = cm_task_priorities();
$operationalUpdateTypeOptions = cm_operational_update_types();
$channelOptions = cm_channels();
$clients = cm_clients();
$directUrl = cm_direct_booking_url($property);
$icalUrl = cm_ical_export_url($property);
$logoPreview = cm_property_logo($property);
$galleryImages = cm_lines($property['gallery_images'] ?? null);
$videoUrls = cm_property_videos($property);
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
        <a class="btn" href="<?= cm_h(cm_base_url('owner.php') . '?client_id=' . (int)$property['client_id']) ?>">Owner</a>
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
        <a class="btn" href="<?= cm_h(cm_base_url('owner.php') . '?client_id=' . (int)$property['client_id']) ?>">Vista proprietario</a>
        <a class="btn" target="_blank" rel="noopener" href="<?= cm_h($icalUrl) ?>">Apri feed iCal</a>
      </div>
    </section>

    <?php if ($heroPreview || $logoPreview): ?>
      <section class="cm-property-banner box cm-panel">
        <?php if ($heroPreview): ?>
          <img src="<?= cm_h($heroPreview) ?>" alt="<?= cm_h($property['name']) ?>">
        <?php else: ?>
          <div class="cm-property-banner-fallback">
            <span><?= cm_h(substr(cm_slugify((string)$property['name']), 0, 2)) ?></span>
          </div>
        <?php endif; ?>
        <div class="cm-property-banner-copy">
          <?php if ($logoPreview): ?>
            <img class="cm-property-brand-mark" src="<?= cm_h($logoPreview) ?>" alt="<?= cm_h($property['name']) ?>">
          <?php endif; ?>
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
      <a href="#operativita">Operativita</a>
      <a href="#aggiornamenti">Aggiornamenti</a>
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

            <label>Logo header</label>
            <input name="logo_image_url" value="<?= cm_h($property['logo_image_url'] ?? '') ?>" placeholder="https://..." />
            <label>Carica logo header</label>
            <input type="file" name="logo_image_file" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" />
            <?php if ($logoPreview): ?>
              <div class="cm-current-media cm-current-media--logo">
                <img src="<?= cm_h($logoPreview) ?>" alt="<?= cm_h($property['name']) ?>">
                <label class="cm-check"><input type="checkbox" name="remove_logo_image" value="1"> Rimuovi logo attuale</label>
              </div>
            <?php endif; ?>

            <label>Video pagina</label>
            <textarea name="video_urls" class="textarea" placeholder="Una URL diretta a file video per riga (.mp4, .webm, .mov)"><?= cm_h($property['video_urls'] ?? '') ?></textarea>
            <label>Carica video</label>
            <input type="file" name="video_files[]" accept="video/mp4,video/webm,video/ogg,video/quicktime" multiple />
            <?php if ($videoUrls): ?>
              <div class="cm-existing-gallery cm-existing-gallery--videos">
                <?php foreach ($videoUrls as $videoUrl): ?>
                  <label class="cm-media-thumb cm-media-thumb--video">
                    <video src="<?= cm_h($videoUrl) ?>" controls muted playsinline preload="metadata"></video>
                    <span class="cm-muted">Rimuovi</span>
                    <input type="checkbox" name="remove_video_urls[]" value="<?= cm_h($videoUrl) ?>">
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

    <section id="operativita" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Operativita</div>
          <h2>Task operative</h2>
          <p>Pulizie, manutenzioni, check-in/check-out e richieste ospite vengono tracciati qui, per immobile e opzionalmente per prenotazione.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Nuova task</div>
          <div class="cm-form-note">Crea un attivita manuale e collega la prenotazione solo se serve contesto operativo.</div>
          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="save_task">

            <label>Titolo task</label>
            <input name="title" required placeholder="Pulizia pre-arrivo, check-in ospite, manutenzione..." />

            <div class="cm-form-third">
              <div>
                <label>Tipo</label>
                <select name="task_type" class="select">
                  <?php foreach ($taskTypeOptions as $taskTypeKey => $taskTypeLabel): ?>
                    <option value="<?= cm_h($taskTypeKey) ?>"><?= cm_h($taskTypeLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Priorita</label>
                <select name="priority" class="select">
                  <?php foreach ($taskPriorityOptions as $priorityKey => $priorityLabel): ?>
                    <option value="<?= cm_h($priorityKey) ?>"<?= $priorityKey === 'normal' ? ' selected' : '' ?>><?= cm_h($priorityLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Scadenza</label>
                <input name="due_at" type="datetime-local" />
              </div>
            </div>

            <div class="cm-form-split">
              <div>
                <label>Assegnata a</label>
                <input name="assignee_name" placeholder="Nome collaboratore o fornitore" />
              </div>
              <div>
                <label>Prenotazione collegata</label>
                <select name="booking_id" class="select">
                  <option value="">Nessuna prenotazione</option>
                  <?php foreach ($bookings as $booking): ?>
                    <option value="<?= (int)$booking['id'] ?>"><?= cm_h(cm_property_task_booking_option_label($booking)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <label>Dettagli operativi</label>
            <textarea name="details" class="textarea" placeholder="Checklist, note di accesso, materiale necessario, criticita..."></textarea>

            <button class="btn-primary" type="submit">Crea task</button>
          </form>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Stato operativita</div>
          <div class="cm-task-summary-grid">
            <div class="cm-task-summary-card">
              <span>Aperte</span>
              <strong><?= (int)$taskSummary['open_tasks'] ?></strong>
            </div>
            <div class="cm-task-summary-card">
              <span>In corso</span>
              <strong><?= (int)$taskSummary['in_progress_tasks'] ?></strong>
            </div>
            <div class="cm-task-summary-card">
              <span>Chiuse</span>
              <strong><?= (int)$taskSummary['done_tasks'] ?></strong>
            </div>
            <div class="cm-task-summary-card<?= (int)$taskSummary['overdue_tasks'] > 0 ? ' is-overdue' : '' ?>">
              <span>In ritardo</span>
              <strong><?= (int)$taskSummary['overdue_tasks'] ?></strong>
            </div>
          </div>

          <div class="cm-section-label">Coda operativa</div>
          <?php if ($tasks): ?>
            <div class="cm-task-list">
              <?php foreach ($tasks as $task): ?>
                <?php
                $isTaskOverdue = (string)($task['due_at'] ?? '') !== ''
                  && in_array((string)$task['status'], cm_active_task_statuses(), true)
                  && strtotime((string)$task['due_at']) < time();
                $linkedBookingLabel = trim((string)($task['booking_guest_name'] ?: ($task['booking_summary'] ?: '')));
                ?>
                <article class="cm-task-card<?= $isTaskOverdue ? ' is-overdue' : '' ?>">
                  <div class="cm-task-card-top">
                    <div>
                      <strong><?= cm_h($task['title']) ?></strong>
                      <div class="cm-muted">
                        <?= cm_h(cm_task_type_label((string)$task['task_type'])) ?><?php if ($task['created_by_name']): ?> - creata da <?= cm_h($task['created_by_name']) ?><?php endif; ?>
                      </div>
                    </div>
                    <div class="cm-task-card-badges">
                      <span class="cm-status-badge <?= cm_h(cm_task_status_badge_class((string)$task['status'])) ?>"><?= cm_h(cm_task_status_label((string)$task['status'])) ?></span>
                      <span class="cm-counter-pill"><?= cm_h(cm_task_priority_label((string)$task['priority'])) ?></span>
                    </div>
                  </div>

                  <div class="cm-task-meta">
                    <span>Assegnata a: <?= cm_h($task['assignee_name'] ?: 'da assegnare') ?></span>
                    <span>Scadenza: <?= cm_h($task['due_at'] ? cm_fmt_datetime((string)$task['due_at']) : 'non impostata') ?></span>
                    <?php if ($linkedBookingLabel !== ''): ?>
                      <span>Prenotazione: <?= cm_h($linkedBookingLabel) ?></span>
                    <?php endif; ?>
                  </div>

                  <?php if ($task['details']): ?>
                    <div class="cm-richtext"><?= nl2br(cm_h((string)$task['details'])) ?></div>
                  <?php endif; ?>

                  <form method="post" class="cm-inline-form">
                    <input type="hidden" name="action" value="update_task_status">
                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                    <select class="select" name="status">
                      <?php foreach ($taskStatusOptions as $taskStatusKey => $taskStatusLabel): ?>
                        <option value="<?= cm_h($taskStatusKey) ?>"<?= $taskStatusKey === $task['status'] ? ' selected' : '' ?>><?= cm_h($taskStatusLabel) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">Aggiorna stato</button>
                    <?php if ($task['booking_id']): ?>
                      <a class="btn" href="#prenotazioni">Apri prenotazioni</a>
                    <?php endif; ?>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessuna task operativa presente per questo immobile.</div>
          <?php endif; ?>
        </article>
      </section>
    </section>

    <section id="aggiornamenti" class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Owner feed</div>
          <h2>Aggiornamenti operativi</h2>
          <p>Usa questa sezione per lasciare note leggibili dal proprietario: avanzamento pulizie, check-in gestito, criticita, aggiornamenti sul soggiorno.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Nuovo aggiornamento</div>
          <div class="cm-form-note">Gli aggiornamenti marcati come visibili al proprietario entrano nell owner dashboard e nella timeline condivisa.</div>
          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="save_operational_update">

            <label>Titolo</label>
            <input name="title" required placeholder="Pulizia completata, check-in eseguito, piccola manutenzione..." />

            <div class="cm-form-third">
              <div>
                <label>Tipo aggiornamento</label>
                <select name="update_type" class="select">
                  <?php foreach ($operationalUpdateTypeOptions as $updateTypeKey => $updateTypeLabel): ?>
                    <option value="<?= cm_h($updateTypeKey) ?>"><?= cm_h($updateTypeLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Quando e successo</label>
                <input name="happened_at" type="datetime-local" />
              </div>
              <div>
                <label>Task collegata</label>
                <select name="task_id" class="select">
                  <option value="">Nessuna task</option>
                  <?php foreach ($tasks as $task): ?>
                    <option value="<?= (int)$task['id'] ?>"><?= cm_h($task['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <label>Prenotazione collegata</label>
            <select name="booking_id" class="select">
              <option value="">Nessuna prenotazione</option>
              <?php foreach ($bookings as $booking): ?>
                <option value="<?= (int)$booking['id'] ?>"><?= cm_h(cm_property_task_booking_option_label($booking)) ?></option>
              <?php endforeach; ?>
            </select>

            <label>Dettagli</label>
            <textarea name="body" class="textarea" placeholder="Spiega cosa e stato fatto, cosa resta da chiudere e se il proprietario deve sapere altro."></textarea>

            <label class="cm-check"><input type="checkbox" name="owner_visible" value="1" checked> Visibile al proprietario</label>
            <button class="btn-primary" type="submit">Salva aggiornamento</button>
          </form>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Storico aggiornamenti</div>
          <?php if ($operationalUpdates): ?>
            <div class="cm-update-list">
              <?php foreach ($operationalUpdates as $update): ?>
                <?php $contextLabel = cm_property_update_context_label($update); ?>
                <article class="cm-update-card<?= (string)$update['update_type'] === 'issue' ? ' is-issue' : '' ?>">
                  <div class="cm-update-card-top">
                    <div>
                      <strong><?= cm_h($update['title']) ?></strong>
                      <div class="cm-muted">
                        <?= cm_h(cm_operational_update_type_label((string)$update['update_type'])) ?>
                        <?php if ($update['created_by_name']): ?> - <?= cm_h($update['created_by_name']) ?><?php endif; ?>
                      </div>
                    </div>
                    <div class="cm-task-card-badges">
                      <span class="cm-status-badge <?= (int)$update['owner_visible'] ? 'is-live' : 'is-draft' ?>"><?= (int)$update['owner_visible'] ? 'Owner visibile' : 'Interno' ?></span>
                    </div>
                  </div>

                  <div class="cm-task-meta">
                    <span>Data evento: <?= cm_h($update['happened_at'] ? cm_fmt_datetime((string)$update['happened_at']) : cm_fmt_datetime((string)$update['created_at'])) ?></span>
                    <?php if ($contextLabel !== ''): ?>
                      <span><?= cm_h($contextLabel) ?></span>
                    <?php endif; ?>
                  </div>

                  <?php if ($update['body']): ?>
                    <div class="cm-richtext"><?= nl2br(cm_h((string)$update['body'])) ?></div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessun aggiornamento operativo registrato per questo immobile.</div>
          <?php endif; ?>
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
