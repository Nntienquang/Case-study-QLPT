<?php
/**
 * Dashboard Controller
 */

class DashboardController {
    private $motel;
    private $user;
    private $booking;
    private $payment;
    private $review;
    private $revenue;
    private $db;
    
    public function __construct($db) {
        $this->motel = new Motel($db);
        $this->user = new User($db);
        $this->booking = new Booking($db);
        $this->payment = new Payment($db);
        $this->review = new Review($db);
        $this->revenue = new AdminRevenue($db);
        $this->db = $db;
    }
    
    /**
     * Show dashboard
     */
    public function index() {
        $data = [];
        
        // Motel stats
        $data['motel_stats'] = $this->motel->getStats();
        
        // User stats
        $data['user_stats'] = $this->user->getStats();
        
        // Booking stats
        $data['booking_stats'] = $this->booking->getStats();
        
        // Payment stats
        $data['payment_stats'] = $this->payment->getStats();
        
        // Total reviews
        $data['total_reviews'] = $this->review->getTotal();
        
        // Admin revenue stats
        $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        $data['revenue_stats'] = $this->revenue->getStats($admin_id);
        
        // Recent motels
        $data['recent_motels'] = $this->motel->getAll(1, 5, 'pending');
        
        // Recent bookings
        $data['recent_bookings'] = $this->booking->getAll(1, 5);
        
        return $data;
    }
}

?>
