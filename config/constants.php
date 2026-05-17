<?php
/**
 * Constants Configuration
 */

if (!defined('BASE_URL')) {
    $defaultBase = 'http://localhost/Case-study-QLPT/public/';
    $baseUrl = $defaultBase;
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $publicRoot = realpath(__DIR__ . '/../public');
        if ($docRoot !== false && $publicRoot !== false) {
            $docRoot = str_replace('\\', '/', $docRoot);
            $publicRoot = str_replace('\\', '/', $publicRoot);
            if (str_starts_with($publicRoot, rtrim($docRoot, '/'))) {
                $rel = substr($publicRoot, strlen(rtrim($docRoot, '/')));
                $rel = '/' . ltrim(str_replace('\\', '/', $rel), '/');
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim($rel, '/') . '/';
            }
        }
    }
    define('BASE_URL', $baseUrl);
}
define('ADMIN_URL', BASE_URL . 'admin/');

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Session timeout (in minutes)
define('SESSION_TIMEOUT', 30);

// Pagination
define('ITEMS_PER_PAGE', 10);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_OWNER', 'owner');
define('ROLE_USER', 'user');

// Motel status
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_HIDDEN', 'hidden');

// Booking status
define('BOOKING_PENDING', 'pending');
define('BOOKING_WAITING_PAYMENT', 'waiting_payment');
define('BOOKING_PAID', 'paid');
define('BOOKING_CONFIRMED', 'confirmed');
define('BOOKING_ACCEPTED', 'accepted');
define('BOOKING_COMPLETED', 'completed');
define('BOOKING_REJECTED', 'rejected');
define('BOOKING_CANCELLED', 'cancelled');

// Payment status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PROCESSING', 'processing');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_CANCELLED', 'cancelled');
define('PAYMENT_REFUNDED', 'refunded');

?>
