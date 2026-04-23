<?php
/**
 * PHASE 2: Admin Router with CRUD
 * PHASE 4: Auth (Login/Logout)
 */

require_once '../config/database.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

// Make $conn global for all models
global $conn;

// Load all controllers
require_once '../controllers/DashboardController.php';
require_once '../controllers/UserController.php';
require_once '../controllers/CategoryController.php';
require_once '../controllers/UtilityController.php';
require_once '../controllers/MotelController.php';
require_once '../controllers/BookingController.php';
require_once '../controllers/PaymentController.php';
require_once '../controllers/TransactionController.php';
require_once '../controllers/WithdrawController.php';
require_once '../controllers/ReviewController.php';

// Get request params
$controller = $_GET['controller'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? 0;
$page = $_GET['page'] ?? 1;

// Check admin
checkAdmin();

// Route to controller
try {
    $viewPath = null;
    $data = [];
    $currentPage = $controller;

    // Handle POST requests (create, update, delete)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postAction = $_POST['action'] ?? '';
        
        switch ($controller) {
            case 'users':
                $ctrl = new UserController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=users&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=users&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=users&action=index');
                    exit;
                }
                break;

            case 'categories':
                $ctrl = new CategoryController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=categories&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=categories&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=categories&action=index');
                    exit;
                }
                break;

            case 'utilities':
                $ctrl = new UtilityController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=utilities&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=utilities&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=utilities&action=index');
                    exit;
                }
                break;

            case 'motels':
                $ctrl = new MotelController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=motels&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=motels&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=motels&action=index');
                    exit;
                } elseif ($postAction === 'approve') {
                    $ctrl->approve($_POST['id']);
                    setFlash('success', '✅ Duyệt phòng thành công');
                    header('Location: ?controller=motels&action=index&page=' . $page);
                    exit;
                } elseif ($postAction === 'reject') {
                    $ctrl->reject($_POST['id']);
                    setFlash('success', '✅ Từ chối phòng thành công');
                    header('Location: ?controller=motels&action=index&page=' . $page);
                    exit;
                }
                break;

            case 'bookings':
                $ctrl = new BookingController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=bookings&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=bookings&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=bookings&action=index');
                    exit;
                } elseif ($postAction === 'accept') {
                    $ctrl->accept($_POST['id']);
                    setFlash('success', '✅ Chấp nhận đặt phòng thành công');
                    header('Location: ?controller=bookings&action=index&page=' . $page);
                    exit;
                } elseif ($postAction === 'reject') {
                    $ctrl->reject($_POST['id']);
                    setFlash('success', '✅ Từ chối đặt phòng thành công');
                    header('Location: ?controller=bookings&action=index&page=' . $page);
                    exit;
                }
                break;

            case 'payments':
                $ctrl = new PaymentController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=payments&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=payments&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=payments&action=index');
                    exit;
                }
                break;

            case 'transactions':
                $ctrl = new TransactionController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=transactions&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=transactions&action=index');
                    exit;
                }
                break;

            case 'withdraw':
                $ctrl = new WithdrawController();
                if ($postAction === 'create') {
                    $ctrl->store($_POST);
                    header('Location: ?controller=withdraw&action=index');
                    exit;
                } elseif ($postAction === 'update') {
                    $ctrl->update($_POST['id'], $_POST);
                    header('Location: ?controller=withdraw&action=index');
                    exit;
                } elseif ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=withdraw&action=index');
                    exit;
                } elseif ($postAction === 'approve') {
                    $ctrl->approve($_POST['id']);
                    setFlash('success', '✅ Duyệt rút tiền thành công');
                    header('Location: ?controller=withdraw&action=index&page=' . $page);
                    exit;
                } elseif ($postAction === 'reject') {
                    $ctrl->reject($_POST['id']);
                    setFlash('success', '✅ Từ chối rút tiền thành công');
                    header('Location: ?controller=withdraw&action=index&page=' . $page);
                    exit;
                }
                break;

            case 'reviews':
                $ctrl = new ReviewController();
                if ($postAction === 'delete') {
                    $ctrl->delete($_POST['id']);
                    header('Location: ?controller=reviews&action=index');
                    exit;
                }
                break;
        }
    }

    // Handle GET requests
    switch ($controller) {
        case 'dashboard':
            $ctrl = new DashboardController();
            $result = $ctrl->index();
            $viewPath = '../views/dashboard.php';
            $stats = $result['stats'];
            $recentBookings = $result['recentBookings'];
            break;

        case 'users':
            $ctrl = new UserController();
            if ($action === 'create') {
                $viewPath = '../views/users/create.php';
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/users/edit.php';
                    $user = $result['user'];
                } else {
                    header('Location: ?controller=users&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/users.php';
                $users = $result['users'];
                $pagination = $result['pagination'];
            }
            break;

        case 'categories':
            $ctrl = new CategoryController();
            if ($action === 'create') {
                $viewPath = '../views/categories/create.php';
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/categories/edit.php';
                    $category = $result['category'];
                } else {
                    header('Location: ?controller=categories&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/categories.php';
                $categories = $result['categories'];
                $pagination = $result['pagination'];
            }
            break;

        case 'utilities':
            $ctrl = new UtilityController();
            if ($action === 'create') {
                $viewPath = '../views/utilities/create.php';
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/utilities/edit.php';
                    $utility = $result['utility'];
                } else {
                    header('Location: ?controller=utilities&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/utilities.php';
                $utilities = $result['utilities'];
                $pagination = $result['pagination'];
            }
            break;

        case 'motels':
            $ctrl = new MotelController();
            if ($action === 'create') {
                $result = $ctrl->create();
                $viewPath = '../views/motels/create.php';
                $categories = $result['categories'];
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/motels/edit.php';
                    $motel = $result['motel'];
                    $categories = $result['categories'];
                } else {
                    header('Location: ?controller=motels&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/motels.php';
                $motels = $result['motels'];
                $pagination = $result['pagination'];
            }
            break;

        case 'bookings':
            $ctrl = new BookingController();
            if ($action === 'create') {
                $result = $ctrl->create();
                $viewPath = '../views/bookings/create.php';
                $users = $result['users'];
                $motels = $result['motels'];
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/bookings/edit.php';
                    $booking = $result['booking'];
                    $users = $result['users'];
                    $motels = $result['motels'];
                } else {
                    header('Location: ?controller=bookings&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/bookings.php';
                $bookings = $result['bookings'];
                $pagination = $result['pagination'];
            }
            break;

        case 'payments':
            $ctrl = new PaymentController();
            if ($action === 'create') {
                $result = $ctrl->create();
                $viewPath = '../views/payments/create.php';
                $bookings = $result['bookings'];
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/payments/edit.php';
                    $payment = $result['payment'];
                    $bookings = $result['bookings'];
                } else {
                    header('Location: ?controller=payments&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/payments.php';
                $payments = $result['payments'];
                $pagination = $result['pagination'];
            }
            break;

        case 'transactions':
            $ctrl = new TransactionController();
            if ($action === 'create') {
                $result = $ctrl->create();
                $viewPath = '../views/transactions/create.php';
                $users = $result['users'];
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/transactions.php';
                $transactions = $result['transactions'];
                $pagination = $result['pagination'];
            }
            break;

        case 'withdraw':
            $ctrl = new WithdrawController();
            if ($action === 'create') {
                $result = $ctrl->create();
                $viewPath = '../views/withdraw/create.php';
                $users = $result['users'];
            } elseif ($action === 'edit' && $id) {
                $result = $ctrl->edit($id);
                if ($result) {
                    $viewPath = '../views/withdraw/edit.php';
                    $withdraw = $result['withdraw'];
                    $users = $result['users'];
                } else {
                    header('Location: ?controller=withdraw&action=index');
                    exit;
                }
            } else {
                $result = $ctrl->index($page);
                $viewPath = '../views/withdraw.php';
                $withdraws = $result['withdraws'];
                $pagination = $result['pagination'];
            }
            break;

        case 'reviews':
            $ctrl = new ReviewController();
            $result = $ctrl->index($page);
            $viewPath = '../views/reviews.php';
            $reviews = $result['reviews'];
            $pagination = $result['pagination'];
            break;

        default:
            die('❌ Controller không tồn tại');
    }

    // Render layout with view
    include '../views/layout.php';

} catch (Exception $e) {
    die('❌ Lỗi: ' . $e->getMessage());
}
?>
