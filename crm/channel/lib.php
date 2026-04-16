<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function cm_base_url(string $path = ''): string {
  $base = rtrim(CRM_BASE_URL, '/') . '/channel';
  if ($path === '') {
    return $base;
  }
  return $base . '/' . ltrim($path, '/');
}

function cm_h(mixed $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cm_fmt_date(?string $value): string {
  if (!$value) {
    return '';
  }
  $dt = date_create_immutable($value);
  return $dt ? $dt->format('d/m/Y') : $value;
}

function cm_fmt_datetime(?string $value): string {
  if (!$value) {
    return '';
  }
  $dt = date_create_immutable($value);
  return $dt ? $dt->format('d/m/Y H:i') : $value;
}

function cm_fmt_money(float|int|string $value, string $currency = 'EUR'): string {
  return number_format((float)$value, 2, ',', '.') . ' ' . strtoupper($currency);
}

function cm_excerpt(string $value, int $width = 180): string {
  if (function_exists('mb_strimwidth')) {
    return mb_strimwidth($value, 0, $width, '...');
  }
  if (strlen($value) <= $width) {
    return $value;
  }
  return substr($value, 0, max(0, $width - 3)) . '...';
}

function cm_channels(): array {
  return [
    'direct' => 'Direct',
    'airbnb' => 'Airbnb',
    'booking' => 'Booking.com',
    'manual' => 'Manuale',
    'other' => 'Altro',
  ];
}

function cm_booking_statuses(): array {
  return [
    'pending' => 'In attesa',
    'confirmed' => 'Confermata',
    'cancelled' => 'Cancellata',
    'blocked' => 'Bloccata',
  ];
}

function cm_active_booking_statuses(): array {
  return ['pending', 'confirmed', 'blocked'];
}

function cm_flash_set(string $type, string $message): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    return;
  }
  $_SESSION['cm_flash'] = ['type' => $type, 'message' => $message];
}

function cm_flash_get(): ?array {
  if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['cm_flash'])) {
    return null;
  }
  $flash = $_SESSION['cm_flash'];
  unset($_SESSION['cm_flash']);
  return is_array($flash) ? $flash : null;
}

function cm_redirect(string $url): never {
  header('Location: ' . $url);
  exit;
}

function cm_install_schema(): void {
  static $installed = false;
  if ($installed) {
    return;
  }

  $sql = file_get_contents(__DIR__ . '/sql/channel_manager.sql');
  if ($sql === false) {
    throw new RuntimeException('Schema channel manager non trovato.');
  }

  $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
  foreach ($parts as $part) {
    $statement = trim($part);
    if ($statement === '') {
      continue;
    }
    db()->exec($statement);
  }

  $installed = true;
}

function cm_slugify(string $value): string {
  $value = trim($value);
  $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = strtolower($value);
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
  $value = trim($value, '-');
  return $value !== '' ? $value : 'immobile';
}

function cm_unique_property_slug(string $baseSlug, int $excludeId = 0): string {
  $slug = $baseSlug;
  $n = 1;

  while (true) {
    $sql = 'SELECT id FROM cm_properties WHERE slug = ?';
    $params = [$slug];
    if ($excludeId > 0) {
      $sql .= ' AND id <> ?';
      $params[] = $excludeId;
    }
    $st = db()->prepare($sql . ' LIMIT 1');
    $st->execute($params);
    if (!$st->fetch()) {
      return $slug;
    }
    $n++;
    $slug = $baseSlug . '-' . $n;
  }
}

function cm_random_token(int $length = 40): string {
  return substr(bin2hex(random_bytes(max(20, (int)ceil($length / 2)))), 0, $length);
}

function cm_money_value(mixed $value): float {
  $raw = str_replace(',', '.', trim((string)$value));
  return round((float)$raw, 2);
}

function cm_int_value(mixed $value, int $default = 0): int {
  if ($value === null || $value === '') {
    return $default;
  }
  return (int)$value;
}

function cm_normalize_date(string $value): string {
  $value = trim($value);
  $dt = date_create_immutable($value);
  if (!$dt) {
    throw new InvalidArgumentException('Data non valida.');
  }
  return $dt->format('Y-m-d');
}

