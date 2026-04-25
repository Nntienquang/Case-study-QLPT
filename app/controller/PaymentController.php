<?php
/**
 * Payment Controller
 */

class PaymentController {
    private $payment;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->payment = new Payment($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all payments
     */
    public function listPayments() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $payments = $this->payment->getAll($page, ITEMS_PER_PAGE, $status);
        $total = $this->payment->getTotal($status);
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        
        return [
            'payments' => $payments,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'status' => $status
        ];
    }
    
    /**
     * View payment details
     */
    public function viewPayment() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $payment = $this->payment->getById($id);
        
        if (!$payment) {
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }
        
        return ['payment' => $payment];
    }
    
    /**
     * Update payment status
     */
    public function updateStatus() {
        if (!isset($_GET['id']) || !isset($_GET['status'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $status = $_GET['status'];
        $payment_old = $this->payment->getById($id);
        
        // Validate status
        $valid_status = ['pending', 'held', 'released', 'refunded'];
        if (!in_array($status, $valid_status)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ';
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }
        
        if ($this->payment->updateStatus($id, $status)) {
            // Log activity
            if ($this->activityLog && $payment_old) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'update_payment_status',
                    'payment',
                    $id,
                    ['old' => $payment_old['status'], 'new' => $status],
                    "Cập nhật thanh toán từ {$payment_old['status']} thành {$status}"
                );
            }
            
            // Nếu status = 'released', tính commission 1% cho admin
            if ($status === 'released') {
                $payment = $this->payment->getById($id);
                
                if ($payment && $payment['booking_id']) {
                    // Lấy thông tin booking để tính commission
                    $booking_sql = "SELECT * FROM bookings WHERE id = " . (int)$payment['booking_id'];
                    $booking = $this->db->getRow($booking_sql);
                    
                    if ($booking) {
                        // Lấy admin id (giả sử admin id = 1, hoặc lấy từ session)
                        $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
                        
                        // Tính commission (1% của deposit_amount)
                        $commission = ceil($booking['deposit_amount'] * 0.01);
                        
                        // Thêm transaction record cho admin (commission)
                        $conn = $this->db->getConnection();
                        $admin_id_esc = $conn->real_escape_string($admin_id);
                        $booking_id_esc = $conn->real_escape_string($payment['booking_id']);
                        $commission_esc = $conn->real_escape_string($commission);
                        
                        $trans_query = "INSERT INTO transactions 
                                       (to_user, booking_id, amount, type, created_at) 
                                       VALUES 
                                       ({$admin_id_esc}, {$booking_id_esc}, {$commission_esc}, 'commission', NOW())";
                        
                        $this->db->query($trans_query);
                    }
                }
            }
            
            $_SESSION['success'] = 'Cập nhật trạng thái thanh toán thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }
}

?>
