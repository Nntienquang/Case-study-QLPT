<?php
/**
 * Admin Initialization
 */

// Bắt đầu session
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Include config
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Include Database class
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Csrf.php';

// Include Models
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Motel.php';
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../core/Booking.php';
require_once __DIR__ . '/../core/Payment.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/District.php';
require_once __DIR__ . '/../core/Utility.php';
require_once __DIR__ . '/../core/Review.php';
require_once __DIR__ . '/../core/AdminRevenue.php';
require_once __DIR__ . '/../core/Report.php';
require_once __DIR__ . '/../core/ActivityLog.php';
require_once __DIR__ . '/../core/EmailNotification.php';
require_once __DIR__ . '/../core/OwnerStatusMiddleware.php';

// Include Controllers
require_once __DIR__ . '/../app/controller/DashboardController.php';
require_once __DIR__ . '/../app/controller/MotelController.php';
require_once __DIR__ . '/../app/controller/UserController.php';
require_once __DIR__ . '/../app/controller/BookingController.php';
require_once __DIR__ . '/../app/controller/PaymentController.php';
require_once __DIR__ . '/../app/controller/CategoryController.php';
require_once __DIR__ . '/../app/controller/DistrictController.php';
require_once __DIR__ . '/../app/controller/UtilityController.php';
require_once __DIR__ . '/../app/controller/ReviewController.php';
require_once __DIR__ . '/../app/controller/AdminRevenueController.php';
require_once __DIR__ . '/../app/controller/ReportController.php';
require_once __DIR__ . '/../app/controller/UserApprovalController.php';
require_once __DIR__ . '/../app/controller/ActivityLogController.php';

// Initialize database
$db = new Database($conn);

// Initialize auth
$auth = new Auth($db);

// Check login
$is_logged_in = $auth->isLoggedIn();
$auth->checkTimeout();

if ($is_logged_in && ($_SESSION['status'] ?? '') === 'blocked') {
    $auth->logout();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if ($is_logged_in && (int)($_SESSION['force_password_change'] ?? 0) === 1) {
    header('Location: ' . BASE_URL . 'change-password.php');
    exit;
}

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : '';

?>