function cm_normalize_time(string $value, string $fallback): string {
  $value = trim($value);
  if ($value === '') {
    return $fallback;
  }
  $dt = date_create_immutable($value);
  if (!$dt) {
    throw new InvalidArgumentException('Orario non valido.');
  }
  return $dt->format('H:i:s');
}

function cm_date_diff_nights(string $checkin, string $checkout): int {
  $start = new DateTimeImmutable($checkin);
  $end = new DateTimeImmutable($checkout);
  return (int)$start->diff($end)->days;
}

function cm_booking_status_label(string $status): string {
  $map = cm_booking_statuses();
  return $map[$status] ?? $status;
}

function cm_channel_label(string $channel): string {
  $map = cm_channels();
  return $map[$channel] ?? strtoupper($channel);
}

function cm_dashboard_stats(): array {
  return [
    'clients' => (int)db()->query('SELECT COUNT(*) FROM cm_clients')->fetchColumn(),
    'properties' => (int)db()->query('SELECT COUNT(*) FROM cm_properties')->fetchColumn(),
    'published_properties' => (int)db()->query('SELECT COUNT(*) FROM cm_properties WHERE published = 1')->fetchColumn(),
    'active_bookings' => (int)db()->query(
      "SELECT COUNT(*) FROM cm_bookings
       WHERE status IN ('pending','confirmed','blocked')
         AND checkout_date > CURDATE()"
    )->fetchColumn(),
  ];
}

function cm_clients(): array {
  $st = db()->query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM cm_properties p WHERE p.client_id = c.id) AS property_count
     FROM cm_clients c
     ORDER BY c.name ASC'
  );
  return $st->fetchAll() ?: [];
}

function cm_client(int $id): ?array {
  $st = db()->prepare('SELECT * FROM cm_clients WHERE id = ? LIMIT 1');
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ?: null;
}

function cm_properties(): array {
  $st = db()->query(
    "SELECT p.*,
            c.name AS client_name,
            (SELECT COUNT(*)
             FROM cm_bookings b
             WHERE b.property_id = p.id
               AND b.status IN ('pending','confirmed','blocked')
               AND b.checkout_date > CURDATE()) AS active_booking_count
     FROM cm_properties p
     INNER JOIN cm_clients c ON c.id = p.client_id
     ORDER BY p.created_at DESC, p.id DESC"
  );
  return $st->fetchAll() ?: [];
}

