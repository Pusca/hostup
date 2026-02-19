<?php
declare(strict_types=1);

const CRM_DB_HOST = 'localhost';
const CRM_DB_NAME = 'hostup_crm';
const CRM_DB_USER = 'hostup_crm';
const CRM_DB_PASS = 'kzUatx7pJNYddsve6JCm';

const CRM_SESSION_NAME = 'hostup_crm_session';
const CRM_BASE_URL = '/crm';

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host=' . CRM_DB_HOST . ';dbname=' . CRM_DB_NAME . ';charset=utf8mb4';
  $pdo = new PDO($dsn, CRM_DB_USER, CRM_DB_PASS, [
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
