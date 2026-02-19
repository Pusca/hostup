<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_name(CRM_SESSION_NAME);
session_start();

function require_login(): array {
  if (empty($_SESSION['user'])) {
    header('Location: ' . CRM_BASE_URL . '/login.php');
    exit;
  }
  return $_SESSION['user'];
}
