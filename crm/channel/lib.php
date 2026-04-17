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

function cm_textarea_value(mixed $value): ?string {
  $value = trim((string)$value);
  if ($value === '') {
    return null;
  }
  $value = str_replace("\r\n", "\n", $value);
  $value = str_replace("\r", "\n", $value);
  return $value;
}

function cm_lines(?string $value): array {
  if ($value === null || trim($value) === '') {
    return [];
  }

  $items = preg_split('/\r?\n+/', $value) ?: [];
  $items = array_map(static fn(string $item): string => trim($item), $items);
  return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
}

function cm_primary_image(array $property): ?string {
  $hero = trim((string)($property['hero_image_url'] ?? ''));
  if ($hero !== '') {
    return $hero;
  }

  $images = cm_lines($property['gallery_images'] ?? null);
  return $images[0] ?? null;
}

function cm_gallery_images(array $property): array {
  $images = cm_lines($property['gallery_images'] ?? null);
  $hero = trim((string)($property['hero_image_url'] ?? ''));
  if ($hero !== '') {
    array_unshift($images, $hero);
  }
  $images = array_values(array_unique(array_filter($images, static fn(string $item): bool => $item !== '')));
  return $images;
}

function cm_property_logo(array $property): ?string {
  $logo = trim((string)($property['logo_image_url'] ?? ''));
  return $logo !== '' ? $logo : null;
}

function cm_property_videos(array $property): array {
  return cm_lines($property['video_urls'] ?? null);
}

function cm_project_root(): string {
  return dirname(__DIR__, 2);
}

function cm_media_base_dir(): string {
  return cm_project_root() . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'properties';
}

function cm_property_media_dir(int $propertyId): string {
  return cm_media_base_dir() . DIRECTORY_SEPARATOR . $propertyId;
}

function cm_property_media_url(int $propertyId, string $filename): string {
  return '/media/properties/' . rawurlencode((string)$propertyId) . '/' . rawurlencode($filename);
}

function cm_uploaded_file_list(array $spec): array {
  $files = [];
  $names = $spec['name'] ?? null;

  if (!is_array($names)) {
    if (($spec['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $files[] = $spec;
    }
    return $files;
  }

  foreach (array_keys($names) as $index) {
    $error = $spec['error'][$index] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
      continue;
    }
    $files[] = [
      'name' => $spec['name'][$index] ?? '',
      'type' => $spec['type'][$index] ?? '',
      'tmp_name' => $spec['tmp_name'][$index] ?? '',
      'error' => $error,
      'size' => $spec['size'][$index] ?? 0,
    ];
  }

  return $files;
}

function cm_store_uploaded_media(array $file, int $propertyId, array $extensions, int $maxSize, string $label): string {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Upload ' . $label . ' non riuscito.');
  }

  $tmpName = (string)($file['tmp_name'] ?? '');
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    throw new RuntimeException('File upload non valido.');
  }

  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > $maxSize) {
    throw new RuntimeException('Il file ' . $label . ' supera la dimensione massima consentita.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = (string)$finfo->file($tmpName);
  $extension = $extensions[$mime] ?? null;
  if ($extension === null) {
    throw new RuntimeException('Formato ' . $label . ' non supportato.');
  }

  $directory = cm_property_media_dir($propertyId);
  if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    throw new RuntimeException('Impossibile creare la cartella media dell\'immobile.');
  }
  @chmod($directory, 0755);

  $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
  $destination = $directory . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file($tmpName, $destination)) {
    throw new RuntimeException('Salvataggio immagine fallito.');
  }
  @chmod($destination, 0644);

  return cm_property_media_url($propertyId, $filename);
}

function cm_store_uploaded_image(array $file, int $propertyId): string {
  return cm_store_uploaded_media(
    $file,
    $propertyId,
    [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
      'image/gif' => 'gif',
      'image/svg+xml' => 'svg',
    ],
    8 * 1024 * 1024,
    'immagine'
  );
}

function cm_store_uploaded_video(array $file, int $propertyId): string {
  return cm_store_uploaded_media(
    $file,
    $propertyId,
    [
      'video/mp4' => 'mp4',
      'video/webm' => 'webm',
      'video/ogg' => 'ogv',
      'video/quicktime' => 'mov',
    ],
    50 * 1024 * 1024,
    'video'
  );
}

