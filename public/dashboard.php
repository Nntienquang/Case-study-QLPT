<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';

if ($role === 'admin') {
    header('Location: ./admin/index.php');
    exit;
}

if ($role === 'owner') {
    header('Location: ./owner/dashboard.php');
    exit;
}

header('Location: ./user/dashboard.php');
exit;
