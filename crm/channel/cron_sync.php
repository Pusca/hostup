<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

cm_install_schema();
$results = cm_sync_all_ical_connections();

foreach ($results as $result) {
  $status = strtoupper((string)$result['status']);
  $message = (string)($result['message'] ?? '');
  echo '[' . $status . '] connection #' . (int)$result['connection_id'] . ' - ' . $message . PHP_EOL;
}
