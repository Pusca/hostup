<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

cm_install_schema();

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
  http_response_code(400);
  echo 'Token mancante.';
  exit;
}

$property = cm_property_by_ical_token($token);
if (!$property) {
  http_response_code(404);
  echo 'Feed non trovato.';
  exit;
}

$filename = cm_slugify((string)$property['name']) . '.ics';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
echo cm_generate_ical($property);
