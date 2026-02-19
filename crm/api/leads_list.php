<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));
$stage = trim((string)($_GET['stage'] ?? ''));

$queries = [
  "SELECT id, created_at, name, email, phone, units AS units, stage FROM crm_leads WHERE 1=1",
  "SELECT id, created_at, name, email, phone, unit AS units, stage FROM crm_leads WHERE 1=1",
  "SELECT id, created_at, name, email, phone, '' AS units, stage FROM crm_leads WHERE 1=1",
];

$rows = null;
$lastError = 'Leads list error';

foreach ($queries as $baseSql) {
  $sql = $baseSql;
  $args = [];

  if ($stage !== '') {
    $sql .= " AND stage=?";
    $args[] = $stage;
  }
  if ($q !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $args[] = "%$q%";
    $args[] = "%$q%";
    $args[] = "%$q%";
  }
  $sql .= " ORDER BY created_at DESC LIMIT 300";

  try {
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll();
    break;
  } catch (Throwable $e) {
    $lastError = $e->getMessage();
  }
}

if ($rows === null) {
  json_out(500, ['ok' => false, 'error' => 'DB error: ' . $lastError]);
}

json_out(200, ['ok' => true, 'leads' => $rows, 'items' => $rows]);
