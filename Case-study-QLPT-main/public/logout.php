<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

session_start();

// Initialize
/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

// Logout
$auth->logout();

// Redirect to login
header("Location: login.php");
exit();
?>
