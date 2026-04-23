<?php
/**
 * PHASE 7: BookingController with CRUD
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Motel.php';

class BookingController {
    private $model;
    private $userModel;
    private $motelModel;

    public function __construct() {
        $this->model = new Booking();
        $this->userModel = new User();
        $this->motelModel = new Motel();
    }

    public function index($page = 1) {
        $total = $this->model->count();
        $perPage = 10;
        
        // Get bookings with user and motel info
        $sql = "SELECT b.*, u.name as user_name, m.title as motel_title
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id
                ORDER BY b.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $bookings = $this->model->query($sql, [$offset, $perPage]);

        return [
            'bookings' => $bookings,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return [
            'users' => $this->userModel->all(),
            'motels' => $this->motelModel->all()
        ];
    }

    // Store booking
    public function store($data) {
        $result = $this->model->create([
            'user_id' => $data['user_id'] ?? 0,
            'motel_id' => $data['motel_id'] ?? 0,
            'checkin_date' => $data['checkin_date'] ?? '',
            'checkout_date' => $data['checkout_date'] ?? '',
            'notes' => $data['notes'] ?? '',
            'status' => 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo đặt phòng thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo đặt phòng!');
        }
    }

    // Show edit form
    public function edit($id) {
        $booking = $this->model->find($id);
        
        if (!$booking) {
            setFlash('error', 'Đặt phòng không tồn tại!');
            return null;
        }
        
        return [
            'booking' => $booking,
            'users' => $this->userModel->all(),
            'motels' => $this->motelModel->all()
        ];
    }

    // Update booking
    public function update($id, $data) {
        $result = $this->model->update($id, [
            'user_id' => $data['user_id'] ?? 0,
            'motel_id' => $data['motel_id'] ?? 0,
            'checkin_date' => $data['checkin_date'] ?? '',
            'checkout_date' => $data['checkout_date'] ?? '',
            'notes' => $data['notes'] ?? '',
            'status' => $data['status'] ?? 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật đặt phòng thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật đặt phòng!');
        }
    }

    // Delete booking
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa đặt phòng thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa đặt phòng!');
        }
    }

    public function accept($id) {
        return $this->model->update($id, ['status' => 'accepted']);
    }

    public function reject($id) {
        return $this->model->update($id, ['status' => 'rejected']);
    }
}
?>
