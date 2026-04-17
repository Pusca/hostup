<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function cm_owner_location_label(array $property): string {
  $parts = [];
  $city = trim((string)($property['city'] ?? ''));
  $region = trim((string)($property['region'] ?? ''));

  if ($city !== '') {
    $parts[] = $city;
  }
  if ($region !== '') {
    $parts[] = '(' . $region . ')';
  }

  return trim(implode(' ', $parts));
}

function cm_owner_guest_label(array $booking): string {
  $guest = trim((string)($booking['guest_name'] ?? ''));
  if ($guest !== '') {
    return $guest;
  }

  $summary = trim((string)($booking['summary'] ?? ''));
  return $summary !== '' ? $summary : 'Prenotazione';
}

$selectedClientId = max(0, (int)($_GET['client_id'] ?? 0));
$selectedClient = $selectedClientId > 0 ? cm_client($selectedClientId) : null;
$inlineAlert = null;

if ($selectedClientId > 0 && !$selectedClient) {
  $selectedClientId = 0;
  $inlineAlert = [
    'type' => 'error',
    'message' => 'Cliente non trovato. Vista ripristinata su tutto il portafoglio.',
  ];
}

$clients = cm_clients();
$summary = cm_owner_dashboard_summary($selectedClientId);
$channelMix = cm_owner_dashboard_channel_mix($selectedClientId);
$operationalSummary = cm_owner_dashboard_operational_summary($selectedClientId, 7);
$operationalUpdates = cm_owner_dashboard_operational_updates($selectedClientId, 8);
$portfolio = cm_owner_dashboard_properties($selectedClientId);
$pendingRequests = cm_owner_dashboard_pending_requests($selectedClientId, 8);
$taskSummary = cm_owner_dashboard_task_summary($selectedClientId, 7);
$taskFeed = cm_owner_dashboard_tasks($selectedClientId, 10);
$timeline = cm_owner_dashboard_timeline($selectedClientId, 18, 10);

