<?php
/**
 * PHASE 7: DashboardController
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Motel.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Review.php';

class DashboardController {
    private $userModel;
    private $motelModel;
    private $bookingModel;
    private $paymentModel;
    private $transactionModel;
    private $reviewModel;

    public function __construct() {
        $this->userModel = new User();
        $this->motelModel = new Motel();
        $this->bookingModel = new Booking();
        $this->paymentModel = new Payment();
        $this->transactionModel = new Transaction();
        $this->reviewModel = new Review();
    }

    public function index() {
        $stats = [
            'total_users' => $this->userModel->count(),
            'total_motels' => $this->motelModel->count(),
            'total_bookings' => $this->bookingModel->count(),
            'total_payments' => $this->paymentModel->count(),
            'total_reviews' => $this->reviewModel->count(),
        ];

        // Get total revenue (sum of fees from released payments)
        $revenue = $this->paymentModel->queryOne(
            "SELECT SUM(fee) as total FROM payments WHERE status = 'released'"
        );
        $stats['total_revenue'] = $revenue['total'] ?? 0;

        // Get recent bookings
        $recentBookings = $this->bookingModel->query(
            "SELECT b.*, u.name as user_name, m.title as motel_title 
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN motels m ON b.motel_id = m.id
             ORDER BY b.created_at DESC LIMIT 10"
        );

        return [
            'stats' => $stats,
            'recentBookings' => $recentBookings
        ];
    }
}
?>
