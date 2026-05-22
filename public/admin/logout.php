<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ActivityLog.php';

session_start();
if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['user_id'])) {
    $logoutLog = new ActivityLog(new Database($conn));
    $logoutLog->log((int)$_SESSION['user_id'], 'logout_admin', 'user', (int)$_SESSION['user_id'], [], 'Admin đăng xuất');
}
session_unset();
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ' . BASE_URL . 'login.php');
exit;
