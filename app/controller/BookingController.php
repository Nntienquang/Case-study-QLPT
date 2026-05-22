<?php

/**
 * Booking Controller
 */

class BookingController
{
    private $booking;
    private $db;
    private $activityLog;

    public function __construct($db, $activityLog = null)
    {
        $this->booking = new Booking($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }

    /**
     * List all bookings
     */
    public function listBookings()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = Booking::filterStatus((string)($_GET['status'] ?? ''));

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
    public function viewBooking()
    {
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
    public function updateStatus()
    {
        if (!isset($_POST['id']) || !isset($_POST['status'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $status = (string)$_POST['status'];
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

    private function refreshRoomAvailability(mysqli $conn, int $motelId): void
    {
        if ($motelId <= 0) {
            return;
        }

        $stmt = $conn->prepare("
            SELECT id
            FROM bookings
            WHERE motel_id = ?
              AND booking_status IN ('waiting_payment','paid','confirmed','completed')
              AND payment_status IN ('pending','processing','paid')
              AND (expires_at IS NULL OR expires_at > NOW() OR payment_status = 'paid')
              AND status NOT IN ('rejected','cancelled')
            LIMIT 1
        ");
        $stmt->bind_param('i', $motelId);
        $stmt->execute();
        $hasActiveBooking = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT id
            FROM booking_room_holds
            WHERE motel_id = ? AND hold_status = 'active' AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->bind_param('i', $motelId);
        $stmt->execute();
        $hasActiveHold = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$hasActiveBooking && !$hasActiveHold) {
            $stmt = $conn->prepare("UPDATE motels SET room_status = 'available' WHERE id = ? AND room_status = 'reserved'");
            $stmt->bind_param('i', $motelId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Delete booking
     */
    public function deleteBooking()
    {
        if (!isset($_POST['id'])) {
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $booking = $this->booking->getById($id);

        if (!$booking) {
            $_SESSION['error'] = 'Đơn đặt phòng không tồn tại.';
            header('Location: ' . ADMIN_URL . 'bookings.php');
            exit;
        }

        $motelId = (int)($booking['motel_id'] ?? 0);
        $conn = $this->db->getConnection();
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE payments SET payment_status = 'cancelled', status = 'refunded', updated_at = NOW() WHERE booking_id = ? AND payment_status != 'paid'");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE booking_room_holds SET hold_status = 'released', updated_at = NOW() WHERE booking_id = ? AND hold_status IN ('active','converted')");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', booking_status = 'cancelled', payment_status = IF(payment_status = 'paid', payment_status, 'failed'), updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $this->refreshRoomAvailability($conn, $motelId);

            if ($this->activityLog && $booking) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_booking',
                    'booking',
                    $id,
                    [],
                    "Xóa đơn đặt phòng từ khách {$booking['user_name']}"
                );
            }
            $conn->commit();
            $_SESSION['success'] = 'Đã hủy đơn đặt phòng và trả trạng thái phòng nếu không còn booking giữ chỗ.';
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . ADMIN_URL . 'bookings.php');
        exit;
    }
}
