<?php
declare(strict_types=1);

require_once __DIR__ . '/crm/channel/lib.php';

cm_install_schema();

function cm_booking_monogram(string $name): string {
  $parts = preg_split('/\s+/', trim($name)) ?: [];
  $letters = '';
  foreach ($parts as $part) {
    $part = trim($part);
    if ($part === '') {
      continue;
    }
    $letters .= strtoupper(substr($part, 0, 1));
    if (strlen($letters) >= 2) {
      break;
    }
  }
  return $letters !== '' ? $letters : 'ST';
}

function cm_render_public_booking_widget(array $property, ?array $quote, bool $isAvailable, string $checkin, string $checkout, int $guests): void {
  ?>
  <div class="cm-public-booking-widget">
    <div class="cm-public-booking-flow">
      <div class="cm-public-booking-step">
        <span>1</span>
        <div>
          <strong>Date</strong>
          <small>Controlla disponibilita e costo</small>
        </div>
      </div>
      <div class="cm-public-booking-step">
        <span>2</span>
        <div>
          <strong>Richiesta</strong>
          <small>Inserisci i tuoi dati e invia</small>
        </div>
      </div>
    </div>

    <form method="get" class="cm-form cm-public-check-form">
      <input type="hidden" name="property" value="<?= cm_h($property['slug']) ?>">
      <div class="cm-public-booking-grid cm-public-booking-grid--dates">
        <div>
          <label>Check-in</label>
          <input type="date" name="checkin" value="<?= cm_h($checkin) ?>" required />
        </div>
        <div>
          <label>Check-out</label>
          <input type="date" name="checkout" value="<?= cm_h($checkout) ?>" required />
        </div>
      </div>

      <div class="cm-public-booking-grid">
        <div>
          <label>Ospiti</label>
          <input type="number" name="guests" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= cm_h($guests) ?>" />
        </div>
      </div>

      <button class="btn-primary cm-public-submit" type="submit">Verifica disponibilita</button>
    </form>

    <?php if ($quote): ?>
      <div class="cm-quote">
        <div><strong><?= (int)$quote['nights'] ?></strong> notti</div>
        <div><?= cm_h(cm_fmt_money((float)$quote['nightly_rate'], (string)$quote['currency'])) ?> / notte</div>
        <div>Pulizie: <?= cm_h(cm_fmt_money((float)$quote['cleaning_fee'], (string)$quote['currency'])) ?></div>
        <div class="cm-quote-total">Totale stimato: <?= cm_h(cm_fmt_money((float)$quote['total'], (string)$quote['currency'])) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($checkin && $checkout): ?>
      <div class="cm-booking-state <?= $isAvailable ? 'free' : 'busy' ?>">
        <?= $isAvailable ? 'Disponibile per le date selezionate' : 'Non disponibile per le date selezionate' ?>
      </div>
    <?php else: ?>
      <div class="cm-booking-state free">Seleziona prima le date per attivare la richiesta.</div>
    <?php endif; ?>

    <form method="post" class="cm-form cm-public-request-form">
      <input type="hidden" name="action" value="create_booking">
      <input type="hidden" name="property_slug" value="<?= cm_h($property['slug']) ?>">
      <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
      <input type="hidden" name="checkin_date" value="<?= cm_h($checkin) ?>">
      <input type="hidden" name="checkout_date" value="<?= cm_h($checkout) ?>">

      <div class="cm-public-booking-grid cm-public-booking-grid--split">
        <div>
          <label>Nome e cognome</label>
          <input name="guest_name" required />
        </div>
        <div>
          <label>Email</label>
          <input name="guest_email" type="email" required />
        </div>
      </div>

      <div class="cm-public-booking-grid cm-public-booking-grid--three">
        <div>
          <label>Telefono</label>
          <input name="guest_phone" />
        </div>
        <div>
          <label>Adulti</label>
          <input name="adults" type="number" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= cm_h(max(1, $guests)) ?>" />
        </div>
        <div>
          <label>Bambini</label>
          <input name="children" type="number" min="0" value="0" />
        </div>
      </div>

      <div class="cm-public-booking-grid">
        <div>
          <label>Note</label>
          <textarea name="guest_notes" class="textarea" placeholder="Orario di arrivo, richieste particolari, note utili..."></textarea>
        </div>
      </div>

      <button class="btn-primary cm-public-submit" type="submit"<?= (!$checkin || !$checkout || !$isAvailable) ? ' disabled' : '' ?>>Invia richiesta</button>
    </form>
  </div>
  <?php
}

