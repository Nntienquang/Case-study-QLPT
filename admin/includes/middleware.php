<?php
/**
 * PHASE 1: Middleware & Helpers
 */

session_start();

// Check if admin is logged in
function checkAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: /QuanLyPhongTro/admin/public/login.php');
        exit();
    }
}

// Check if user is logged in (any role)
function checkLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: /QuanLyPhongTro/admin/public/login.php');
        exit();
    }
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Get current user
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Logout
function logout() {
    session_destroy();
    header('Location: /QuanLyPhongTro/admin/public/login.php');
    exit();
}
?>
