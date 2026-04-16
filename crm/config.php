<?php
declare(strict_types=1);

function env_or_default(string $key, string $default): string {
  $value = getenv($key);
  return ($value !== false && $value !== '') ? $value : $default;
}

const CRM_DB_HOST = 'localhost';
const CRM_DB_NAME = 'hostup_crm';
const CRM_DB_USER = 'hostup_crm';
const CRM_DB_PASS = 'kzUatx7pJNYddsve6JCm';
const CRM_DB_PORT = 3306;

const CRM_SESSION_NAME = 'hostup_crm_session';
const CRM_BASE_URL = '/crm';

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $host = env_or_default('HOSTUP_DB_HOST', CRM_DB_HOST);
  $name = env_or_default('HOSTUP_DB_NAME', CRM_DB_NAME);
  $user = env_or_default('HOSTUP_DB_USER', CRM_DB_USER);
  $pass = env_or_default('HOSTUP_DB_PASS', CRM_DB_PASS);
  $port = (int)env_or_default('HOSTUP_DB_PORT', (string)CRM_DB_PORT);
  $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function json_out(int $code, array $payload): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

const CRM_INVITE_CODE = 'Host01'; // chiave segreta per registrarsi
