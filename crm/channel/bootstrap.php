<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/lib.php';

$u = require_login();
cm_install_schema();
