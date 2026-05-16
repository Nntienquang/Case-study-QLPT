<?php
require_once __DIR__ . '/../admin_init.php';

// Check login
if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Logout
$auth->logout();

header('Location: ' . ADMIN_URL . 'login.php');
exit;

?>
