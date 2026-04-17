CREATE TABLE IF NOT EXISTS cm_clients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  company_name VARCHAR(160) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  vat_number VARCHAR(64) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_properties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  logo_image_url VARCHAR(255) DEFAULT NULL,
  hero_image_url VARCHAR(255) DEFAULT NULL,
  gallery_images TEXT DEFAULT NULL,
  video_urls TEXT DEFAULT NULL,
  public_highlights TEXT DEFAULT NULL,
  amenities TEXT DEFAULT NULL,
  arrival_instructions TEXT DEFAULT NULL,
  checkin_instructions TEXT DEFAULT NULL,
  checkout_instructions TEXT DEFAULT NULL,
  house_rules TEXT DEFAULT NULL,
  contact_name VARCHAR(160) DEFAULT NULL,
  contact_phone VARCHAR(60) DEFAULT NULL,
  address_line1 VARCHAR(190) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  region VARCHAR(120) DEFAULT NULL,
  country_code CHAR(2) NOT NULL DEFAULT 'IT',
  timezone_name VARCHAR(80) NOT NULL DEFAULT 'Europe/Rome',
  bedrooms SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bathrooms DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  beds SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  max_guests SMALLINT UNSIGNED NOT NULL DEFAULT 2,
  min_nights SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  checkin_from TIME NOT NULL DEFAULT '15:00:00',
  checkin_until TIME NOT NULL DEFAULT '20:00:00',
  checkout_until TIME NOT NULL DEFAULT '10:00:00',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cleaning_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  booking_notice_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,
  direct_booking_enabled TINYINT(1) NOT NULL DEFAULT 1,
  published TINYINT(1) NOT NULL DEFAULT 0,
  ical_export_token CHAR(40) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_cm_properties_slug (slug),
  UNIQUE KEY uniq_cm_properties_ical_token (ical_export_token),
  KEY idx_cm_properties_client (client_id),
  CONSTRAINT fk_cm_properties_client
    FOREIGN KEY (client_id) REFERENCES cm_clients(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_channel_connections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  channel_code VARCHAR(30) NOT NULL,
  sync_mode VARCHAR(20) NOT NULL DEFAULT 'ical',
  external_listing_id VARCHAR(120) DEFAULT NULL,
  import_url TEXT DEFAULT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  last_sync_at DATETIME DEFAULT NULL,
  last_sync_status VARCHAR(30) DEFAULT NULL,
  last_sync_message VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_cm_channel_property (property_id, channel_code),
  KEY idx_cm_channel_property (property_id),
  CONSTRAINT fk_cm_channel_property
    FOREIGN KEY (property_id) REFERENCES cm_properties(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id INT UNSIGNED NOT NULL,
  connection_id INT UNSIGNED DEFAULT NULL,
  source_channel VARCHAR(30) NOT NULL DEFAULT 'direct',
  external_id VARCHAR(190) DEFAULT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  booking_type VARCHAR(20) NOT NULL DEFAULT 'reservation',
  guest_name VARCHAR(190) DEFAULT NULL,
  guest_email VARCHAR(190) DEFAULT NULL,
  guest_phone VARCHAR(60) DEFAULT NULL,
  guest_notes TEXT DEFAULT NULL,
  summary VARCHAR(190) DEFAULT NULL,
  adults SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  children SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  checkin_date DATE NOT NULL,
  checkout_date DATE NOT NULL,
  nights SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  imported_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_cm_booking_external (property_id, source_channel, external_id),
  KEY idx_cm_booking_dates (property_id, checkin_date, checkout_date),
  KEY idx_cm_booking_status (status),
  CONSTRAINT fk_cm_booking_property
    FOREIGN KEY (property_id) REFERENCES cm_properties(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_cm_booking_connection
    FOREIGN KEY (connection_id) REFERENCES cm_channel_connections(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_tasks (
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
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_operational_updates (
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
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cm_sync_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  connection_id INT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  imported_events INT UNSIGNED NOT NULL DEFAULT 0,
  message VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cm_sync_logs_connection (connection_id),
  CONSTRAINT fk_cm_sync_logs_connection
    FOREIGN KEY (connection_id) REFERENCES cm_channel_connections(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
