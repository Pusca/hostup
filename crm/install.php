<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  echo "CLI only.\n";
  exit(1);
}

$options = getopt('', [
  'admin-host::',
  'admin-port::',
  'admin-user::',
  'admin-pass::',
  'app-db::',
  'app-user::',
  'app-pass::',
  'seed-admin-email::',
  'seed-admin-name::',
  'seed-admin-pass::',
]);

$adminHost = (string)($options['admin-host'] ?? getenv('HOSTUP_INSTALL_ADMIN_HOST') ?: '127.0.0.1');
$adminPort = (int)($options['admin-port'] ?? getenv('HOSTUP_INSTALL_ADMIN_PORT') ?: 3306);
$adminUser = (string)($options['admin-user'] ?? getenv('HOSTUP_INSTALL_ADMIN_USER') ?: 'root');
$adminPass = (string)($options['admin-pass'] ?? getenv('HOSTUP_INSTALL_ADMIN_PASS') ?: '');

$appDb = (string)($options['app-db'] ?? getenv('HOSTUP_DB_NAME') ?: 'hostup_crm');
$appUser = (string)($options['app-user'] ?? getenv('HOSTUP_DB_USER') ?: 'hostup_crm');
$appPass = (string)($options['app-pass'] ?? getenv('HOSTUP_DB_PASS') ?: 'kzUatx7pJNYddsve6JCm');

$seedAdminEmail = trim((string)($options['seed-admin-email'] ?? getenv('HOSTUP_SEED_ADMIN_EMAIL') ?: ''));
$seedAdminName = trim((string)($options['seed-admin-name'] ?? getenv('HOSTUP_SEED_ADMIN_NAME') ?: ''));
$seedAdminPass = (string)($options['seed-admin-pass'] ?? getenv('HOSTUP_SEED_ADMIN_PASS') ?: '');

function runSqlFile(PDO $pdo, string $file): void {
  $sql = file_get_contents($file);
  if ($sql === false) {
    throw new RuntimeException('Impossibile leggere ' . $file);
  }

  $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
  foreach ($parts as $part) {
    $statement = trim($part);
    if ($statement === '') {
      continue;
    }
    $pdo->exec($statement);
  }
}

echo "Connecting as admin {$adminUser}@{$adminHost}:{$adminPort}\n";
$adminPdo = new PDO(
  'mysql:host=' . $adminHost . ';port=' . $adminPort . ';charset=utf8mb4',
  $adminUser,
  $adminPass,
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);

echo "Creating database {$appDb}\n";
$adminPdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $appDb) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "Creating application user {$appUser}\n";
$adminPdo->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY " . $adminPdo->quote($appPass));
$adminPdo->exec("GRANT ALL PRIVILEGES ON `" . str_replace('`', '``', $appDb) . "`.* TO '{$appUser}'@'localhost'");
$adminPdo->exec('FLUSH PRIVILEGES');

$appPdo = new PDO(
  'mysql:host=' . $adminHost . ';port=' . $adminPort . ';dbname=' . $appDb . ';charset=utf8mb4',
  $appUser,
  $appPass,
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);

echo "Applying CRM schema\n";
runSqlFile($appPdo, __DIR__ . '/sql/crm_schema.sql');
echo "Applying Channel Manager schema\n";
runSqlFile($appPdo, __DIR__ . '/channel/sql/channel_manager.sql');

if ($seedAdminEmail !== '' && $seedAdminName !== '' && $seedAdminPass !== '') {
  echo "Seeding admin user {$seedAdminEmail}\n";
  $hash = password_hash($seedAdminPass, PASSWORD_DEFAULT);
  $stmt = $appPdo->prepare(
    'INSERT INTO crm_users (name, email, password_hash, role)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       name = VALUES(name),
       password_hash = VALUES(password_hash),
       role = VALUES(role)'
  );
  $stmt->execute([$seedAdminName, $seedAdminEmail, $hash, 'admin']);
}

echo "Install completed.\n";
