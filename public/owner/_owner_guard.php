<?php
/** @var mysqli $conn */
require_once __DIR__ . '/../../core/OwnerStatusMiddleware.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$ownerGuardDb = $db ?? new Database($conn);
$ownerGuard = new OwnerStatusMiddleware($ownerGuardDb);
$ownerGuardInfo = $ownerGuard->getOwnerApprovalInfo((int)$_SESSION['user_id']);
$ownerVerificationStatus = (string)($ownerGuardInfo['owner_verification_status'] ?? 'pending_verification');

if (in_array((string)($ownerGuardInfo['status'] ?? ''), ['blocked', 'locked', 'banned'], true)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?blocked=1');
    exit;
}

if (!($allowUnverifiedOwner ?? false)) {
    $ownerGuard->checkOwnerAccess((int)$_SESSION['user_id'], 'profile.php?verify=1');
}
