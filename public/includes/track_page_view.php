<?php

if (PHP_SAPI === 'cli') {
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
$requestPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '');
$lowerPath = strtolower($requestPath);
if (
    $requestPath === ''
    || str_contains($lowerPath, '/admin/')
    || str_contains($lowerPath, '/api/')
    || str_contains($lowerPath, '/ajax/')
    || preg_match('/\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|map)$/i', $requestPath)
) {
    return;
}

$pageName = strtolower((string)pathinfo($requestPath, PATHINFO_BASENAME));
$pageTypes = [
    'index.php' => 'home',
    'phongtro.php' => 'room_search',
    'blog.php' => 'blog',
    'blog_detail.php' => 'blog_article',
    'trogiup.php' => 'help',
];
if (!isset($pageTypes[$pageName])) {
    return;
}

$host = substr((string)($_SERVER['HTTP_HOST'] ?? 'localhost'), 0, 255);
$pageUrl = substr($requestUri, 0, 500);
$dedupeKey = hash('sha256', $host . '|' . $pageUrl);
$now = time();
$recentViews = $_SESSION['_qlpt_page_views'] ?? [];
if (($recentViews[$dedupeKey] ?? 0) > ($now - 600)) {
    return;
}

require_once __DIR__ . '/../../config/database.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

$tableName = 'page_views';
$tableStmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
if (!$tableStmt) {
    return;
}
$tableStmt->bind_param('s', $tableName);
$tableStmt->execute();
$hasTable = (bool)$tableStmt->get_result()->fetch_row();
$tableStmt->close();
if (!$hasTable) {
    return;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$role = isset($_SESSION['user_role']) || isset($_SESSION['role'])
    ? substr((string)($_SESSION['user_role'] ?? $_SESSION['role']), 0, 30)
    : null;
$ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
$referrer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);
$pageType = $pageTypes[$pageName];

$insertStmt = $conn->prepare(
    'INSERT INTO page_views (user_id, role, host, page_url, page_type, ip_address, user_agent, referrer, viewed_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
);
if (!$insertStmt) {
    return;
}
$insertStmt->bind_param('isssssss', $userId, $role, $host, $pageUrl, $pageType, $ipAddress, $userAgent, $referrer);
if ($insertStmt->execute()) {
    $recentViews[$dedupeKey] = $now;
    $_SESSION['_qlpt_page_views'] = array_filter(
        $recentViews,
        static fn ($viewedAt): bool => (int)$viewedAt > ($now - 3600)
    );
}
$insertStmt->close();