$scopeTitle = $selectedClient ? $selectedClient['name'] : 'Tutti i clienti';
$scopeMeta = $selectedClient
  ? 'Vista filtrata sul singolo proprietario, pronta per essere trasformata in area dedicata.'
  : 'Vista aggregata del portafoglio attuale. Usa il filtro per simulare l esperienza proprietario.';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Owner Dashboard | HostUp Channel</title>
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
    <section class="cm-page-header cm-owner-header">
      <div class="cm-page-copy">
        <div class="cm-eyebrow">Sprint 3 MVP</div>
        <h1>Owner Dashboard</h1>
        <p>Vista proprietario read-only con portafoglio, task, aggiornamenti manuali del team e timeline operativa unificata su soggiorni, richieste dirette e attivita in corso.</p>
        <div class="cm-chip-row">
          <span class="cm-chip"><?= cm_h($scopeTitle) ?></span>
          <span class="cm-chip">Lookahead <?= (int)$summary['lookahead_days'] ?> giorni</span>
          <span class="cm-chip">Ricavi solo su prenotazioni valorizzate</span>
          <span class="cm-chip"><?= (int)$operationalSummary['recent_updates'] ?> aggiornamenti ultimi <?= (int)$operationalSummary['recent_days'] ?> giorni</span>
        </div>
      </div>

      <div class="cm-page-actions">
        <form method="get" class="cm-form cm-owner-filter-form">
          <label>Cliente / proprietario</label>
          <select name="client_id" class="select">
            <option value="0">Tutto il portafoglio</option>
            <?php foreach ($clients as $client): ?>
              <option value="<?= (int)$client['id'] ?>"<?= (int)$client['id'] === $selectedClientId ? ' selected' : '' ?>><?= cm_h($client['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn-primary" type="submit">Aggiorna vista</button>
          <?php if ($selectedClientId > 0): ?>
            <a class="btn" href="<?= cm_h(cm_base_url('owner.php')) ?>">Reset filtro</a>
          <?php endif; ?>
        </form>

        <div class="cm-owner-header-note">
          <strong>Perimetro sprint</strong>
          <p><?= cm_h($scopeMeta) ?></p>
          <a class="btn" href="<?= cm_h(cm_base_url('index.php')) ?>">Torna al channel manager</a>
        </div>
      </div>
    </section>

    <?php if ($inlineAlert): ?>
      <div class="cm-alert <?= cm_h($inlineAlert['type']) ?>"><?= cm_h($inlineAlert['message']) ?></div>
    <?php endif; ?>

    <section class="cm-stats">
      <article class="cm-stat">
        <div class="cm-stat-label">Immobili</div>
        <div class="cm-stat-value"><?= (int)$summary['properties'] ?></div>
        <div class="cm-stat-meta"><?= (int)$summary['published_properties'] ?> pubblicati, <?= (int)$summary['direct_enabled_properties'] ?> con booking diretto attivo</div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Prenotazioni attive</div>
        <div class="cm-stat-value"><?= (int)$summary['active_bookings'] ?></div>
        <div class="cm-stat-meta"><?= (int)$summary['pending_direct_requests'] ?> richieste dirette ancora da lavorare</div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Check-in prossimi <?= (int)$summary['lookahead_days'] ?> giorni</div>
        <div class="cm-stat-value"><?= (int)$summary['upcoming_checkins'] ?></div>
        <div class="cm-stat-meta"><?= (int)$summary['upcoming_checkouts'] ?> check-out nello stesso orizzonte</div>
      </article>
      <article class="cm-stat">
        <div class="cm-stat-label">Ricavi tracciati mese corrente</div>
        <div class="cm-stat-value"><?= cm_h(cm_fmt_money((float)$summary['tracked_revenue_month'])) ?></div>
        <div class="cm-stat-meta">Pipeline futura tracciata: <?= cm_h(cm_fmt_money((float)$summary['tracked_revenue_future'])) ?></div>
      </article>
    </section>

    <section class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Overview</div>
          <h2>Snapshot operativo</h2>
          <p>In questa sezione il proprietario vede lo stato attuale del portafoglio senza entrare nei dettagli operativi del team.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Stato del portafoglio</div>
          <div class="cm-owner-overview-list">
            <div class="cm-owner-overview-row">
              <span>Immobili pubblicati</span>
              <strong><?= (int)$summary['published_properties'] ?> / <?= (int)$summary['properties'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Canale diretto attivo</span>
              <strong><?= (int)$summary['direct_enabled_properties'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Richieste dirette pending</span>
              <strong><?= (int)$summary['pending_direct_requests'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Partenze imminenti</span>
              <strong><?= (int)$summary['upcoming_checkouts'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Task aperte / in corso</span>
              <strong><?= (int)$taskSummary['open_tasks'] + (int)$taskSummary['in_progress_tasks'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Aggiornamenti recenti</span>
              <strong><?= (int)$operationalSummary['recent_updates'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Ricavo futuro tracciato</span>
              <strong><?= cm_h(cm_fmt_money((float)$summary['tracked_revenue_future'])) ?></strong>
            </div>
          </div>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Mix canali attivi</div>
          <?php if ($channelMix): ?>
            <div class="cm-owner-mix-list">
              <?php foreach ($channelMix as $row): ?>
                <div class="cm-owner-mix-row">
                  <div>
                    <strong><?= cm_h(cm_channel_label((string)$row['source_channel'])) ?></strong>
                    <div class="cm-muted"><?= (int)$row['booking_count'] ?> prenotazioni attive</div>
                  </div>
                  <div class="cm-owner-mix-metrics">
                    <span class="cm-counter-pill"><?= (int)$row['booking_count'] ?></span>
                    <span><?= cm_h(cm_fmt_money((float)$row['tracked_revenue'])) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">
              Nessun soggiorno attivo nel periodo corrente.
            </div>
          <?php endif; ?>
        </article>
      </section>
    </section>

    <section class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Aggiornamenti</div>
          <h2>Feed proprietario</h2>
          <p>Il team puo pubblicare aggiornamenti strutturati dall immobile. Qui il proprietario vede solo quelli marcati come condivisibili.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Sintesi aggiornamenti</div>
          <div class="cm-owner-overview-list">
            <div class="cm-owner-overview-row">
              <span>Ultimi <?= (int)$operationalSummary['recent_days'] ?> giorni</span>
              <strong><?= (int)$operationalSummary['recent_updates'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Criticita registrate</span>
              <strong><?= (int)$operationalSummary['issue_updates'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>Ultimo aggiornamento</span>
              <strong><?= cm_h($operationalSummary['last_update_at'] ? cm_fmt_datetime((string)$operationalSummary['last_update_at']) : '-') ?></strong>
            </div>
          </div>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Ultimi aggiornamenti condivisi</div>
          <?php if ($operationalUpdates): ?>
            <div class="cm-owner-event-list">
              <?php foreach ($operationalUpdates as $update): ?>
                <article class="cm-owner-event<?= (string)$update['update_type'] === 'issue' ? ' is-overdue' : '' ?>">
                  <div class="cm-owner-event-top">
                    <div>
                      <strong><?= cm_h($update['title']) ?></strong>
                      <div class="cm-muted">
                        <?= cm_h(cm_operational_update_type_label((string)$update['update_type'])) ?> - <?= cm_h($update['property_name']) ?>
                        <?php if ($selectedClientId === 0): ?> - <?= cm_h($update['client_name']) ?><?php endif; ?>
                      </div>
                    </div>
                    <span class="cm-status-badge <?= (string)$update['update_type'] === 'issue' ? 'is-warning' : 'is-draft' ?>"><?= cm_h(cm_operational_update_type_label((string)$update['update_type'])) ?></span>
                  </div>
                  <div class="cm-owner-event-meta">
                    <span><?= cm_h($update['happened_at'] ? cm_fmt_datetime((string)$update['happened_at']) : cm_fmt_datetime((string)$update['created_at'])) ?></span>
                    <?php if ($update['created_by_name']): ?>
                      <span><?= cm_h($update['created_by_name']) ?></span>
                    <?php endif; ?>
                    <?php if ($update['task_title']): ?>
                      <span>Task <?= cm_h((string)$update['task_title']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($update['body']): ?>
                    <div class="cm-richtext"><?= nl2br(cm_h((string)$update['body'])) ?></div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessun aggiornamento condiviso disponibile.</div>
          <?php endif; ?>
        </article>
      </section>
    </section>

    <section class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Operativita</div>
          <h2>Task monitorate</h2>
          <p>Le task restano gestite dal team nel backoffice immobile, ma qui il proprietario vede volume, urgenze e prossime scadenze.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Sintesi task</div>
          <div class="cm-owner-overview-list">
            <div class="cm-owner-overview-row">
              <span>Aperte</span>
              <strong><?= (int)$taskSummary['open_tasks'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>In corso</span>
              <strong><?= (int)$taskSummary['in_progress_tasks'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>In scadenza nei prossimi <?= (int)$taskSummary['lookahead_days'] ?> giorni</span>
              <strong><?= (int)$taskSummary['due_soon_tasks'] ?></strong>
            </div>
            <div class="cm-owner-overview-row">
              <span>In ritardo</span>
              <strong><?= (int)$taskSummary['overdue_tasks'] ?></strong>
            </div>
          </div>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Coda operativa</div>
          <?php if ($taskFeed): ?>
            <div class="cm-owner-event-list">
              <?php foreach ($taskFeed as $task): ?>
                <?php $taskIsOverdue = (string)($task['due_at'] ?? '') !== '' && in_array((string)$task['status'], cm_active_task_statuses(), true) && strtotime((string)$task['due_at']) < time(); ?>
                <article class="cm-owner-event<?= $taskIsOverdue ? ' is-overdue' : '' ?>">
                  <div class="cm-owner-event-top">
                    <div>
                      <strong><?= cm_h($task['title']) ?></strong>
                      <div class="cm-muted">
                        <?= cm_h(cm_task_type_label((string)$task['task_type'])) ?> - <?= cm_h($task['property_name']) ?>
                        <?php if ($selectedClientId === 0): ?> - <?= cm_h($task['client_name']) ?><?php endif; ?>
                      </div>
                    </div>
                    <span class="cm-status-badge <?= cm_h(cm_task_status_badge_class((string)$task['status'])) ?>"><?= cm_h(cm_task_status_label((string)$task['status'])) ?></span>
                  </div>
                  <div class="cm-owner-event-meta">
                    <span>Priorita <?= cm_h(cm_task_priority_label((string)$task['priority'])) ?></span>
                    <span>Scadenza <?= cm_h($task['due_at'] ? cm_fmt_datetime((string)$task['due_at']) : 'non impostata') ?></span>
                    <span>Assegnata a <?= cm_h($task['assignee_name'] ?: 'da assegnare') ?></span>
                  </div>
                  <div class="cm-actions-cell">
                    <a class="btn" href="<?= cm_h(cm_base_url('property.php') . '?id=' . (int)$task['property_id'] . '#operativita') ?>">Apri task</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessuna task attiva nel filtro selezionato.</div>
          <?php endif; ?>
        </article>
      </section>
    </section>

    <section class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Portfolio</div>
          <h2>Immobili monitorati</h2>
          <p>Ogni card riassume stato pubblicazione, attivita corrente e prossimi movimenti dell immobile.</p>
        </div>
      </div>

      <?php if ($portfolio): ?>
        <div class="cm-owner-property-grid">
          <?php foreach ($portfolio as $property): ?>
            <?php
            $image = cm_primary_image($property);
            $location = cm_owner_location_label($property);
            ?>
            <article class="cm-panel cm-owner-property-card">
              <?php if ($image): ?>
                <div class="cm-owner-property-media">
                  <img src="<?= cm_h($image) ?>" alt="<?= cm_h($property['name']) ?>">
                </div>
              <?php endif; ?>

              <div class="cm-owner-property-body">
                <div class="cm-owner-property-head">
                  <div>
                    <h3><?= cm_h($property['name']) ?></h3>
                    <p><?= cm_h($location !== '' ? $location : 'Localita non configurata') ?></p>
                  </div>
                  <div class="cm-owner-property-badges">
                    <span class="cm-status-badge <?= (int)$property['published'] ? 'is-live' : 'is-draft' ?>"><?= (int)$property['published'] ? 'Pubblicato' : 'Bozza' ?></span>
                    <span class="cm-status-badge <?= (int)$property['direct_booking_enabled'] ? 'is-live' : 'is-draft' ?>"><?= (int)$property['direct_booking_enabled'] ? 'Diretto on' : 'Diretto off' ?></span>
                  </div>
                </div>

                <?php if ($selectedClientId === 0): ?>
                  <div class="cm-muted cm-owner-property-client">Cliente: <?= cm_h($property['client_name']) ?></div>
                <?php endif; ?>

                <div class="cm-owner-property-metrics">
                  <div>
                    <span>Prenotazioni attive</span>
                    <strong><?= (int)$property['active_booking_count'] ?></strong>
                  </div>
                  <div>
                    <span>Prossimo check-in</span>
                    <strong><?= cm_h($property['next_checkin_date'] ? cm_fmt_date((string)$property['next_checkin_date']) : '-') ?></strong>
                  </div>
                  <div>
                    <span>Prossimo check-out</span>
                    <strong><?= cm_h($property['next_checkout_date'] ? cm_fmt_date((string)$property['next_checkout_date']) : '-') ?></strong>
                  </div>
                  <div>
                    <span>Ricavo tracciato mese</span>
                    <strong><?= cm_h(cm_fmt_money((float)$property['tracked_revenue_month'])) ?></strong>
                  </div>
                </div>

                <div class="cm-chip-row cm-owner-property-chips">
                  <span class="cm-chip"><?= (int)$property['max_guests'] ?> ospiti</span>
                  <span class="cm-chip"><?= (int)$property['beds'] ?> letti</span>
                  <span class="cm-chip"><?= (int)$property['pending_direct_count'] ?> richieste pending</span>
                  <span class="cm-chip"><?= (int)$property['active_task_count'] ?> task attive</span>
                  <?php if ((int)$property['overdue_task_count'] > 0): ?>
                    <span class="cm-chip"><?= (int)$property['overdue_task_count'] ?> task in ritardo</span>
                  <?php endif; ?>
                </div>

                <div class="cm-actions-cell">
                  <a class="btn" href="<?= cm_h(cm_base_url('property.php') . '?id=' . (int)$property['id']) ?>">Apri immobile</a>
                  <?php if ((int)$property['direct_booking_enabled']): ?>
                    <a class="btn" href="<?= cm_h(cm_direct_booking_url($property)) ?>" target="_blank" rel="noopener">Pagina diretta</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <article class="cm-panel cm-owner-empty-panel">
          Nessun immobile disponibile per il filtro selezionato.
        </article>
      <?php endif; ?>
    </section>

    <section class="cm-page-section">
      <div class="cm-section-head">
        <div>
          <div class="cm-eyebrow">Timeline</div>
          <h2>Timeline operativa</h2>
          <p>Feed unificato di aggiornamenti condivisi, check-in/check-out imminenti, task operative e richieste dirette.</p>
        </div>
      </div>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Timeline unificata</div>
          <?php if ($timeline): ?>
            <div class="cm-owner-event-list">
              <?php foreach ($timeline as $item): ?>
                <article class="cm-owner-event">
                  <div class="cm-owner-event-top">
                    <div>
                      <strong><?= cm_h((string)$item['title']) ?></strong>
                      <div class="cm-muted"><?= cm_h((string)$item['meta']) ?></div>
                    </div>
                    <span class="cm-status-badge <?= cm_h((string)$item['status_class']) ?>"><?= cm_h((string)$item['status_label']) ?></span>
                  </div>
                  <div class="cm-owner-event-meta">
                    <span><?= cm_h(cm_fmt_datetime((string)$item['sort_at'])) ?></span>
                    <?php if ((string)$item['detail'] !== ''): ?>
                      <span><?= cm_h((string)$item['detail']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="cm-actions-cell">
                    <a class="btn" href="<?= cm_h((string)$item['href']) ?>">Apri dettaglio</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessun evento disponibile nella timeline.</div>
          <?php endif; ?>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Richieste dirette da lavorare</div>
          <?php if ($pendingRequests): ?>
            <div class="cm-owner-event-list">
              <?php foreach ($pendingRequests as $booking): ?>
                <article class="cm-owner-event">
                  <div class="cm-owner-event-top">
                    <div>
                      <strong><?= cm_h(cm_owner_guest_label($booking)) ?></strong>
                      <div class="cm-muted"><?= cm_h($booking['property_name']) ?> - richiesta diretta</div>
                    </div>
                    <a class="btn" href="<?= cm_h(cm_base_url('property.php') . '?id=' . (int)$booking['property_id'] . '#prenotazioni') ?>">Apri pratica</a>
                  </div>
                  <div class="cm-owner-event-meta">
                    <span>Soggiorno <?= cm_h(cm_fmt_date((string)$booking['checkin_date'])) ?> - <?= cm_h(cm_fmt_date((string)$booking['checkout_date'])) ?></span>
                    <span><?= (int)$booking['adults'] ?> adulti<?php if ((int)$booking['children'] > 0): ?> + <?= (int)$booking['children'] ?> bambini<?php endif; ?></span>
                    <span><?= cm_h((string)($booking['guest_email'] ?: '-')) ?></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="cm-owner-empty">Nessuna richiesta diretta pending.</div>
          <?php endif; ?>
        </article>
      </section>
    </section>
  </main>
</body>
</html>
