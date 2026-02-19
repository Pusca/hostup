<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_login();

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
  json_out(400, ['ok' => false, 'error' => 'ID non valido']);
}

try {
  db()->prepare("DELETE FROM crm_lead_notes WHERE lead_id=?")->execute([$id]);
  db()->prepare("DELETE FROM crm_leads WHERE id=?")->execute([$id]);
  json_out(200, ['ok' => true]);
} catch (Throwable $e) {
  json_out(500, ['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
