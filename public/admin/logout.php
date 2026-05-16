<?php
require_once __DIR__ . '/../../config/constants.php';

session_start();
session_unset();
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ' . BASE_URL . 'login.php');
exit;
