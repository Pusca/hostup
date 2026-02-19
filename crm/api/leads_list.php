<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
require_login();

function lead_columns(PDO $pdo): array {
  $st = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'crm_leads'");
  $st->execute();
  $cols = $st->fetchAll(PDO::FETCH_COLUMN);
  $map = [];
  foreach ($cols as $c) $map[(string)$c] = true;
  return $map;
}

$q = trim((string)($_GET['q'] ?? ''));
$stage = trim((string)($_GET['stage'] ?? ''));

$cols = lead_columns(db());
$unitsExpr = isset($cols['units']) ? 'units' : (isset($cols['unit']) ? 'unit' : "''");
$stageExpr = isset($cols['stage']) ? 'stage' : "'new'";

$sql = "SELECT id, created_at, name, email, phone, {$unitsExpr} AS units, {$stageExpr} AS stage FROM crm_leads WHERE 1=1";
$args = [];

if ($stage !== '') { $sql .= " AND stage=?"; $args[] = $stage; }
if ($q !== '') {
  $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
  $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
}
$sql .= " ORDER BY created_at DESC LIMIT 300";

$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();
json_out(200, ['ok'=>true, 'leads'=>$rows, 'items'=>$rows]);
