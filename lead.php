<?php
declare(strict_types=1);

require_once __DIR__ . '/crm/config.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Allow-Origin: *');
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  json_out(405, ['ok' => false, 'error' => 'Metodo non consentito']);
}

function clean(string $v): string {
  $v = trim($v);
  return str_replace(["\r", "\n"], ' ', $v);
}

function request_data(): array {
  $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
  $raw = file_get_contents('php://input');
  if (stripos($contentType, 'application/json') !== false) {
    $json = json_decode((string)$raw, true);
    return is_array($json) ? $json : [];
  }
  return $_POST ?: [];
}

function table_exists(PDO $pdo, string $table): bool {
  $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $st->execute([$table]);
  return ((int)$st->fetchColumn()) > 0;
}

function table_columns(PDO $pdo, string $table): array {
  $st = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?");
  $st->execute([$table]);
  $cols = $st->fetchAll(PDO::FETCH_COLUMN);
  $map = [];
  foreach ($cols as $c) {
    $map[(string)$c] = true;
  }
  return $map;
}

function insert_dynamic(PDO $pdo, string $table, array $values): int {
  $cols = array_keys($values);
  $placeholders = implode(',', array_fill(0, count($cols), '?'));
  $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES ({$placeholders})";
  $st = $pdo->prepare($sql);
  $st->execute(array_values($values));
  return (int)$pdo->lastInsertId();
}

$data = request_data();
$name  = clean((string)($data['name'] ?? ''));
$email = clean((string)($data['email'] ?? ''));
$phone = clean((string)($data['phone'] ?? ''));
$units = clean((string)($data['units'] ?? ''));
$msg   = trim((string)($data['msg'] ?? ''));

if ($name === '' || mb_strlen($name) < 2) {
  json_out(422, ['ok' => false, 'error' => 'Nome non valido']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(422, ['ok' => false, 'error' => 'Email non valida']);
}
if ($units === '') {
  json_out(422, ['ok' => false, 'error' => 'Seleziona le unita']);
}

$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

try {
  $pdo = db();

  if (!table_exists($pdo, 'crm_leads')) {
    json_out(500, ['ok' => false, 'error' => 'Tabella crm_leads non trovata']);
  }

  $leadCols = table_columns($pdo, 'crm_leads');
  $payload = [];

  if (isset($leadCols['source'])) $payload['source'] = 'hostup.it';
  if (isset($leadCols['name'])) $payload['name'] = $name;
  if (isset($leadCols['email'])) $payload['email'] = $email;
  if (isset($leadCols['phone'])) $payload['phone'] = ($phone !== '' ? $phone : null);
  if (isset($leadCols['units'])) $payload['units'] = $units;
  if (isset($leadCols['unit'])) $payload['unit'] = $units;
  if (isset($leadCols['msg'])) $payload['msg'] = ($msg !== '' ? $msg : null);
  if (isset($leadCols['message'])) $payload['message'] = ($msg !== '' ? $msg : null);
  if (isset($leadCols['ip'])) $payload['ip'] = ($ip !== '' ? $ip : null);
  if (isset($leadCols['user_agent'])) $payload['user_agent'] = ($ua !== '' ? $ua : null);
  if (isset($leadCols['stage'])) $payload['stage'] = 'new';

  if (empty($payload['name']) || empty($payload['email'])) {
    json_out(500, ['ok' => false, 'error' => 'Schema crm_leads incompleto (name/email mancanti)']);
  }

  $pdo->beginTransaction();
  $leadId = insert_dynamic($pdo, 'crm_leads', $payload);

  if (table_exists($pdo, 'crm_lead_notes')) {
    $notesCols = table_columns($pdo, 'crm_lead_notes');
    $notePayload = [];
    if (isset($notesCols['lead_id'])) $notePayload['lead_id'] = $leadId;
    if (isset($notesCols['user_id'])) $notePayload['user_id'] = null;
    if (isset($notesCols['note'])) $notePayload['note'] = 'Lead acquisito dalla landing HostUp';
    if (isset($notesCols['kind'])) $notePayload['kind'] = 'system';
    if (isset($notePayload['lead_id']) && isset($notePayload['note'])) {
      insert_dynamic($pdo, 'crm_lead_notes', $notePayload);
    }
  }

  $pdo->commit();

  $to = 'info@smartera.it';
  $fromEmail = 'info@smartera.it';
  $subject = 'Nuovo lead HostUp (CRM) #' . $leadId;
  $body  = "Nuovo lead salvato nel CRM.\n\n";
  $body .= "ID: $leadId\nNome: $name\nEmail: $email\nTelefono: " . ($phone ?: 'n/d') . "\nUnita: $units\n";
  $body .= "Note: " . ($msg ?: 'n/d') . "\nIP: " . ($ip ?: 'n/d') . "\n";
  $headers = "From: HostUp <{$fromEmail}>\r\n";
  $headers .= "Reply-To: {$name} <{$email}>\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  @mail($to, $subject, $body, $headers, "-f{$fromEmail}");

  json_out(200, ['ok' => true, 'lead_id' => $leadId]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  json_out(500, ['ok' => false, 'error' => 'Errore salvataggio lead: ' . $e->getMessage()]);
}
