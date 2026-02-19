<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
  echo json_encode(['ok' => false, 'error' => 'ID non valido']);
  exit;
}

// DB bootstrap
require_once __DIR__ . '/db.php';
$pdo = null;

if (function_exists('db')) {
  $pdo = db();
} elseif (isset($GLOBALS['pdo'])) {
  $pdo = $GLOBALS['pdo'];
} elseif (isset($pdo) && $pdo instanceof PDO) {
  // ok
} else {
  $pdo = $pdo ?? null;
}

if (!$pdo instanceof PDO) {
  echo json_encode(['ok' => false, 'error' => 'Connessione DB non disponibile (db.php)']);
  exit;
}

try {
  // se hai tabelle collegate (note, tasks, ecc.) qui puoi cascatare
  // esempio:
  // $pdo->prepare("DELETE FROM lead_notes WHERE lead_id = :id")->execute([':id'=>$id]);

  $st = $pdo->prepare("DELETE FROM leads WHERE id = :id LIMIT 1");
  $st->execute([':id' => $id]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
