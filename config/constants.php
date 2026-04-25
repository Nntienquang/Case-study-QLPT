<?php
/**
 * Constants Configuration
 */

define('BASE_URL', 'http://localhost/QuanLyPhongTro/public/');
define('ADMIN_URL', BASE_URL . 'admin/');

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', BASE_URL . 'public/uploads/');

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
define('BOOKING_PAID', 'paid');
define('BOOKING_ACCEPTED', 'accepted');
define('BOOKING_COMPLETED', 'completed');
define('BOOKING_REJECTED', 'rejected');
define('BOOKING_CANCELLED', 'cancelled');

// Payment status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_HELD', 'held');
define('PAYMENT_RELEASED', 'released');
define('PAYMENT_REFUNDED', 'refunded');

?>
