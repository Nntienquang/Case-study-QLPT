<?php
/**
 * PHASE 7: PaymentController with CRUD
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Booking.php';

class PaymentController {
    private $model;
    private $bookingModel;

    public function __construct() {
        $this->model = new Payment();
        $this->bookingModel = new Booking();
    }

    public function index($page = 1) {
        $total = $this->model->count();
        $perPage = 10;

        $sql = "SELECT p.*, u.name as user_name, m.title as motel_title, p.amount - p.fee as admin_fee
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id
                ORDER BY p.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $payments = $this->model->query($sql, [$offset, $perPage]);

        return [
            'payments' => $payments,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return ['bookings' => $this->bookingModel->all()];
    }

    // Store payment
    public function store($data) {
        $amount = floatval($data['amount'] ?? 0);
        $fee = floatval($data['fee'] ?? 0);
        
        $result = $this->model->create([
            'booking_id' => $data['booking_id'] ?? 0,
            'amount' => $amount,
            'fee' => $fee,
            'method' => $data['method'] ?? 'unknown',
            'status' => $data['status'] ?? 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo thanh toán thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo thanh toán!');
        }
    }

    // Show edit form
    public function edit($id) {
        $payment = $this->model->find($id);
        
        if (!$payment) {
            setFlash('error', 'Thanh toán không tồn tại!');
            return null;
        }
        
        return [
            'payment' => $payment,
            'bookings' => $this->bookingModel->all()
        ];
    }

    // Update payment
    public function update($id, $data) {
        $amount = floatval($data['amount'] ?? 0);
        $fee = floatval($data['fee'] ?? 0);
        
        $result = $this->model->update($id, [
            'booking_id' => $data['booking_id'] ?? 0,
            'amount' => $amount,
            'fee' => $fee,
            'method' => $data['method'] ?? 'unknown',
            'status' => $data['status'] ?? 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật thanh toán thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật thanh toán!');
        }
    }

    // Delete payment
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa thanh toán thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa thanh toán!');
        }
    }
}
?>