function cm_recent_bookings(int $limit = 25, ?int $propertyId = null): array {
  $sql = "SELECT b.*, p.name AS property_name, p.slug AS property_slug
          FROM cm_bookings b
          INNER JOIN cm_properties p ON p.id = b.property_id";
  $params = [];

  if ($propertyId !== null) {
    $sql .= ' WHERE b.property_id = ?';
    $params[] = $propertyId;
  }

  $sql .= ' ORDER BY b.checkin_date DESC, b.id DESC LIMIT ' . max(1, (int)$limit);
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_property(int $id): ?array {
  $st = db()->prepare(
    'SELECT p.*, c.name AS client_name, c.email AS client_email, c.phone AS client_phone
     FROM cm_properties p
     INNER JOIN cm_clients c ON c.id = p.client_id
     WHERE p.id = ?
     LIMIT 1'
  );
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ?: null;
}

function cm_property_by_slug(string $slug): ?array {
  $st = db()->prepare(
    'SELECT p.*, c.name AS client_name
     FROM cm_properties p
     INNER JOIN cm_clients c ON c.id = p.client_id
     WHERE p.slug = ?
     LIMIT 1'
  );
  $st->execute([$slug]);
  $row = $st->fetch();
  return $row ?: null;
}

function cm_property_by_ical_token(string $token): ?array {
  $st = db()->prepare('SELECT * FROM cm_properties WHERE ical_export_token = ? LIMIT 1');
  $st->execute([$token]);
  $row = $st->fetch();
  return $row ?: null;
}

function cm_property_connections(int $propertyId): array {
  $st = db()->prepare(
    'SELECT *
     FROM cm_channel_connections
     WHERE property_id = ?
     ORDER BY channel_code ASC'
  );
  $st->execute([$propertyId]);
  return $st->fetchAll() ?: [];
}

function cm_public_properties(array $filters = []): array {
  $params = [];
  $sql = 'SELECT p.*, c.name AS client_name
          FROM cm_properties p
          INNER JOIN cm_clients c ON c.id = p.client_id
          WHERE p.published = 1
            AND p.direct_booking_enabled = 1';

  $guests = cm_int_value($filters['guests'] ?? 0);
  if ($guests > 0) {
    $sql .= ' AND p.max_guests >= ?';
    $params[] = $guests;
  }

  $sql .= ' ORDER BY p.city ASC, p.name ASC';
  $st = db()->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll() ?: [];

  $checkin = trim((string)($filters['checkin'] ?? ''));
  $checkout = trim((string)($filters['checkout'] ?? ''));
  if ($checkin === '' || $checkout === '') {
    return $rows;
  }

  $available = [];
  foreach ($rows as $row) {
    if (cm_is_property_available((int)$row['id'], $checkin, $checkout)) {
      $available[] = $row;
    }
  }
  return $available;
}

function cm_save_client(array $input): int {
  $id = cm_int_value($input['id'] ?? 0);
  $name = trim((string)($input['name'] ?? ''));
  if ($name === '') {
    throw new InvalidArgumentException('Il nome cliente è obbligatorio.');
  }

  $payload = [
    $name,
    trim((string)($input['company_name'] ?? '')) ?: null,
    trim((string)($input['email'] ?? '')) ?: null,
    trim((string)($input['phone'] ?? '')) ?: null,
    trim((string)($input['vat_number'] ?? '')) ?: null,
    trim((string)($input['notes'] ?? '')) ?: null,
  ];

  if ($id > 0) {
    $payload[] = $id;
    db()->prepare(
      'UPDATE cm_clients
       SET name = ?, company_name = ?, email = ?, phone = ?, vat_number = ?, notes = ?
       WHERE id = ?'
    )->execute($payload);
    return $id;
  }

  db()->prepare(
    'INSERT INTO cm_clients (name, company_name, email, phone, vat_number, notes)
     VALUES (?, ?, ?, ?, ?, ?)'
  )->execute($payload);

  return (int)db()->lastInsertId();
}

function cm_save_property(array $input): int {
  $id = cm_int_value($input['id'] ?? 0);
  $clientId = cm_int_value($input['client_id'] ?? 0);
  $name = trim((string)($input['name'] ?? ''));
  if ($clientId <= 0) {
    throw new InvalidArgumentException('Seleziona il cliente proprietario.');
  }
  if ($name === '') {
    throw new InvalidArgumentException('Il nome immobile è obbligatorio.');
  }

  $checkinFrom = cm_normalize_time((string)($input['checkin_from'] ?? ''), '15:00:00');
  $checkinUntil = cm_normalize_time((string)($input['checkin_until'] ?? ''), '20:00:00');
  $checkoutUntil = cm_normalize_time((string)($input['checkout_until'] ?? ''), '10:00:00');

  $baseSlug = cm_slugify((string)($input['slug'] ?? $name));
  $slug = cm_unique_property_slug($baseSlug, $id);

  $payload = [
    $clientId,
    $name,
    $slug,
    trim((string)($input['description'] ?? '')) ?: null,
    trim((string)($input['address_line1'] ?? '')) ?: null,
    trim((string)($input['city'] ?? '')) ?: null,
    trim((string)($input['region'] ?? '')) ?: null,
    strtoupper(trim((string)($input['country_code'] ?? 'IT'))) ?: 'IT',
    trim((string)($input['timezone_name'] ?? 'Europe/Rome')) ?: 'Europe/Rome',
    max(0, cm_int_value($input['bedrooms'] ?? 0)),
    max(0.5, (float)str_replace(',', '.', trim((string)($input['bathrooms'] ?? '1')))),
    max(1, cm_int_value($input['beds'] ?? 1)),
    max(1, cm_int_value($input['max_guests'] ?? 2)),
    max(1, cm_int_value($input['min_nights'] ?? 1)),
    $checkinFrom,
    $checkinUntil,
    $checkoutUntil,
    max(0, cm_money_value($input['base_price'] ?? 0)),
    max(0, cm_money_value($input['cleaning_fee'] ?? 0)),
    strtoupper(trim((string)($input['currency'] ?? 'EUR'))) ?: 'EUR',
    max(0, cm_int_value($input['booking_notice_hours'] ?? 24)),
    !empty($input['direct_booking_enabled']) ? 1 : 0,
    !empty($input['published']) ? 1 : 0,
  ];

  if ($id > 0) {
    $payload[] = $id;
    db()->prepare(
      'UPDATE cm_properties
       SET client_id = ?, name = ?, slug = ?, description = ?, address_line1 = ?, city = ?, region = ?,
           country_code = ?, timezone_name = ?, bedrooms = ?, bathrooms = ?, beds = ?, max_guests = ?,
           min_nights = ?, checkin_from = ?, checkin_until = ?, checkout_until = ?, base_price = ?,
           cleaning_fee = ?, currency = ?, booking_notice_hours = ?, direct_booking_enabled = ?, published = ?
       WHERE id = ?'
    )->execute($payload);
    return $id;
  }

  $token = cm_random_token(40);
  $payload[] = $token;
  db()->prepare(
    'INSERT INTO cm_properties (
        client_id, name, slug, description, address_line1, city, region, country_code, timezone_name,
        bedrooms, bathrooms, beds, max_guests, min_nights, checkin_from, checkin_until, checkout_until,
        base_price, cleaning_fee, currency, booking_notice_hours, direct_booking_enabled, published, ical_export_token
     ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute($payload);

  return (int)db()->lastInsertId();
}

function cm_save_connection(array $input): int {
  $id = cm_int_value($input['id'] ?? 0);
  $propertyId = cm_int_value($input['property_id'] ?? 0);
  $channelCode = trim((string)($input['channel_code'] ?? ''));
  $syncMode = trim((string)($input['sync_mode'] ?? 'ical'));
  $enabled = !empty($input['enabled']) ? 1 : 0;

  if ($propertyId <= 0) {
    throw new InvalidArgumentException('Immobile non valido.');
  }
  if ($channelCode === '') {
    throw new InvalidArgumentException('Seleziona il canale.');
  }

  $payload = [
    $propertyId,
    $channelCode,
    $syncMode,
    trim((string)($input['external_listing_id'] ?? '')) ?: null,
    trim((string)($input['import_url'] ?? '')) ?: null,
    $enabled,
  ];

  if ($id > 0) {
    $payload[] = $id;
    db()->prepare(
      'UPDATE cm_channel_connections
       SET property_id = ?, channel_code = ?, sync_mode = ?, external_listing_id = ?, import_url = ?, enabled = ?
       WHERE id = ?'
    )->execute($payload);
    return $id;
  }

  db()->prepare(
    'INSERT INTO cm_channel_connections (property_id, channel_code, sync_mode, external_listing_id, import_url, enabled)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       sync_mode = VALUES(sync_mode),
       external_listing_id = VALUES(external_listing_id),
       import_url = VALUES(import_url),
       enabled = VALUES(enabled)'
  )->execute($payload);

  $existing = db()->prepare(
    'SELECT id FROM cm_channel_connections WHERE property_id = ? AND channel_code = ? LIMIT 1'
  );
  $existing->execute([$propertyId, $channelCode]);
  return (int)$existing->fetchColumn();
}

function cm_booking_conflicts(int $propertyId, string $checkin, string $checkout, int $excludeBookingId = 0): array {
  $sql = "SELECT *
          FROM cm_bookings
          WHERE property_id = ?
            AND status IN ('pending','confirmed','blocked')
            AND NOT (checkout_date <= ? OR checkin_date >= ?)";
  $params = [$propertyId, $checkin, $checkout];

  if ($excludeBookingId > 0) {
    $sql .= ' AND id <> ?';
    $params[] = $excludeBookingId;
  }

  $sql .= ' ORDER BY checkin_date ASC';
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_is_property_available(int $propertyId, string $checkin, string $checkout): bool {
  $checkin = cm_normalize_date($checkin);
  $checkout = cm_normalize_date($checkout);
  return count(cm_booking_conflicts($propertyId, $checkin, $checkout)) === 0;
}

function cm_quote(array $property, string $checkin, string $checkout): array {
  $checkin = cm_normalize_date($checkin);
  $checkout = cm_normalize_date($checkout);
  $nights = cm_date_diff_nights($checkin, $checkout);
  $nightly = (float)$property['base_price'];
  $cleaning = (float)$property['cleaning_fee'];
  $subtotal = round($nightly * $nights, 2);
  $total = round($subtotal + $cleaning, 2);
  return [
    'nights' => $nights,
    'nightly_rate' => $nightly,
    'cleaning_fee' => $cleaning,
    'subtotal' => $subtotal,
    'total' => $total,
    'currency' => $property['currency'] ?: 'EUR',
  ];
}

function cm_create_direct_booking(array $input): int {
  $propertyId = cm_int_value($input['property_id'] ?? 0);
  $property = cm_property($propertyId);
  if (!$property) {
    throw new InvalidArgumentException('Immobile non trovato.');
  }
  if (!(int)$property['direct_booking_enabled']) {
    throw new InvalidArgumentException('Prenotazione diretta disabilitata per questo immobile.');
  }

  $checkin = cm_normalize_date((string)($input['checkin_date'] ?? ''));
  $checkout = cm_normalize_date((string)($input['checkout_date'] ?? ''));
  $nights = cm_date_diff_nights($checkin, $checkout);
  if ($nights <= 0) {
    throw new InvalidArgumentException('Le date di soggiorno non sono valide.');
  }
  if ($nights < (int)$property['min_nights']) {
    throw new InvalidArgumentException('Il soggiorno minimo per questo immobile è di ' . (int)$property['min_nights'] . ' notti.');
  }

  $adults = max(1, cm_int_value($input['adults'] ?? 1, 1));
  $children = max(0, cm_int_value($input['children'] ?? 0));
  if (($adults + $children) > (int)$property['max_guests']) {
    throw new InvalidArgumentException('Numero ospiti superiore alla capienza dell\'immobile.');
  }

  if (!cm_is_property_available($propertyId, $checkin, $checkout)) {
    throw new InvalidArgumentException('Le date selezionate non sono più disponibili.');
  }

  $guestName = trim((string)($input['guest_name'] ?? ''));
  $guestEmail = trim((string)($input['guest_email'] ?? ''));
  if ($guestName === '' || $guestEmail === '') {
    throw new InvalidArgumentException('Nome ed email sono obbligatori.');
  }

  $quote = cm_quote($property, $checkin, $checkout);
  $externalId = 'direct-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

  db()->prepare(
    'INSERT INTO cm_bookings (
       property_id, source_channel, external_id, status, booking_type, guest_name, guest_email, guest_phone,
       guest_notes, summary, adults, children, checkin_date, checkout_date, nights, total_amount, currency
     ) VALUES (
       ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute([
    $propertyId,
    'direct',
    $externalId,
    'pending',
    'reservation',
    $guestName,
    $guestEmail,
    trim((string)($input['guest_phone'] ?? '')) ?: null,
    trim((string)($input['guest_notes'] ?? '')) ?: null,
    'Prenotazione diretta ' . $guestName,
    $adults,
    $children,
    $checkin,
    $checkout,
    $quote['nights'],
    $quote['total'],
    $quote['currency'],
  ]);

  return (int)db()->lastInsertId();
}

function cm_create_manual_block(array $input): int {
  $propertyId = cm_int_value($input['property_id'] ?? 0);
  $property = cm_property($propertyId);
  if (!$property) {
    throw new InvalidArgumentException('Immobile non trovato.');
  }

  $checkin = cm_normalize_date((string)($input['checkin_date'] ?? ''));
  $checkout = cm_normalize_date((string)($input['checkout_date'] ?? ''));
  $nights = cm_date_diff_nights($checkin, $checkout);
  if ($nights <= 0) {
    throw new InvalidArgumentException('Intervallo date non valido.');
  }
  if (!cm_is_property_available($propertyId, $checkin, $checkout)) {
    throw new InvalidArgumentException('Esiste già una prenotazione o un blocco nel periodo selezionato.');
  }

  $summary = trim((string)($input['summary'] ?? 'Blocco manuale'));

  db()->prepare(
    'INSERT INTO cm_bookings (
       property_id, source_channel, status, booking_type, guest_name, summary, checkin_date, checkout_date, nights, total_amount, currency
     ) VALUES (
       ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute([
    $propertyId,
    'manual',
    'blocked',
    'block',
    $summary,
    $summary,
    $checkin,
    $checkout,
    $nights,
    0,
    $property['currency'] ?: 'EUR',
  ]);

  return (int)db()->lastInsertId();
}

function cm_update_booking_status(int $bookingId, string $status): void {
  if (!array_key_exists($status, cm_booking_statuses())) {
    throw new InvalidArgumentException('Stato prenotazione non valido.');
  }
  db()->prepare('UPDATE cm_bookings SET status = ? WHERE id = ?')->execute([$status, $bookingId]);
}

function cm_property_occupancy(int $propertyId, int $days = 60): array {
  $start = new DateTimeImmutable('today');
  $end = $start->modify('+' . max(1, $days - 1) . ' days');

  $st = db()->prepare(
    "SELECT source_channel, status, summary, guest_name, checkin_date, checkout_date
     FROM cm_bookings
     WHERE property_id = ?
       AND status IN ('pending','confirmed','blocked')
       AND checkin_date <= ?
       AND checkout_date >= ?"
  );
  $st->execute([$propertyId, $end->format('Y-m-d'), $start->format('Y-m-d')]);
  $bookings = $st->fetchAll() ?: [];

  $map = [];
  foreach ($bookings as $booking) {
    $cursor = new DateTimeImmutable($booking['checkin_date']);
    $checkout = new DateTimeImmutable($booking['checkout_date']);
    while ($cursor < $checkout) {
      $key = $cursor->format('Y-m-d');
      $map[$key] = [
        'busy' => true,
        'status' => $booking['status'],
        'channel' => $booking['source_channel'],
        'label' => $booking['summary'] ?: ($booking['guest_name'] ?: cm_channel_label((string)$booking['source_channel'])),
      ];
      $cursor = $cursor->modify('+1 day');
    }
  }

  $daysList = [];
  $cursor = $start;
  while ($cursor <= $end) {
    $key = $cursor->format('Y-m-d');
    $daysList[] = [
      'date' => $key,
      'label' => $cursor->format('d M'),
      'weekday' => $cursor->format('D'),
      'busy' => !empty($map[$key]['busy']),
      'status' => $map[$key]['status'] ?? 'free',
      'channel' => $map[$key]['channel'] ?? '',
      'booking_label' => $map[$key]['label'] ?? 'Disponibile',
    ];
    $cursor = $cursor->modify('+1 day');
  }

  return $daysList;
}

function cm_direct_booking_url(array $property): string {
  return '/booking.php?property=' . rawurlencode((string)$property['slug']);
}

function cm_ical_export_url(array $property): string {
  return cm_base_url('ical.php') . '?token=' . rawurlencode((string)$property['ical_export_token']);
}

function cm_ical_datetime(string $date): string {
  return str_replace('-', '', $date);
}

function cm_generate_ical(array $property): string {
  $st = db()->prepare(
    "SELECT id, source_channel, status, summary, guest_name, checkin_date, checkout_date, updated_at
     FROM cm_bookings
     WHERE property_id = ?
       AND status IN ('pending','confirmed','blocked')
     ORDER BY checkin_date ASC"
  );
  $st->execute([(int)$property['id']]);
  $bookings = $st->fetchAll() ?: [];

  $lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//HostUp//Channel Manager//IT',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'X-WR-CALNAME:' . cm_escape_ical_text((string)$property['name']),
  ];

  foreach ($bookings as $booking) {
    $uid = 'hostup-' . $property['id'] . '-' . $booking['id'] . '@hostup.local';
    $summary = $booking['summary'] ?: ($booking['guest_name'] ?: 'Booked');
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . cm_escape_ical_text($uid);
    $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z', strtotime((string)$booking['updated_at']));
    $lines[] = 'DTSTART;VALUE=DATE:' . cm_ical_datetime((string)$booking['checkin_date']);
    $lines[] = 'DTEND;VALUE=DATE:' . cm_ical_datetime((string)$booking['checkout_date']);
    $lines[] = 'SUMMARY:' . cm_escape_ical_text($summary);
    $lines[] = 'DESCRIPTION:' . cm_escape_ical_text(cm_channel_label((string)$booking['source_channel']) . ' - ' . cm_booking_status_label((string)$booking['status']));
    $lines[] = 'STATUS:' . ($booking['status'] === 'cancelled' ? 'CANCELLED' : 'CONFIRMED');
    $lines[] = 'END:VEVENT';
  }

  $lines[] = 'END:VCALENDAR';
  return implode("\r\n", $lines) . "\r\n";
}

function cm_escape_ical_text(string $value): string {
  $value = str_replace('\\', '\\\\', $value);
  $value = str_replace(';', '\;', $value);
  $value = str_replace(',', '\,', $value);
  return str_replace(["\r\n", "\n", "\r"], '\n', $value);
}

function cm_ical_fetch_url(string $url): string {
  $context = stream_context_create([
    'http' => [
      'timeout' => 20,
      'ignore_errors' => true,
      'user_agent' => 'HostUp-ChannelManager/1.0',
    ],
  ]);

  $raw = @file_get_contents($url, false, $context);
  if ($raw === false || trim($raw) === '') {
    throw new RuntimeException('Download feed iCal fallito.');
  }
  return $raw;
}

function cm_ical_unfold_lines(string $raw): array {
  $raw = str_replace("\r\n", "\n", $raw);
  $raw = str_replace("\r", "\n", $raw);
  $lines = explode("\n", $raw);
  $unfolded = [];

  foreach ($lines as $line) {
    if ($line === '') {
      continue;
    }
    if (($line[0] ?? '') === ' ' || ($line[0] ?? '') === "\t") {
      $last = count($unfolded) - 1;
      if ($last >= 0) {
        $unfolded[$last] .= substr($line, 1);
      }
      continue;
    }
    $unfolded[] = $line;
  }

  return $unfolded;
}

function cm_parse_ical_date(string $value): ?string {
  $value = trim($value);
  if ($value === '') {
    return null;
  }

  if (preg_match('/^\d{8}$/', $value)) {
    $dt = DateTimeImmutable::createFromFormat('Ymd', $value, new DateTimeZone('UTC'));
    return $dt ? $dt->format('Y-m-d') : null;
  }

  $dt = date_create_immutable($value);
  if ($dt) {
    return $dt->format('Y-m-d');
  }

  if (preg_match('/^(\d{8})T\d{6}Z?$/', $value, $m)) {
    $dt = DateTimeImmutable::createFromFormat('Ymd', $m[1], new DateTimeZone('UTC'));
    return $dt ? $dt->format('Y-m-d') : null;
  }

  return null;
}

function cm_parse_ical(string $raw): array {
  $lines = cm_ical_unfold_lines($raw);
  $events = [];
  $event = null;

  foreach ($lines as $line) {
    if ($line === 'BEGIN:VEVENT') {
      $event = [];
      continue;
    }
    if ($line === 'END:VEVENT') {
      if ($event !== null) {
        $events[] = $event;
      }
      $event = null;
      continue;
    }
    if ($event === null) {
      continue;
    }

    [$namePart, $value] = array_pad(explode(':', $line, 2), 2, '');
    $name = strtoupper((string)explode(';', $namePart, 2)[0]);
    $event[$name] = $value;
  }

  return $events;
}

function cm_sync_log(int $connectionId, string $status, int $imported, string $message): void {
  db()->prepare(
    'INSERT INTO cm_sync_logs (connection_id, status, imported_events, message)
     VALUES (?, ?, ?, ?)'
  )->execute([$connectionId, $status, $imported, $message]);
}

function cm_sync_ical_connection(int $connectionId): array {
  $st = db()->prepare(
    'SELECT cc.*, p.name AS property_name, p.currency
     FROM cm_channel_connections cc
     INNER JOIN cm_properties p ON p.id = cc.property_id
     WHERE cc.id = ?
     LIMIT 1'
  );
  $st->execute([$connectionId]);
  $connection = $st->fetch();
  if (!$connection) {
    throw new InvalidArgumentException('Connessione canale non trovata.');
  }
  if (trim((string)$connection['import_url']) === '') {
    throw new InvalidArgumentException('Import URL iCal non configurato.');
  }

  try {
    $raw = cm_ical_fetch_url((string)$connection['import_url']);
    $events = cm_parse_ical($raw);

    $imported = 0;
    foreach ($events as $event) {
      $checkin = cm_parse_ical_date((string)($event['DTSTART'] ?? ''));
      $checkout = cm_parse_ical_date((string)($event['DTEND'] ?? ''));
      if (!$checkin || !$checkout || $checkin >= $checkout) {
        continue;
      }

      $status = strtoupper((string)($event['STATUS'] ?? 'CONFIRMED')) === 'CANCELLED' ? 'cancelled' : 'confirmed';
      $externalId = trim((string)($event['UID'] ?? ''));
      if ($externalId === '') {
        $externalId = sha1($connection['channel_code'] . '|' . $checkin . '|' . $checkout . '|' . ($event['SUMMARY'] ?? ''));
      }

      $summary = trim((string)($event['SUMMARY'] ?? 'Import ' . cm_channel_label((string)$connection['channel_code'])));
      $notes = trim((string)($event['DESCRIPTION'] ?? '')) ?: null;
      $nights = cm_date_diff_nights($checkin, $checkout);

      db()->prepare(
        'INSERT INTO cm_bookings (
           property_id, connection_id, source_channel, external_id, status, booking_type,
           guest_name, guest_notes, summary, checkin_date, checkout_date, nights, total_amount, currency, imported_at
         ) VALUES (
           ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
         )
         ON DUPLICATE KEY UPDATE
           connection_id = VALUES(connection_id),
           status = VALUES(status),
           guest_name = VALUES(guest_name),
           guest_notes = VALUES(guest_notes),
           summary = VALUES(summary),
           checkin_date = VALUES(checkin_date),
           checkout_date = VALUES(checkout_date),
           nights = VALUES(nights),
           imported_at = NOW(),
           updated_at = CURRENT_TIMESTAMP'
      )->execute([
        (int)$connection['property_id'],
        (int)$connection['id'],
        (string)$connection['channel_code'],
        $externalId,
        $status,
        'reservation',
        $summary,
        $notes,
        $summary,
        $checkin,
        $checkout,
        $nights,
        0,
        $connection['currency'] ?: 'EUR',
      ]);

      $imported++;
    }

    $message = 'Importati ' . $imported . ' eventi da ' . cm_channel_label((string)$connection['channel_code']) . '.';
    db()->prepare(
      'UPDATE cm_channel_connections
       SET last_sync_at = NOW(), last_sync_status = ?, last_sync_message = ?
       WHERE id = ?'
    )->execute(['ok', $message, $connectionId]);
    cm_sync_log($connectionId, 'ok', $imported, $message);

    return ['imported' => $imported, 'message' => $message];
  } catch (Throwable $e) {
    db()->prepare(
      'UPDATE cm_channel_connections
       SET last_sync_at = NOW(), last_sync_status = ?, last_sync_message = ?
       WHERE id = ?'
    )->execute(['error', $e->getMessage(), $connectionId]);
    cm_sync_log($connectionId, 'error', 0, $e->getMessage());
    throw $e;
  }
}

function cm_sync_all_ical_connections(): array {
  $st = db()->query(
    "SELECT id
     FROM cm_channel_connections
     WHERE enabled = 1
       AND sync_mode = 'ical'
       AND import_url IS NOT NULL
       AND import_url <> ''
     ORDER BY id ASC"
  );
  $ids = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];

  $results = [];
  foreach ($ids as $id) {
    try {
      $results[] = ['connection_id' => (int)$id, 'status' => 'ok'] + cm_sync_ical_connection((int)$id);
    } catch (Throwable $e) {
      $results[] = ['connection_id' => (int)$id, 'status' => 'error', 'message' => $e->getMessage(), 'imported' => 0];
    }
  }

  return $results;
}
