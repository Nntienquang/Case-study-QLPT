<?php
/**
 * Booking Controller
 */

class BookingController {
    private $booking;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->booking = new Booking($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all bookings
     */
    public function listBookings() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $bookings = $this->booking->getAll($page, ITEMS_PER_PAGE, $status);
        $total = $this->booking->getTotal($status);
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        
        return [
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'status' => $status
        ];
    }
    
    /**
     * View booking details
     */
    public function viewBooking() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $booking = $this->booking->getById($id);
        
        if (!$booking) {
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }
        
        return ['booking' => $booking];
    }
    
    /**
     * Update booking status
     */
    public function updateStatus() {
        if (!isset($_GET['id']) || !isset($_GET['status'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $status = $_GET['status'];
        $booking_old = $this->booking->getById($id);
        
        // Validate status
        $valid_status = ['pending', 'paid', 'accepted', 'completed', 'rejected', 'cancelled'];
        if (!in_array($status, $valid_status)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ';
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }
        
        if ($this->booking->updateStatus($id, $status)) {
            if ($this->activityLog && $booking_old) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'update_booking_status',
                    'booking',
                    $id,
                    ['old' => $booking_old['status'], 'new' => $status],
                    "Cập nhật đơn đặt phòng từ {$booking_old['status']} thành {$status}"
                );
            }
            $_SESSION['success'] = 'Cập nhật trạng thái thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'bookings.php');
        exit;
    }
    
    /**
     * Delete booking
     */
    public function deleteBooking() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $booking = $this->booking->getById($id);
        
        if ($this->booking->delete($id)) {
            if ($this->activityLog && $booking) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_booking',
                    'booking',
                    $id,
                    [],
                    "Xóa đơn đặt phòng từ khách {$booking['customer_name']}"
                );
            }
            $_SESSION['success'] = 'Xóa đơn đặt phòng thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'bookings.php');
        exit;
    }
}

?>
