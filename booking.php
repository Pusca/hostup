<?php
declare(strict_types=1);

require_once __DIR__ . '/crm/channel/lib.php';

cm_install_schema();

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
$galleryImages = $property ? cm_gallery_images($property) : [];
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
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= $property ? cm_h($property['name']) . ' | Prenota' : 'Prenotazione diretta | HostUp' ?></title>
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/crm.css">
  <link rel="stylesheet" href="<?= cm_h(CRM_BASE_URL) ?>/assets/channel.css">
</head>
<body class="crm-bg">
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div class="bn">HostUp <span>Booking</span></div>
      </div>
      <?php if ($property): ?>
        <div class="right">
          <a class="btn" href="/booking.php">Altri immobili</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="wrap">
    <section class="head">
      <div>
        <h1><?= $property ? cm_h($property['name']) : 'Prenotazione diretta' ?></h1>
        <p><?= $property ? cm_h(trim(($property['city'] ?: '') . ' ' . ($property['region'] ?: ''))) : 'Ricerca disponibilità sul booking engine diretto integrato con il calendario unico.' ?></p>
      </div>
    </section>

    <?php if ($success): ?>
      <div class="cm-alert success"><?= cm_h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="cm-alert error"><?= cm_h($error) ?></div>
    <?php endif; ?>

    <?php if ($property): ?>
      <?php if ($galleryImages): ?>
        <section class="box cm-panel cm-hero-media">
          <img src="<?= cm_h($galleryImages[0]) ?>" alt="<?= cm_h($property['name']) ?>">
          <?php if (count($galleryImages) > 1): ?>
            <div class="cm-gallery-strip">
              <?php foreach (array_slice($galleryImages, 1, 4) as $image): ?>
                <img src="<?= cm_h($image) ?>" alt="<?= cm_h($property['name']) ?>">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <section class="cm-grid">
        <article class="box cm-panel">
          <div class="boxTitle">Dettagli soggiorno</div>
          <?php if ($property['description']): ?>
            <p><?= nl2br(cm_h((string)$property['description'])) ?></p>
          <?php endif; ?>
          <div class="cm-kv"><strong>Capienza:</strong> <?= (int)$property['max_guests'] ?> ospiti</div>
          <div class="cm-kv"><strong>Letti:</strong> <?= (int)$property['beds'] ?></div>
          <div class="cm-kv"><strong>Check-in:</strong> <?= cm_h(substr((string)$property['checkin_from'], 0, 5)) ?> - <?= cm_h(substr((string)$property['checkin_until'], 0, 5)) ?></div>
          <div class="cm-kv"><strong>Check-out:</strong> entro <?= cm_h(substr((string)$property['checkout_until'], 0, 5)) ?></div>
          <div class="cm-kv"><strong>Prezzo base:</strong> <?= cm_h(cm_fmt_money((float)$property['base_price'], (string)$property['currency'])) ?></div>

          <?php if ($highlights): ?>
            <div class="cm-subtitle">Punti forti</div>
            <ul class="cm-list">
              <?php foreach ($highlights as $item): ?>
                <li><?= cm_h($item) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($amenities): ?>
            <div class="cm-subtitle">Servizi inclusi</div>
            <ul class="cm-list">
              <?php foreach ($amenities as $item): ?>
                <li><?= cm_h($item) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <form method="get" class="cm-form" style="margin-top:14px;">
            <input type="hidden" name="property" value="<?= cm_h($property['slug']) ?>">
            <div class="cm-form-grid4">
              <div>
                <label>Check-in</label>
                <input type="date" name="checkin" value="<?= cm_h($checkin) ?>" required />
              </div>
              <div>
                <label>Check-out</label>
                <input type="date" name="checkout" value="<?= cm_h($checkout) ?>" required />
              </div>
              <div>
                <label>Ospiti</label>
                <input type="number" name="guests" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= cm_h($guests) ?>" />
              </div>
              <div class="cm-form-submit">
                <button class="btn-primary" type="submit">Verifica disponibilità</button>
              </div>
            </div>
          </form>
        </article>

        <article class="box cm-panel">
          <div class="boxTitle">Prenota</div>
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
            <p>Inserisci le date per calcolare disponibilità e preventivo.</p>
          <?php endif; ?>

          <form method="post" class="cm-form">
            <input type="hidden" name="action" value="create_booking">
            <input type="hidden" name="property_slug" value="<?= cm_h($property['slug']) ?>">
            <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
            <input type="hidden" name="checkin_date" value="<?= cm_h($checkin) ?>">
            <input type="hidden" name="checkout_date" value="<?= cm_h($checkout) ?>">
            <div class="cm-form-split">
              <div>
                <label>Nome e cognome</label>
                <input name="guest_name" required />
              </div>
              <div>
                <label>Email</label>
                <input name="guest_email" type="email" required />
              </div>
            </div>
            <div class="cm-form-third">
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
            <label>Note</label>
            <textarea name="guest_notes" class="textarea" placeholder="Orario di arrivo, richieste particolari, note utili..."></textarea>
            <button class="btn-primary" type="submit"<?= (!$checkin || !$checkout || !$isAvailable) ? ' disabled' : '' ?>>Invia richiesta</button>
          </form>
        </article>
      </section>

      <?php if ($property['arrival_instructions'] || $property['checkin_instructions'] || $property['checkout_instructions'] || $property['house_rules'] || $property['contact_name'] || $property['contact_phone'] || count($galleryImages) > 1): ?>
        <section class="cm-info-grid">
          <?php if ($property['arrival_instructions']): ?>
            <article class="box cm-panel">
              <div class="boxTitle">Come arrivare</div>
              <div class="cm-richtext"><?= nl2br(cm_h((string)$property['arrival_instructions'])) ?></div>
            </article>
          <?php endif; ?>

          <?php if ($property['checkin_instructions']): ?>
            <article class="box cm-panel">
              <div class="boxTitle">Istruzioni check-in</div>
              <div class="cm-richtext"><?= nl2br(cm_h((string)$property['checkin_instructions'])) ?></div>
            </article>
          <?php endif; ?>

          <?php if ($property['checkout_instructions']): ?>
            <article class="box cm-panel">
              <div class="boxTitle">Istruzioni check-out</div>
              <div class="cm-richtext"><?= nl2br(cm_h((string)$property['checkout_instructions'])) ?></div>
            </article>
          <?php endif; ?>

          <?php if ($property['house_rules']): ?>
            <article class="box cm-panel">
              <div class="boxTitle">Regole della casa</div>
              <div class="cm-richtext"><?= nl2br(cm_h((string)$property['house_rules'])) ?></div>
            </article>
          <?php endif; ?>

          <?php if ($property['contact_name'] || $property['contact_phone']): ?>
            <article class="box cm-panel">
              <div class="boxTitle">Contatto soggiorno</div>
              <?php if ($property['contact_name']): ?>
                <div class="cm-kv"><strong>Referente:</strong> <?= cm_h($property['contact_name']) ?></div>
              <?php endif; ?>
              <?php if ($property['contact_phone']): ?>
                <div class="cm-kv"><strong>Telefono:</strong> <a href="tel:<?= cm_h($property['contact_phone']) ?>"><?= cm_h($property['contact_phone']) ?></a></div>
              <?php endif; ?>
            </article>
          <?php endif; ?>

          <?php if (count($galleryImages) > 1): ?>
            <article class="box cm-panel cm-gallery-card">
              <div class="boxTitle">Galleria</div>
              <div class="cm-gallery-grid">
                <?php foreach ($galleryImages as $image): ?>
                  <img src="<?= cm_h($image) ?>" alt="<?= cm_h($property['name']) ?>">
                <?php endforeach; ?>
              </div>
            </article>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    <?php else: ?>
      <section class="box cm-panel">
        <div class="boxTitle">Trova disponibilità</div>
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
              <button class="btn-primary" type="submit">Cerca</button>
            </div>
          </div>
        </form>
      </section>

      <section class="cm-property-list">
        <?php foreach ($properties as $row): ?>
          <article class="box cm-panel cm-public-card">
            <?php $cardImage = cm_primary_image($row); ?>
            <?php if ($cardImage): ?>
              <img class="cm-public-thumb" src="<?= cm_h($cardImage) ?>" alt="<?= cm_h($row['name']) ?>">
            <?php endif; ?>
            <div class="boxTitle"><?= cm_h($row['name']) ?></div>
            <div class="cm-kv"><strong>Località:</strong> <?= cm_h(trim(($row['city'] ?: '') . ' ' . ($row['region'] ?: ''))) ?></div>
            <div class="cm-kv"><strong>Capienza:</strong> <?= (int)$row['max_guests'] ?> ospiti</div>
            <div class="cm-kv"><strong>Prezzo base:</strong> <?= cm_h(cm_fmt_money((float)$row['base_price'], (string)$row['currency'])) ?></div>
            <?php if ($row['description']): ?>
              <p><?= cm_h(cm_excerpt((string)$row['description'], 180)) ?></p>
            <?php endif; ?>
            <a class="btn-primary" href="/booking.php?property=<?= rawurlencode((string)$row['slug']) ?><?php if ($checkin): ?>&checkin=<?= rawurlencode($checkin) ?><?php endif; ?><?php if ($checkout): ?>&checkout=<?= rawurlencode($checkout) ?><?php endif; ?><?php if ($guests): ?>&guests=<?= rawurlencode((string)$guests) ?><?php endif; ?>">Apri scheda</a>
          </article>
        <?php endforeach; ?>
        <?php if (!$properties): ?>
          <article class="box cm-panel">
            <div class="boxTitle">Nessun immobile disponibile</div>
            <p>Non ci sono immobili pubblicati compatibili con i filtri selezionati.</p>
          </article>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
