<?php
require_once __DIR__ . '/config.php';
session_name(CRM_SESSION_NAME);
session_start();
session_destroy();
header('Location: ' . CRM_BASE_URL . '/login.php');