function cm_gallery_without_removed(array $images, array $removeImages): array {
  $removeLookup = [];
  foreach ($removeImages as $item) {
    $key = trim((string)$item);
    if ($key !== '') {
      $removeLookup[$key] = true;
    }
  }

  $result = [];
  foreach ($images as $image) {
    $image = trim((string)$image);
    if ($image === '' || isset($removeLookup[$image])) {
      continue;
    }
    $result[] = $image;
  }

  return array_values(array_unique($result));
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

function cm_task_types(): array {
  return [
    'cleaning' => 'Pulizia',
    'maintenance' => 'Manutenzione',
    'checkin' => 'Check-in',
    'checkout' => 'Check-out',
    'guest' => 'Richiesta ospite',
    'general' => 'Operativo',
  ];
}

function cm_task_statuses(): array {
  return [
    'open' => 'Aperto',
    'in_progress' => 'In corso',
    'done' => 'Completato',
    'cancelled' => 'Annullato',
  ];
}

function cm_active_task_statuses(): array {
  return ['open', 'in_progress'];
}

function cm_task_priorities(): array {
  return [
    'low' => 'Bassa',
    'normal' => 'Normale',
    'high' => 'Alta',
    'urgent' => 'Urgente',
  ];
}

function cm_task_type_label(string $taskType): string {
  $map = cm_task_types();
  return $map[$taskType] ?? $taskType;
}

function cm_task_status_label(string $status): string {
  $map = cm_task_statuses();
  return $map[$status] ?? $status;
}

function cm_task_priority_label(string $priority): string {
  $map = cm_task_priorities();
  return $map[$priority] ?? $priority;
}

function cm_task_status_badge_class(string $status): string {
  return match ($status) {
    'done' => 'is-live',
    'in_progress' => 'is-warning',
    default => 'is-draft',
  };
}

function cm_operational_update_types(): array {
  return [
    'daily' => 'Aggiornamento',
    'cleaning' => 'Pulizia',
    'maintenance' => 'Manutenzione',
    'guest' => 'Ospite',
    'checkin' => 'Check-in',
    'checkout' => 'Check-out',
    'issue' => 'Criticita',
  ];
}

function cm_operational_update_type_label(string $updateType): string {
  $map = cm_operational_update_types();
  return $map[$updateType] ?? $updateType;
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

  cm_ensure_channel_schema_upgrades();

  $installed = true;
}

function cm_table_has_column(string $table, string $column): bool {
  $st = db()->prepare(
    'SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = ?
       AND COLUMN_NAME = ?'
  );
  $st->execute([$table, $column]);
  return (int)$st->fetchColumn() > 0;
}

function cm_ensure_column(string $table, string $column, string $definition): void {
  if (cm_table_has_column($table, $column)) {
    return;
  }
  db()->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}

function cm_ensure_tasks_table(): void {
  db()->exec(
    "CREATE TABLE IF NOT EXISTS cm_tasks (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      property_id INT UNSIGNED NOT NULL,
      booking_id INT UNSIGNED DEFAULT NULL,
      created_by_user_id INT UNSIGNED DEFAULT NULL,
      task_type VARCHAR(30) NOT NULL DEFAULT 'general',
      status VARCHAR(30) NOT NULL DEFAULT 'open',
      priority VARCHAR(20) NOT NULL DEFAULT 'normal',
      title VARCHAR(190) NOT NULL,
      details TEXT DEFAULT NULL,
      assignee_name VARCHAR(160) DEFAULT NULL,
      due_at DATETIME DEFAULT NULL,
      completed_at DATETIME DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_cm_tasks_property_status_due (property_id, status, due_at),
      KEY idx_cm_tasks_booking (booking_id),
      KEY idx_cm_tasks_status_due (status, due_at),
      CONSTRAINT fk_cm_tasks_property
        FOREIGN KEY (property_id) REFERENCES cm_properties(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_cm_tasks_booking
        FOREIGN KEY (booking_id) REFERENCES cm_bookings(id)
        ON DELETE SET NULL,
      CONSTRAINT fk_cm_tasks_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES crm_users(id)
        ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );
}

function cm_ensure_operational_updates_table(): void {
  db()->exec(
    "CREATE TABLE IF NOT EXISTS cm_operational_updates (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      property_id INT UNSIGNED NOT NULL,
      booking_id INT UNSIGNED DEFAULT NULL,
      task_id INT UNSIGNED DEFAULT NULL,
      created_by_user_id INT UNSIGNED DEFAULT NULL,
      update_type VARCHAR(30) NOT NULL DEFAULT 'daily',
      owner_visible TINYINT(1) NOT NULL DEFAULT 1,
      title VARCHAR(190) NOT NULL,
      body TEXT DEFAULT NULL,
      happened_at DATETIME DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_cm_operational_updates_property_time (property_id, happened_at, created_at),
      KEY idx_cm_operational_updates_booking (booking_id),
      KEY idx_cm_operational_updates_task (task_id),
      KEY idx_cm_operational_updates_owner_visible (owner_visible, happened_at),
      CONSTRAINT fk_cm_operational_updates_property
        FOREIGN KEY (property_id) REFERENCES cm_properties(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_cm_operational_updates_booking
        FOREIGN KEY (booking_id) REFERENCES cm_bookings(id)
        ON DELETE SET NULL,
      CONSTRAINT fk_cm_operational_updates_task
        FOREIGN KEY (task_id) REFERENCES cm_tasks(id)
        ON DELETE SET NULL,
      CONSTRAINT fk_cm_operational_updates_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES crm_users(id)
        ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );
}

function cm_ensure_channel_schema_upgrades(): void {
  cm_ensure_tasks_table();
  cm_ensure_operational_updates_table();
  cm_ensure_column('cm_properties', 'logo_image_url', 'VARCHAR(255) DEFAULT NULL');
  cm_ensure_column('cm_properties', 'hero_image_url', 'VARCHAR(255) DEFAULT NULL');
  cm_ensure_column('cm_properties', 'gallery_images', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'video_urls', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'public_highlights', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'amenities', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'arrival_instructions', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'checkin_instructions', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'checkout_instructions', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'house_rules', 'TEXT DEFAULT NULL');
  cm_ensure_column('cm_properties', 'contact_name', 'VARCHAR(160) DEFAULT NULL');
  cm_ensure_column('cm_properties', 'contact_phone', 'VARCHAR(60) DEFAULT NULL');
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

function cm_normalize_datetime_or_null(mixed $value): ?string {
  $value = trim((string)$value);
  if ($value === '') {
    return null;
  }

  $dt = date_create_immutable($value);
  if (!$dt) {
    throw new InvalidArgumentException('Data e ora non valide.');
  }

  return $dt->format('Y-m-d H:i:s');
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

function cm_owner_dashboard_summary(int $clientId = 0, int $lookaheadDays = 14): array {
  $clientId = max(0, $clientId);
  $lookaheadDays = max(1, $lookaheadDays);
  $today = new DateTimeImmutable('today');
  $lookaheadDate = $today->modify('+' . $lookaheadDays . ' days')->format('Y-m-d');
  $monthStart = $today->modify('first day of this month')->format('Y-m-d');
  $monthEnd = $today->modify('last day of this month')->format('Y-m-d');

  $propertySql = 'SELECT COUNT(*) AS properties,
                         SUM(CASE WHEN p.published = 1 THEN 1 ELSE 0 END) AS published_properties,
                         SUM(CASE WHEN p.direct_booking_enabled = 1 THEN 1 ELSE 0 END) AS direct_enabled_properties
                  FROM cm_properties p';
  $propertyParams = [];
  if ($clientId > 0) {
    $propertySql .= ' WHERE p.client_id = ?';
    $propertyParams[] = $clientId;
  }
  $propertySt = db()->prepare($propertySql);
  $propertySt->execute($propertyParams);
  $propertyStats = $propertySt->fetch() ?: [];

  $bookingSql = "SELECT COUNT(*) AS active_bookings,
                        SUM(CASE
                              WHEN b.status = 'pending'
                               AND b.source_channel = 'direct'
                               AND b.booking_type = 'reservation'
                              THEN 1 ELSE 0
                            END) AS pending_direct_requests,
                        SUM(CASE
                              WHEN b.checkin_date >= ?
                               AND b.checkin_date <= ?
                              THEN 1 ELSE 0
                            END) AS upcoming_checkins,
                        SUM(CASE
                              WHEN b.checkout_date >= ?
                               AND b.checkout_date <= ?
                              THEN 1 ELSE 0
                            END) AS upcoming_checkouts,
                        SUM(CASE
                              WHEN b.status IN ('pending', 'confirmed')
                               AND b.total_amount > 0
                               AND b.checkin_date >= ?
                               AND b.checkin_date <= ?
                              THEN b.total_amount ELSE 0
                            END) AS tracked_revenue_month,
                        SUM(CASE
                              WHEN b.status IN ('pending', 'confirmed')
                               AND b.total_amount > 0
                               AND b.checkin_date >= ?
                              THEN b.total_amount ELSE 0
                            END) AS tracked_revenue_future
                 FROM cm_bookings b
                 INNER JOIN cm_properties p ON p.id = b.property_id
                 WHERE b.status IN ('pending', 'confirmed', 'blocked')
                   AND b.checkout_date > ?";
  $bookingParams = [
    $today->format('Y-m-d'),
    $lookaheadDate,
    $today->format('Y-m-d'),
    $lookaheadDate,
    $monthStart,
    $monthEnd,
    $today->format('Y-m-d'),
    $today->format('Y-m-d'),
  ];
  if ($clientId > 0) {
    $bookingSql .= ' AND p.client_id = ?';
    $bookingParams[] = $clientId;
  }
  $bookingSt = db()->prepare($bookingSql);
  $bookingSt->execute($bookingParams);
  $bookingStats = $bookingSt->fetch() ?: [];

  return [
    'properties' => (int)($propertyStats['properties'] ?? 0),
    'published_properties' => (int)($propertyStats['published_properties'] ?? 0),
    'direct_enabled_properties' => (int)($propertyStats['direct_enabled_properties'] ?? 0),
    'active_bookings' => (int)($bookingStats['active_bookings'] ?? 0),
    'pending_direct_requests' => (int)($bookingStats['pending_direct_requests'] ?? 0),
    'upcoming_checkins' => (int)($bookingStats['upcoming_checkins'] ?? 0),
    'upcoming_checkouts' => (int)($bookingStats['upcoming_checkouts'] ?? 0),
    'tracked_revenue_month' => round((float)($bookingStats['tracked_revenue_month'] ?? 0), 2),
    'tracked_revenue_future' => round((float)($bookingStats['tracked_revenue_future'] ?? 0), 2),
    'lookahead_days' => $lookaheadDays,
  ];
}

function cm_owner_dashboard_channel_mix(int $clientId = 0): array {
  $clientId = max(0, $clientId);
  $sql = "SELECT b.source_channel,
                 COUNT(*) AS booking_count,
                 SUM(CASE
                       WHEN b.status IN ('pending', 'confirmed')
                        AND b.total_amount > 0
                       THEN b.total_amount ELSE 0
                     END) AS tracked_revenue
          FROM cm_bookings b
          INNER JOIN cm_properties p ON p.id = b.property_id
          WHERE b.status IN ('pending', 'confirmed', 'blocked')
            AND b.checkout_date > CURDATE()";
  $params = [];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= ' GROUP BY b.source_channel
            ORDER BY booking_count DESC, b.source_channel ASC';

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_properties(int $clientId = 0): array {
  $clientId = max(0, $clientId);
  $today = new DateTimeImmutable('today');
  $monthStart = $today->modify('first day of this month')->format('Y-m-d');
  $monthEnd = $today->modify('last day of this month')->format('Y-m-d');

  $sql = "SELECT p.*,
                 c.name AS client_name,
                 (SELECT COUNT(*)
                  FROM cm_bookings b
                  WHERE b.property_id = p.id
                    AND b.status IN ('pending', 'confirmed', 'blocked')
                    AND b.checkout_date > CURDATE()) AS active_booking_count,
                 (SELECT MIN(b.checkin_date)
                  FROM cm_bookings b
                  WHERE b.property_id = p.id
                    AND b.status IN ('pending', 'confirmed', 'blocked')
                    AND b.checkin_date >= CURDATE()) AS next_checkin_date,
                 (SELECT MIN(b.checkout_date)
                  FROM cm_bookings b
                  WHERE b.property_id = p.id
                    AND b.status IN ('pending', 'confirmed', 'blocked')
                    AND b.checkout_date >= CURDATE()) AS next_checkout_date,
                 (SELECT COUNT(*)
                 FROM cm_bookings b
                  WHERE b.property_id = p.id
                    AND b.status = 'pending'
                    AND b.source_channel = 'direct'
                    AND b.booking_type = 'reservation'
                    AND b.checkout_date > CURDATE()) AS pending_direct_count,
                 (SELECT COUNT(*)
                  FROM cm_tasks t
                  WHERE t.property_id = p.id
                    AND t.status IN ('open', 'in_progress')) AS active_task_count,
                 (SELECT COUNT(*)
                  FROM cm_tasks t
                  WHERE t.property_id = p.id
                    AND t.status IN ('open', 'in_progress')
                    AND t.due_at IS NOT NULL
                    AND t.due_at < NOW()) AS overdue_task_count,
                 (SELECT COALESCE(SUM(b.total_amount), 0)
                  FROM cm_bookings b
                  WHERE b.property_id = p.id
                    AND b.status IN ('pending', 'confirmed')
                    AND b.total_amount > 0
                    AND b.checkin_date >= ?
                    AND b.checkin_date <= ?) AS tracked_revenue_month
          FROM cm_properties p
          INNER JOIN cm_clients c ON c.id = p.client_id";
  $params = [$monthStart, $monthEnd];
  if ($clientId > 0) {
    $sql .= ' WHERE p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= ' ORDER BY p.published DESC, p.name ASC';

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_upcoming_bookings(int $clientId = 0, int $limit = 12): array {
  $clientId = max(0, $clientId);
  $limit = max(1, $limit);

  $sql = "SELECT b.*, p.name AS property_name, p.slug AS property_slug, c.name AS client_name
          FROM cm_bookings b
          INNER JOIN cm_properties p ON p.id = b.property_id
          INNER JOIN cm_clients c ON c.id = p.client_id
          WHERE b.status IN ('pending', 'confirmed', 'blocked')
            AND b.checkout_date > CURDATE()";
  $params = [];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= ' ORDER BY b.checkin_date ASC, b.id ASC
            LIMIT ' . $limit;

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_pending_requests(int $clientId = 0, int $limit = 8): array {
  $clientId = max(0, $clientId);
  $limit = max(1, $limit);

  $sql = "SELECT b.*, p.name AS property_name, p.slug AS property_slug, c.name AS client_name
          FROM cm_bookings b
          INNER JOIN cm_properties p ON p.id = b.property_id
          INNER JOIN cm_clients c ON c.id = p.client_id
          WHERE b.status = 'pending'
            AND b.source_channel = 'direct'
            AND b.booking_type = 'reservation'
            AND b.checkout_date > CURDATE()";
  $params = [];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= ' ORDER BY b.checkin_date ASC, b.created_at DESC
            LIMIT ' . $limit;

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_task(int $id): ?array {
  $st = db()->prepare(
    "SELECT t.*,
            p.name AS property_name,
            c.name AS client_name,
            b.summary AS booking_summary,
            b.guest_name AS booking_guest_name,
            b.checkin_date AS booking_checkin_date,
            b.checkout_date AS booking_checkout_date,
            u.name AS created_by_name
     FROM cm_tasks t
     INNER JOIN cm_properties p ON p.id = t.property_id
     INNER JOIN cm_clients c ON c.id = p.client_id
     LEFT JOIN cm_bookings b ON b.id = t.booking_id
     LEFT JOIN crm_users u ON u.id = t.created_by_user_id
     WHERE t.id = ?
     LIMIT 1"
  );
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ?: null;
}

function cm_property_task_summary(int $propertyId): array {
  $st = db()->prepare(
    "SELECT SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done_tasks,
            SUM(CASE
                  WHEN status IN ('open', 'in_progress')
                   AND due_at IS NOT NULL
                   AND due_at < NOW()
                  THEN 1 ELSE 0
                END) AS overdue_tasks
     FROM cm_tasks
     WHERE property_id = ?"
  );
  $st->execute([$propertyId]);
  $row = $st->fetch() ?: [];

  return [
    'open_tasks' => (int)($row['open_tasks'] ?? 0),
    'in_progress_tasks' => (int)($row['in_progress_tasks'] ?? 0),
    'done_tasks' => (int)($row['done_tasks'] ?? 0),
    'overdue_tasks' => (int)($row['overdue_tasks'] ?? 0),
  ];
}

function cm_owner_dashboard_task_summary(int $clientId = 0, int $lookaheadDays = 7): array {
  $clientId = max(0, $clientId);
  $lookaheadDays = max(1, $lookaheadDays);
  $until = (new DateTimeImmutable('now'))->modify('+' . $lookaheadDays . ' days')->format('Y-m-d H:i:s');

  $sql = "SELECT SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END) AS open_tasks,
                 SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
                 SUM(CASE
                       WHEN t.status IN ('open', 'in_progress')
                        AND t.due_at IS NOT NULL
                        AND t.due_at < NOW()
                       THEN 1 ELSE 0
                     END) AS overdue_tasks,
                 SUM(CASE
                       WHEN t.status IN ('open', 'in_progress')
                        AND t.due_at IS NOT NULL
                        AND t.due_at >= NOW()
                        AND t.due_at <= ?
                       THEN 1 ELSE 0
                     END) AS due_soon_tasks
          FROM cm_tasks t
          INNER JOIN cm_properties p ON p.id = t.property_id
          WHERE 1=1";
  $params = [$until];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }

  $st = db()->prepare($sql);
  $st->execute($params);
  $row = $st->fetch() ?: [];

  return [
    'open_tasks' => (int)($row['open_tasks'] ?? 0),
    'in_progress_tasks' => (int)($row['in_progress_tasks'] ?? 0),
    'overdue_tasks' => (int)($row['overdue_tasks'] ?? 0),
    'due_soon_tasks' => (int)($row['due_soon_tasks'] ?? 0),
    'lookahead_days' => $lookaheadDays,
  ];
}

function cm_property_tasks(int $propertyId, int $limit = 30): array {
  $limit = max(1, $limit);
  $st = db()->prepare(
    "SELECT t.*,
            b.summary AS booking_summary,
            b.guest_name AS booking_guest_name,
            b.checkin_date AS booking_checkin_date,
            b.checkout_date AS booking_checkout_date,
            u.name AS created_by_name
     FROM cm_tasks t
     LEFT JOIN cm_bookings b ON b.id = t.booking_id
     LEFT JOIN crm_users u ON u.id = t.created_by_user_id
     WHERE t.property_id = ?
     ORDER BY CASE
                WHEN t.status = 'open' THEN 0
                WHEN t.status = 'in_progress' THEN 1
                WHEN t.status = 'done' THEN 2
                ELSE 3
              END ASC,
              CASE WHEN t.due_at IS NULL THEN 1 ELSE 0 END ASC,
              t.due_at ASC,
              t.created_at DESC
     LIMIT {$limit}"
  );
  $st->execute([$propertyId]);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_tasks(int $clientId = 0, int $limit = 10): array {
  $clientId = max(0, $clientId);
  $limit = max(1, $limit);
  $sql = "SELECT t.*,
                 p.name AS property_name,
                 c.name AS client_name,
                 b.summary AS booking_summary,
                 b.guest_name AS booking_guest_name,
                 b.checkin_date AS booking_checkin_date,
                 b.checkout_date AS booking_checkout_date
          FROM cm_tasks t
          INNER JOIN cm_properties p ON p.id = t.property_id
          INNER JOIN cm_clients c ON c.id = p.client_id
          LEFT JOIN cm_bookings b ON b.id = t.booking_id
          WHERE t.status IN ('open', 'in_progress')";
  $params = [];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= " ORDER BY CASE
                      WHEN t.status = 'open' THEN 0
                      WHEN t.status = 'in_progress' THEN 1
                      WHEN t.status = 'done' THEN 2
                      ELSE 3
                    END ASC,
                    CASE WHEN t.due_at IS NULL THEN 1 ELSE 0 END ASC,
                    t.due_at ASC,
                    t.created_at DESC
            LIMIT {$limit}";

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_save_task(array $input, ?int $createdByUserId = null): int {
  $propertyId = cm_int_value($input['property_id'] ?? 0);
  $property = cm_property($propertyId);
  if (!$property) {
    throw new InvalidArgumentException('Immobile non valido per la task.');
  }

  $taskType = trim((string)($input['task_type'] ?? 'general'));
  if (!array_key_exists($taskType, cm_task_types())) {
    throw new InvalidArgumentException('Tipo task non valido.');
  }

  $priority = trim((string)($input['priority'] ?? 'normal'));
  if (!array_key_exists($priority, cm_task_priorities())) {
    throw new InvalidArgumentException('Priorita task non valida.');
  }

  $title = trim((string)($input['title'] ?? ''));
  if ($title === '') {
    throw new InvalidArgumentException('Titolo task obbligatorio.');
  }

  $bookingId = cm_int_value($input['booking_id'] ?? 0);
  if ($bookingId > 0) {
    $booking = db()->prepare('SELECT id, property_id FROM cm_bookings WHERE id = ? LIMIT 1');
    $booking->execute([$bookingId]);
    $bookingRow = $booking->fetch();
    if (!$bookingRow || (int)$bookingRow['property_id'] !== $propertyId) {
      throw new InvalidArgumentException('La prenotazione selezionata non appartiene a questo immobile.');
    }
  } else {
    $bookingId = 0;
  }

  $dueAt = cm_normalize_datetime_or_null($input['due_at'] ?? null);
  $details = trim((string)($input['details'] ?? '')) ?: null;
  $assigneeName = trim((string)($input['assignee_name'] ?? '')) ?: null;

  db()->prepare(
    'INSERT INTO cm_tasks (
       property_id, booking_id, created_by_user_id, task_type, status, priority, title, details, assignee_name, due_at
     ) VALUES (
       ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute([
    $propertyId,
    $bookingId > 0 ? $bookingId : null,
    $createdByUserId,
    $taskType,
    'open',
    $priority,
    $title,
    $details,
    $assigneeName,
    $dueAt,
  ]);

  return (int)db()->lastInsertId();
}

function cm_update_task_status(int $taskId, string $status): void {
  if (!array_key_exists($status, cm_task_statuses())) {
    throw new InvalidArgumentException('Stato task non valido.');
  }

  $task = cm_task($taskId);
  if (!$task) {
    throw new InvalidArgumentException('Task non trovata.');
  }

  db()->prepare(
    'UPDATE cm_tasks
     SET status = ?, completed_at = ?
     WHERE id = ?'
  )->execute([
    $status,
    $status === 'done' ? date('Y-m-d H:i:s') : null,
    $taskId,
  ]);
}

function cm_save_operational_update(array $input, ?int $createdByUserId = null): int {
  $propertyId = cm_int_value($input['property_id'] ?? 0);
  $property = cm_property($propertyId);
  if (!$property) {
    throw new InvalidArgumentException('Immobile non valido per l aggiornamento.');
  }

  $updateType = trim((string)($input['update_type'] ?? 'daily'));
  if (!array_key_exists($updateType, cm_operational_update_types())) {
    throw new InvalidArgumentException('Tipo aggiornamento non valido.');
  }

  $title = trim((string)($input['title'] ?? ''));
  if ($title === '') {
    throw new InvalidArgumentException('Titolo aggiornamento obbligatorio.');
  }

  $bookingId = cm_int_value($input['booking_id'] ?? 0);
  if ($bookingId > 0) {
    $booking = db()->prepare('SELECT id, property_id FROM cm_bookings WHERE id = ? LIMIT 1');
    $booking->execute([$bookingId]);
    $bookingRow = $booking->fetch();
    if (!$bookingRow || (int)$bookingRow['property_id'] !== $propertyId) {
      throw new InvalidArgumentException('La prenotazione selezionata non appartiene a questo immobile.');
    }
  } else {
    $bookingId = 0;
  }

  $taskId = cm_int_value($input['task_id'] ?? 0);
  if ($taskId > 0) {
    $task = cm_task($taskId);
    if (!$task || (int)$task['property_id'] !== $propertyId) {
      throw new InvalidArgumentException('La task selezionata non appartiene a questo immobile.');
    }
  } else {
    $taskId = 0;
  }

  $body = trim((string)($input['body'] ?? '')) ?: null;
  $happenedAt = cm_normalize_datetime_or_null($input['happened_at'] ?? null);
  $ownerVisible = !empty($input['owner_visible']) ? 1 : 0;

  db()->prepare(
    'INSERT INTO cm_operational_updates (
       property_id, booking_id, task_id, created_by_user_id, update_type, owner_visible, title, body, happened_at
     ) VALUES (
       ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute([
    $propertyId,
    $bookingId > 0 ? $bookingId : null,
    $taskId > 0 ? $taskId : null,
    $createdByUserId,
    $updateType,
    $ownerVisible,
    $title,
    $body,
    $happenedAt,
  ]);

  return (int)db()->lastInsertId();
}

function cm_property_operational_updates(int $propertyId, int $limit = 20, bool $ownerVisibleOnly = false): array {
  $limit = max(1, $limit);
  $sql = "SELECT u.*,
                 b.summary AS booking_summary,
                 b.guest_name AS booking_guest_name,
                 b.checkin_date AS booking_checkin_date,
                 b.checkout_date AS booking_checkout_date,
                 t.title AS task_title,
                 t.status AS task_status,
                 cu.name AS created_by_name
          FROM cm_operational_updates u
          LEFT JOIN cm_bookings b ON b.id = u.booking_id
          LEFT JOIN cm_tasks t ON t.id = u.task_id
          LEFT JOIN crm_users cu ON cu.id = u.created_by_user_id
          WHERE u.property_id = ?";
  $params = [$propertyId];
  if ($ownerVisibleOnly) {
    $sql .= ' AND u.owner_visible = 1';
  }
  $sql .= " ORDER BY COALESCE(u.happened_at, u.created_at) DESC, u.id DESC
            LIMIT {$limit}";

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_operational_summary(int $clientId = 0, int $recentDays = 7): array {
  $clientId = max(0, $clientId);
  $recentDays = max(1, $recentDays);
  $since = (new DateTimeImmutable('now'))->modify('-' . $recentDays . ' days')->format('Y-m-d H:i:s');

  $sql = "SELECT COUNT(*) AS recent_updates,
                 SUM(CASE WHEN u.update_type = 'issue' THEN 1 ELSE 0 END) AS issue_updates,
                 MAX(COALESCE(u.happened_at, u.created_at)) AS last_update_at
          FROM cm_operational_updates u
          INNER JOIN cm_properties p ON p.id = u.property_id
          WHERE u.owner_visible = 1
            AND COALESCE(u.happened_at, u.created_at) >= ?";
  $params = [$since];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }

  $st = db()->prepare($sql);
  $st->execute($params);
  $row = $st->fetch() ?: [];

  return [
    'recent_updates' => (int)($row['recent_updates'] ?? 0),
    'issue_updates' => (int)($row['issue_updates'] ?? 0),
    'last_update_at' => $row['last_update_at'] ?? null,
    'recent_days' => $recentDays,
  ];
}

function cm_owner_dashboard_operational_updates(int $clientId = 0, int $limit = 8): array {
  $clientId = max(0, $clientId);
  $limit = max(1, $limit);
  $sql = "SELECT u.*,
                 p.name AS property_name,
                 c.name AS client_name,
                 b.summary AS booking_summary,
                 b.guest_name AS booking_guest_name,
                 t.title AS task_title,
                 t.status AS task_status,
                 cu.name AS created_by_name
          FROM cm_operational_updates u
          INNER JOIN cm_properties p ON p.id = u.property_id
          INNER JOIN cm_clients c ON c.id = p.client_id
          LEFT JOIN cm_bookings b ON b.id = u.booking_id
          LEFT JOIN cm_tasks t ON t.id = u.task_id
          LEFT JOIN crm_users cu ON cu.id = u.created_by_user_id
          WHERE u.owner_visible = 1";
  $params = [];
  if ($clientId > 0) {
    $sql .= ' AND p.client_id = ?';
    $params[] = $clientId;
  }
  $sql .= " ORDER BY COALESCE(u.happened_at, u.created_at) DESC, u.id DESC
            LIMIT {$limit}";

  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll() ?: [];
}

function cm_owner_dashboard_timeline(int $clientId = 0, int $limit = 18, int $lookaheadDays = 10): array {
  $clientId = max(0, $clientId);
  $limit = max(1, $limit);
  $lookaheadDays = max(1, $lookaheadDays);
  $timeline = [];

  foreach (cm_owner_dashboard_operational_updates($clientId, max($limit, 8)) as $update) {
    $sortAt = (string)($update['happened_at'] ?: $update['created_at']);
    $timeline[] = [
      'kind' => 'update',
      'sort_at' => $sortAt,
      'title' => (string)$update['title'],
      'meta' => trim(cm_operational_update_type_label((string)$update['update_type']) . ' - ' . (string)$update['property_name']),
      'detail' => (string)($update['body'] ?: ''),
      'status_class' => (string)$update['update_type'] === 'issue' ? 'is-warning' : 'is-draft',
      'status_label' => cm_operational_update_type_label((string)$update['update_type']),
      'href' => cm_base_url('property.php') . '?id=' . (int)$update['property_id'] . '#aggiornamenti',
    ];
  }

  $lookaheadUntil = (new DateTimeImmutable('today'))->modify('+' . $lookaheadDays . ' days');
  foreach (cm_owner_dashboard_upcoming_bookings($clientId, max($limit, 10)) as $booking) {
    $checkinDate = date_create_immutable((string)$booking['checkin_date']);
    $checkoutDate = date_create_immutable((string)$booking['checkout_date']);

    if ($checkinDate && $checkinDate <= $lookaheadUntil) {
      $timeline[] = [
        'kind' => 'checkin',
        'sort_at' => $checkinDate->format('Y-m-d 15:00:00'),
        'title' => 'Check-in: ' . cm_owner_guest_label($booking),
        'meta' => trim((string)$booking['property_name'] . ' - ' . cm_channel_label((string)$booking['source_channel'])),
        'detail' => (int)$booking['nights'] . ' notti - ' . cm_fmt_money((float)$booking['total_amount'], (string)$booking['currency']),
        'status_class' => cm_task_status_badge_class((string)$booking['status']),
        'status_label' => cm_booking_status_label((string)$booking['status']),
        'href' => cm_base_url('property.php') . '?id=' . (int)$booking['property_id'] . '#prenotazioni',
      ];
    }

    if ($checkoutDate && $checkoutDate <= $lookaheadUntil) {
      $timeline[] = [
        'kind' => 'checkout',
        'sort_at' => $checkoutDate->format('Y-m-d 10:00:00'),
        'title' => 'Check-out: ' . cm_owner_guest_label($booking),
        'meta' => trim((string)$booking['property_name'] . ' - ' . cm_channel_label((string)$booking['source_channel'])),
        'detail' => 'Partenza prevista per il soggiorno in corso',
        'status_class' => cm_task_status_badge_class((string)$booking['status']),
        'status_label' => 'Checkout',
        'href' => cm_base_url('property.php') . '?id=' . (int)$booking['property_id'] . '#prenotazioni',
      ];
    }
  }

  foreach (cm_owner_dashboard_tasks($clientId, max($limit, 10)) as $task) {
    $sortAt = (string)($task['due_at'] ?: $task['created_at']);
    $timeline[] = [
      'kind' => 'task',
      'sort_at' => $sortAt,
      'title' => 'Task: ' . (string)$task['title'],
      'meta' => trim((string)$task['property_name'] . ' - ' . cm_task_type_label((string)$task['task_type'])),
      'detail' => 'Priorita ' . mb_strtolower(cm_task_priority_label((string)$task['priority'])) . ' - assegnata a ' . ((string)($task['assignee_name'] ?: 'da assegnare')),
      'status_class' => cm_task_status_badge_class((string)$task['status']),
      'status_label' => cm_task_status_label((string)$task['status']),
      'href' => cm_base_url('property.php') . '?id=' . (int)$task['property_id'] . '#operativita',
    ];
  }

  foreach (cm_owner_dashboard_pending_requests($clientId, max($limit, 6)) as $booking) {
    $timeline[] = [
      'kind' => 'pending_request',
      'sort_at' => (string)$booking['created_at'],
      'title' => 'Nuova richiesta diretta: ' . cm_owner_guest_label($booking),
      'meta' => trim((string)$booking['property_name'] . ' - richiesta diretta'),
      'detail' => 'Soggiorno ' . cm_fmt_date((string)$booking['checkin_date']) . ' - ' . cm_fmt_date((string)$booking['checkout_date']),
      'status_class' => 'is-warning',
      'status_label' => 'In attesa',
      'href' => cm_base_url('property.php') . '?id=' . (int)$booking['property_id'] . '#prenotazioni',
    ];
  }

  usort(
    $timeline,
    static function (array $left, array $right): int {
      return strcmp((string)$right['sort_at'], (string)$left['sort_at']);
    }
  );

  return array_slice($timeline, 0, $limit);
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

function cm_save_property(array $input, array $files = []): int {
  $id = cm_int_value($input['id'] ?? 0);
  $clientId = cm_int_value($input['client_id'] ?? 0);
  $name = trim((string)($input['name'] ?? ''));
  $existing = $id > 0 ? cm_property($id) : null;
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
  $logoImageUrl = trim((string)($input['logo_image_url'] ?? ($existing['logo_image_url'] ?? '')));
  $heroImageUrl = trim((string)($input['hero_image_url'] ?? ($existing['hero_image_url'] ?? '')));
  $galleryImages = cm_lines($input['gallery_images'] ?? ($existing['gallery_images'] ?? null));
  $videoUrls = cm_lines($input['video_urls'] ?? ($existing['video_urls'] ?? null));

  if (!empty($input['remove_logo_image'])) {
    $logoImageUrl = '';
  }

  if (!empty($input['remove_hero_image'])) {
    $heroImageUrl = '';
  }
  if (!empty($input['remove_gallery_images']) && is_array($input['remove_gallery_images'])) {
    $galleryImages = cm_gallery_without_removed($galleryImages, $input['remove_gallery_images']);
  }
  if (!empty($input['remove_video_urls']) && is_array($input['remove_video_urls'])) {
    $videoUrls = cm_gallery_without_removed($videoUrls, $input['remove_video_urls']);
  }

  if ($id > 0 && !empty($files['logo_image_file'])) {
    $logoUpload = cm_uploaded_file_list($files['logo_image_file']);
    if ($logoUpload !== []) {
      $logoImageUrl = cm_store_uploaded_image($logoUpload[0], $id);
    }
  }

  if ($id > 0 && !empty($files['hero_image_file'])) {
    $heroUpload = cm_uploaded_file_list($files['hero_image_file']);
    if ($heroUpload !== []) {
      $heroImageUrl = cm_store_uploaded_image($heroUpload[0], $id);
    }
  }

  if ($id > 0 && !empty($files['gallery_image_files'])) {
    foreach (cm_uploaded_file_list($files['gallery_image_files']) as $upload) {
      $galleryImages[] = cm_store_uploaded_image($upload, $id);
    }
    $galleryImages = array_values(array_unique(array_filter($galleryImages, static fn(string $item): bool => trim($item) !== '')));
  }

  if ($id > 0 && !empty($files['video_files'])) {
    foreach (cm_uploaded_file_list($files['video_files']) as $upload) {
      $videoUrls[] = cm_store_uploaded_video($upload, $id);
    }
    $videoUrls = array_values(array_unique(array_filter($videoUrls, static fn(string $item): bool => trim($item) !== '')));
  }

  $payload = [
    $clientId,
    $name,
    $slug,
    trim((string)($input['description'] ?? '')) ?: null,
    $logoImageUrl !== '' ? $logoImageUrl : null,
    $heroImageUrl !== '' ? $heroImageUrl : null,
    !empty($galleryImages) ? implode("\n", $galleryImages) : null,
    !empty($videoUrls) ? implode("\n", $videoUrls) : null,
    cm_textarea_value($input['public_highlights'] ?? null),
    cm_textarea_value($input['amenities'] ?? null),
    cm_textarea_value($input['arrival_instructions'] ?? null),
    cm_textarea_value($input['checkin_instructions'] ?? null),
    cm_textarea_value($input['checkout_instructions'] ?? null),
    cm_textarea_value($input['house_rules'] ?? null),
    trim((string)($input['contact_name'] ?? '')) ?: null,
    trim((string)($input['contact_phone'] ?? '')) ?: null,
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
       SET client_id = ?, name = ?, slug = ?, description = ?, logo_image_url = ?, hero_image_url = ?, gallery_images = ?,
           video_urls = ?, public_highlights = ?, amenities = ?, arrival_instructions = ?, checkin_instructions = ?,
           checkout_instructions = ?, house_rules = ?, contact_name = ?, contact_phone = ?, address_line1 = ?,
           city = ?, region = ?, country_code = ?, timezone_name = ?, bedrooms = ?, bathrooms = ?, beds = ?,
           max_guests = ?, min_nights = ?, checkin_from = ?, checkin_until = ?, checkout_until = ?,
           base_price = ?, cleaning_fee = ?, currency = ?, booking_notice_hours = ?, direct_booking_enabled = ?,
           published = ?
       WHERE id = ?'
    )->execute($payload);
    return $id;
  }

  $token = cm_random_token(40);
  $payload[] = $token;
  db()->prepare(
    'INSERT INTO cm_properties (
        client_id, name, slug, description, logo_image_url, hero_image_url, gallery_images, video_urls,
        public_highlights, amenities, arrival_instructions, checkin_instructions, checkout_instructions,
        house_rules, contact_name, contact_phone, address_line1, city, region, country_code, timezone_name,
        bedrooms, bathrooms, beds, max_guests, min_nights, checkin_from, checkin_until, checkout_until, base_price, cleaning_fee, currency,
        booking_notice_hours, direct_booking_enabled, published, ical_export_token
     ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
     )'
  )->execute($payload);
  $newId = (int)db()->lastInsertId();

  if ($newId > 0 && (!empty($files['logo_image_file']) || !empty($files['hero_image_file']) || !empty($files['gallery_image_files']) || !empty($files['video_files']))) {
    $updateInput = $input;
    $updateInput['id'] = $newId;
    cm_save_property($updateInput, $files);
  }

  return $newId;
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
