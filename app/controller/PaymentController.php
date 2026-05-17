<?php

class PaymentController
{
    private Payment $payment;
    private Database $db;
    private $activityLog;

    public function __construct(Database $db, $activityLog = null)
    {
        $this->payment = new Payment($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }

    public function listPayments(): array
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? (string)$_GET['status'] : '';

        $payments = $this->payment->getAll($page, ITEMS_PER_PAGE, $status);
        $total = $this->payment->getTotal($status);

        return [
            'payments' => $payments,
            'total' => $total,
            'page' => $page,
            'total_pages' => (int)ceil($total / ITEMS_PER_PAGE),
            'status' => $status,
        ];
    }

    public function viewPayment(): array
    {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }

        $payment = $this->payment->getById((int)$_GET['id']);
        if (!$payment) {
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }

        return ['payment' => $payment];
    }

    public function updateStatus(): void
    {
        if (!isset($_POST['id'], $_POST['status'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $status = (string)$_POST['status'];
        $paymentOld = $this->payment->getById($id);

        $valid = ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'];
        if (!$paymentOld || !in_array($status, $valid, true)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ';
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }

        $oldStatus = (string)($paymentOld['payment_status'] ?? $paymentOld['status'] ?? 'pending');
        $allowedTransitions = [
            'pending' => ['processing', 'paid', 'failed', 'cancelled'],
            'processing' => ['paid', 'failed', 'cancelled'],
            'paid' => ['refunded'],
        ];
        if ($status !== $oldStatus && !in_array($status, $allowedTransitions[$oldStatus] ?? [], true)) {
            $_SESSION['error'] = 'Luồng cập nhật trạng thái thanh toán không hợp lệ';
            header('Location: ' . ADMIN_URL . 'payments.php');
            exit;
        }

        $conn = $this->db->getConnection();
        $conn->begin_transaction();

        try {
            if (!$this->payment->updateStatus($id, $status)) {
                throw new RuntimeException('Không thể cập nhật thanh toán');
            }

            $bookingId = (int)($paymentOld['booking_id'] ?? 0);
            if ($bookingId > 0) {
                $bookingStatus = match ($status) {
                    'paid' => 'paid',
                    'refunded', 'cancelled' => 'cancelled',
                    default => 'waiting_payment',
                };
                $legacyBookingStatus = match ($status) {
                    'paid' => 'paid',
                    'refunded', 'cancelled' => 'cancelled',
                    default => 'pending',
                };
                $stmt = $conn->prepare('UPDATE bookings SET payment_status = ?, booking_status = ?, status = ?, updated_at = NOW() WHERE id = ?');
                $stmt->bind_param('sssi', $status, $bookingStatus, $legacyBookingStatus, $bookingId);
                $stmt->execute();
                $stmt->close();

                if ($status === 'paid') {
                    $stmt = $conn->prepare("UPDATE booking_room_holds SET hold_status = 'converted', updated_at = NOW() WHERE booking_id = ?");
                    $stmt->bind_param('i', $bookingId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            if ($status === 'paid') {
                $payment = $this->payment->getById($id);
                $platformFee = (int)ceil(((int)($payment['amount'] ?? 0)) * 0.05);
                $stmt = $conn->prepare('UPDATE payments SET fee = ? WHERE id = ?');
                $stmt->bind_param('ii', $platformFee, $id);
                $stmt->execute();
                $stmt->close();

                $adminId = (int)($_SESSION['user_id'] ?? 1);
                $stmt = $conn->prepare("INSERT INTO transactions (to_user, booking_id, amount, type, created_at) VALUES (?, ?, ?, 'fee', NOW())");
                $stmt->bind_param('iii', $adminId, $bookingId, $platformFee);
                $stmt->execute();
                $stmt->close();
            }

            if ($this->activityLog) {
                $this->activityLog->log(
                    (int)$_SESSION['user_id'],
                    'update_payment_status',
                    'payment',
                    $id,
                    ['old' => $oldStatus, 'new' => $status],
                    "Cập nhật thanh toán từ {$oldStatus} thành {$status}"
                );
            }

            $conn->commit();
            $_SESSION['success'] = 'Cập nhật trạng thái thanh toán thành công';
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }
}