$propertySlug = trim((string)($_GET['property'] ?? $_POST['property_slug'] ?? ''));
$checkin = trim((string)($_GET['checkin'] ?? $_POST['checkin_date'] ?? ''));
$checkout = trim((string)($_GET['checkout'] ?? $_POST['checkout_date'] ?? ''));
$guests = max(1, cm_int_value($_GET['guests'] ?? $_POST['adults'] ?? 1, 1));

$property = $propertySlug !== '' ? cm_property_by_slug($propertySlug) : null;
if ($property && (!(int)$property['published'] || !(int)$property['direct_booking_enabled'])) {
  $property = null;
}

$success = '';
$error = '';
$quote = null;
$isAvailable = false;
$logoUrl = $property ? cm_property_logo($property) : null;
$heroImage = $property ? cm_primary_image($property) : null;
$galleryImages = $property ? cm_gallery_images($property) : [];
$videoUrls = $property ? cm_property_videos($property) : [];
$highlights = $property ? cm_lines($property['public_highlights'] ?? null) : [];
$amenities = $property ? cm_lines($property['amenities'] ?? null) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'create_booking') {
  try {
    if (!$property) {
      throw new InvalidArgumentException('Immobile non disponibile.');
    }
    $bookingId = cm_create_direct_booking(array_merge($_POST, ['property_id' => (int)$property['id']]));
    $quote = cm_quote($property, (string)$_POST['checkin_date'], (string)$_POST['checkout_date']);
    $success = 'Richiesta inviata correttamente. ID prenotazione: #' . $bookingId . '.';
    $checkin = (string)$_POST['checkin_date'];
    $checkout = (string)$_POST['checkout_date'];
    $guests = max(1, cm_int_value($_POST['adults'] ?? 1, 1) + cm_int_value($_POST['children'] ?? 0));
    $isAvailable = true;
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

if ($property && $checkin !== '' && $checkout !== '') {
  try {
    $isAvailable = cm_is_property_available((int)$property['id'], $checkin, $checkout);
    if ($isAvailable) {
      $quote = cm_quote($property, $checkin, $checkout);
    }
  } catch (Throwable $e) {
    if ($error === '') {
      $error = $e->getMessage();
    }
  }
}

$properties = $property ? [] : cm_public_properties([
  'checkin' => $checkin,
  'checkout' => $checkout,
  'guests' => $guests,
]);

$pageTitle = $property ? cm_h($property['name']) . ' | Prenota direttamente' : 'Prenotazione diretta';
$locationLabel = $property ? trim((string)($property['city'] ?? '') . ' ' . (($property['region'] ?? '') !== '' ? '(' . (string)$property['region'] . ')' : '')) : '';
$description = $property ? trim((string)($property['description'] ?? '')) : '';
$mobileBookingLabel = $property ? ($quote ? 'Completa richiesta' : 'Apri form') : '';
$mobileBookingMeta = $property
  ? ($checkin && $checkout ? ($isAvailable ? 'Disponibile per le date scelte' : 'Date non disponibili') : 'Verifica disponibilita e invia la richiesta')
  : '';
$mobileDrawerInitiallyOpen = $property && $error !== '';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/crm.css">
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/channel.css">
</head>
<body class="crm-bg cm-booking">
  <header class="topbar">
    <div class="wrap">
      <div class="cm-public-brand">
        <?php if ($logoUrl): ?>
          <img class="cm-public-brand-mark" src="<?= cm_h($logoUrl) ?>" alt="<?= cm_h($property ? $property['name'] : 'Prenotazione diretta') ?>">
        <?php else: ?>
          <div class="cm-public-wordmark"><?= cm_h(cm_booking_monogram($property['name'] ?? 'Stay')) ?></div>
        <?php endif; ?>
        <div class="cm-public-brand-copy">
          <strong><?= cm_h($property['name'] ?? 'Prenotazione diretta') ?></strong>
          <span><?= $property ? 'Sito diretto della struttura' : 'Collezione di soggiorni prenotabili direttamente' ?></span>
        </div>
      </div>

      <div class="right cm-public-nav">
        <?php if ($property): ?>
          <a class="btn" href="/booking.php">Altri soggiorni</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="wrap cm-public-shell">
    <?php if ($success): ?>
      <div class="cm-alert success"><?= cm_h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="cm-alert error"><?= cm_h($error) ?></div>
    <?php endif; ?>

    <?php if ($property): ?>
      <section class="cm-public-hero">
        <div class="cm-public-hero-media">
          <?php if ($heroImage): ?>
            <img src="<?= cm_h($heroImage) ?>" alt="<?= cm_h($property['name']) ?>">
          <?php else: ?>
            <div class="cm-public-media-fallback">
              <span><?= cm_h(cm_booking_monogram($property['name'])) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div class="cm-public-hero-copy">
          <div class="cm-public-overline">Prenotazione diretta</div>
          <h1><?= cm_h($property['name']) ?></h1>
          <p><?= cm_h($description !== '' ? cm_excerpt($description, 260) : 'Prenota direttamente con la struttura, con calendario sincronizzato e disponibilita aggiornata in tempo reale.') ?></p>

          <div class="cm-public-kpis">
            <?php if ($locationLabel !== ''): ?>
              <div class="cm-public-kpi">
                <span>Localita</span>
                <strong><?= cm_h($locationLabel) ?></strong>
              </div>
            <?php endif; ?>
            <div class="cm-public-kpi">
              <span>Ospiti</span>
              <strong><?= (int)$property['max_guests'] ?></strong>
            </div>
            <div class="cm-public-kpi">
              <span>Letti</span>
              <strong><?= (int)$property['beds'] ?></strong>
            </div>
            <div class="cm-public-kpi">
              <span>Da</span>
              <strong><?= cm_h(cm_fmt_money((float)$property['base_price'], (string)$property['currency'])) ?></strong>
            </div>
          </div>

          <?php if ($highlights): ?>
            <div class="cm-feature-pills">
              <?php foreach (array_slice($highlights, 0, 5) as $highlight): ?>
                <span class="cm-feature-pill"><?= cm_h($highlight) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="cm-public-layout">
        <div class="cm-public-main">
          <article class="cm-public-panel">
            <div class="cm-public-section-head">
              <div>
                <div class="cm-public-overline">Soggiorno</div>
                <h2>Dettagli e servizi</h2>
              </div>
            </div>

            <?php if ($description !== ''): ?>
              <div class="cm-public-copy"><?= nl2br(cm_h($description)) ?></div>
            <?php endif; ?>

            <div class="cm-public-stats-grid">
              <div class="cm-public-stat-box">
                <span>Capienza</span>
                <strong><?= (int)$property['max_guests'] ?> ospiti</strong>
              </div>
              <div class="cm-public-stat-box">
                <span>Check-in</span>
                <strong><?= cm_h(substr((string)$property['checkin_from'], 0, 5)) ?> - <?= cm_h(substr((string)$property['checkin_until'], 0, 5)) ?></strong>
              </div>
              <div class="cm-public-stat-box">
                <span>Check-out</span>
                <strong>Entro <?= cm_h(substr((string)$property['checkout_until'], 0, 5)) ?></strong>
              </div>
              <div class="cm-public-stat-box">
                <span>Soggiorno minimo</span>
                <strong><?= (int)$property['min_nights'] ?> notti</strong>
              </div>
            </div>

            <?php if ($amenities): ?>
              <div class="cm-public-overline cm-public-overline--section">Servizi inclusi</div>
              <div class="cm-feature-pills">
                <?php foreach ($amenities as $item): ?>
                  <span class="cm-feature-pill"><?= cm_h($item) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </article>

          <?php if (count($galleryImages) > 1): ?>
            <article class="cm-public-panel">
              <div class="cm-public-section-head">
                <div>
                  <div class="cm-public-overline">Media</div>
                  <h2>Galleria immagini</h2>
                </div>
              </div>
              <div class="cm-public-gallery">
                <?php foreach ($galleryImages as $image): ?>
                  <figure class="cm-public-gallery-item">
                    <img src="<?= cm_h($image) ?>" alt="<?= cm_h($property['name']) ?>">
                  </figure>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endif; ?>

          <?php if ($videoUrls): ?>
            <article class="cm-public-panel">
              <div class="cm-public-section-head">
                <div>
                  <div class="cm-public-overline">Media</div>
                  <h2>Video della struttura</h2>
                </div>
              </div>
              <div class="cm-public-videos">
                <?php foreach ($videoUrls as $videoUrl): ?>
                  <div class="cm-public-video">
                    <video src="<?= cm_h($videoUrl) ?>" controls playsinline preload="metadata"></video>
                  </div>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endif; ?>

          <?php if ($property['arrival_instructions'] || $property['checkin_instructions'] || $property['checkout_instructions'] || $property['house_rules'] || $property['contact_name'] || $property['contact_phone']): ?>
            <section class="cm-public-info-grid">
              <?php if ($property['arrival_instructions']): ?>
                <article class="cm-public-panel">
                  <div class="cm-public-section-head">
                    <div>
                      <div class="cm-public-overline">Arrivo</div>
                      <h2>Come arrivare</h2>
                    </div>
                  </div>
                  <div class="cm-public-copy"><?= nl2br(cm_h((string)$property['arrival_instructions'])) ?></div>
                </article>
              <?php endif; ?>

              <?php if ($property['checkin_instructions']): ?>
                <article class="cm-public-panel">
                  <div class="cm-public-section-head">
                    <div>
                      <div class="cm-public-overline">Accesso</div>
                      <h2>Istruzioni check-in</h2>
                    </div>
                  </div>
                  <div class="cm-public-copy"><?= nl2br(cm_h((string)$property['checkin_instructions'])) ?></div>
                </article>
              <?php endif; ?>

              <?php if ($property['checkout_instructions']): ?>
                <article class="cm-public-panel">
                  <div class="cm-public-section-head">
                    <div>
                      <div class="cm-public-overline">Partenza</div>
                      <h2>Istruzioni check-out</h2>
                    </div>
                  </div>
                  <div class="cm-public-copy"><?= nl2br(cm_h((string)$property['checkout_instructions'])) ?></div>
                </article>
              <?php endif; ?>

              <?php if ($property['house_rules']): ?>
                <article class="cm-public-panel">
                  <div class="cm-public-section-head">
                    <div>
                      <div class="cm-public-overline">Regole</div>
                      <h2>Regole della casa</h2>
                    </div>
                  </div>
                  <div class="cm-public-copy"><?= nl2br(cm_h((string)$property['house_rules'])) ?></div>
                </article>
              <?php endif; ?>

              <?php if ($property['contact_name'] || $property['contact_phone']): ?>
                <article class="cm-public-panel cm-public-contact">
                  <div class="cm-public-section-head">
                    <div>
                      <div class="cm-public-overline">Assistenza</div>
                      <h2>Contatto soggiorno</h2>
                    </div>
                  </div>
                  <?php if ($property['contact_name']): ?>
                    <div class="cm-public-contact-row">
                      <span>Referente</span>
                      <strong><?= cm_h($property['contact_name']) ?></strong>
                    </div>
                  <?php endif; ?>
                  <?php if ($property['contact_phone']): ?>
                    <div class="cm-public-contact-row">
                      <span>Telefono</span>
                      <strong><a href="tel:<?= cm_h($property['contact_phone']) ?>"><?= cm_h($property['contact_phone']) ?></a></strong>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endif; ?>
            </section>
          <?php endif; ?>
        </div>

        <aside class="cm-public-sidebar">
          <div class="cm-public-panel cm-public-panel--sticky" id="booking-panel">
            <div class="cm-public-section-head">
              <div>
                <div class="cm-public-overline">Disponibilita</div>
                <h2>Verifica e prenota</h2>
              </div>
            </div>

            <?php cm_render_public_booking_widget($property, $quote, $isAvailable, $checkin, $checkout, $guests); ?>
          </div>
        </aside>
      </section>

      <div class="cm-mobile-booking-bar">
        <div class="cm-mobile-booking-bar-copy">
          <strong><?= cm_h(cm_fmt_money((float)$property['base_price'], (string)$property['currency'])) ?></strong>
          <span><?= cm_h($mobileBookingMeta) ?></span>
        </div>
        <button class="btn-primary" type="button" data-booking-toggle aria-controls="mobile-booking-drawer" aria-expanded="<?= $mobileDrawerInitiallyOpen ? 'true' : 'false' ?>">
          <?= cm_h($mobileBookingLabel) ?>
        </button>
      </div>

      <div class="cm-mobile-booking-overlay<?= $mobileDrawerInitiallyOpen ? ' is-open' : '' ?>" data-booking-overlay></div>
      <section class="cm-mobile-booking-drawer<?= $mobileDrawerInitiallyOpen ? ' is-open' : '' ?>" id="mobile-booking-drawer" aria-hidden="<?= $mobileDrawerInitiallyOpen ? 'false' : 'true' ?>">
        <div class="cm-mobile-booking-drawer-handle"></div>
        <div class="cm-mobile-booking-drawer-head">
          <div>
            <div class="cm-public-overline">Richiesta diretta</div>
            <h2><?= cm_h($property['name']) ?></h2>
          </div>
          <button class="btn" type="button" data-booking-close>Chiudi</button>
        </div>

        <?php cm_render_public_booking_widget($property, $quote, $isAvailable, $checkin, $checkout, $guests); ?>
      </section>
    <?php else: ?>
      <section class="cm-public-listing-hero">
        <div class="cm-public-overline">Prenotazione diretta</div>
        <h1>Scegli il soggiorno giusto per te</h1>
        <p>Ogni struttura ha una propria identita visiva, contenuti dedicati e disponibilita sincronizzata con il calendario unico.</p>
      </section>

      <section class="cm-public-panel cm-public-filter-card">
        <div class="cm-public-section-head">
          <div>
            <div class="cm-public-overline">Ricerca</div>
            <h2>Filtra per date e ospiti</h2>
          </div>
        </div>
        <form method="get" class="cm-form">
          <div class="cm-form-grid4">
            <div>
              <label>Check-in</label>
              <input type="date" name="checkin" value="<?= cm_h($checkin) ?>" />
            </div>
            <div>
              <label>Check-out</label>
              <input type="date" name="checkout" value="<?= cm_h($checkout) ?>" />
            </div>
            <div>
              <label>Ospiti</label>
              <input type="number" name="guests" min="1" value="<?= cm_h($guests) ?>" />
            </div>
            <div class="cm-form-submit">
              <button class="btn-primary" type="submit">Cerca disponibilita</button>
            </div>
          </div>
        </form>
      </section>

      <section class="cm-public-cards">
        <?php foreach ($properties as $row): ?>
          <?php
          $cardImage = cm_primary_image($row);
          $cardVideos = cm_property_videos($row);
          $cardLocation = trim((string)($row['city'] ?? '') . ' ' . (($row['region'] ?? '') !== '' ? '(' . (string)$row['region'] . ')' : ''));
          ?>
          <article class="cm-public-card">
            <div class="cm-public-card-media">
              <?php if ($cardImage): ?>
                <img src="<?= cm_h($cardImage) ?>" alt="<?= cm_h($row['name']) ?>">
              <?php else: ?>
                <div class="cm-public-media-fallback">
                  <span><?= cm_h(cm_booking_monogram((string)$row['name'])) ?></span>
                </div>
              <?php endif; ?>
            </div>

            <div class="cm-public-card-body">
              <div class="cm-public-card-head">
                <div>
                  <h2><?= cm_h($row['name']) ?></h2>
                  <?php if ($cardLocation !== ''): ?>
                    <p><?= cm_h($cardLocation) ?></p>
                  <?php endif; ?>
                </div>
                <?php if ($cardVideos): ?>
                  <span class="cm-feature-pill"><?= count($cardVideos) ?> video</span>
                <?php endif; ?>
              </div>

              <div class="cm-public-card-stats">
                <div><span>Ospiti</span><strong><?= (int)$row['max_guests'] ?></strong></div>
                <div><span>Letti</span><strong><?= (int)$row['beds'] ?></strong></div>
                <div><span>Da</span><strong><?= cm_h(cm_fmt_money((float)$row['base_price'], (string)$row['currency'])) ?></strong></div>
              </div>

              <?php if ($row['description']): ?>
                <div class="cm-public-copy"><?= cm_h(cm_excerpt((string)$row['description'], 180)) ?></div>
              <?php endif; ?>

              <a class="btn-primary" href="/booking.php?property=<?= rawurlencode((string)$row['slug']) ?><?php if ($checkin): ?>&checkin=<?= rawurlencode($checkin) ?><?php endif; ?><?php if ($checkout): ?>&checkout=<?= rawurlencode($checkout) ?><?php endif; ?><?php if ($guests): ?>&guests=<?= rawurlencode((string)$guests) ?><?php endif; ?>">Apri scheda</a>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$properties): ?>
          <article class="cm-public-panel cm-public-empty">
            <div class="cm-public-section-head">
              <div>
                <div class="cm-public-overline">Nessun risultato</div>
                <h2>Nessun soggiorno compatibile</h2>
              </div>
            </div>
            <p>Non ci sono immobili pubblicati compatibili con i filtri selezionati. Prova a modificare le date o il numero di ospiti.</p>
          </article>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>

  <?php if ($property): ?>
    <script>
      (() => {
        const drawer = document.getElementById('mobile-booking-drawer');
        const overlay = document.querySelector('[data-booking-overlay]');
        const openButtons = document.querySelectorAll('[data-booking-toggle]');
        const closeButtons = document.querySelectorAll('[data-booking-close]');
        if (!drawer || !overlay || openButtons.length === 0) {
          return;
        }

        const setOpen = (open) => {
          drawer.classList.toggle('is-open', open);
          overlay.classList.toggle('is-open', open);
          drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
          document.body.classList.toggle('cm-booking-drawer-open', open);
          openButtons.forEach((button) => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
        };

        openButtons.forEach((button) => {
          button.addEventListener('click', () => setOpen(true));
        });

        closeButtons.forEach((button) => {
          button.addEventListener('click', () => setOpen(false));
        });

        overlay.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            setOpen(false);
          }
        });

        <?php if ($mobileDrawerInitiallyOpen): ?>
        setOpen(true);
        <?php endif; ?>
      })();
    </script>
  <?php endif; ?>
</body>
</html>
