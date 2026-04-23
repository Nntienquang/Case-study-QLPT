<?php
/**
 * PHASE 7: MotelController with CRUD
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Motel.php';
require_once __DIR__ . '/../models/Category.php';

class MotelController {
    private $model;
    private $categoryModel;

    public function __construct() {
        $this->model = new Motel();
        $this->categoryModel = new Category();
    }

    public function index($page = 1) {
        $perPage = 10;
        $total = $this->model->count();
        
        $sql = "SELECT m.*, u.name as user_name, c.name as category_name
                FROM motels m
                LEFT JOIN users u ON m.user_id = u.id
                LEFT JOIN categories c ON m.category_id = c.id
                ORDER BY m.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $motels = $this->model->query($sql, [$offset, $perPage]);

        return [
            'motels' => $motels,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return ['categories' => $this->categoryModel->all()];
    }

    // Store motel
    public function store($data) {
        $result = $this->model->create([
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'price' => $data['price'] ?? 0,
            'category_id' => $data['category_id'] ?? 0,
            'user_id' => $data['user_id'] ?? 0,
            'status' => 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo phòng trọ thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo phòng trọ!');
        }
    }

    // Show edit form
    public function edit($id) {
        $motel = $this->model->find($id);
        
        if (!$motel) {
            setFlash('error', 'Phòng trọ không tồn tại!');
            return null;
        }
        
        return [
            'motel' => $motel,
            'categories' => $this->categoryModel->all()
        ];
    }

    // Update motel
    public function update($id, $data) {
        $result = $this->model->update($id, [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'price' => $data['price'] ?? 0,
            'category_id' => $data['category_id'] ?? 0,
            'status' => $data['status'] ?? 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật phòng trọ thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật phòng trọ!');
        }
    }

    // Delete motel
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa phòng trọ thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa phòng trọ!');
        }
    }

    public function approve($id) {
        return $this->model->update($id, ['status' => 'approved']);
    }

    public function reject($id) {
        return $this->model->update($id, ['status' => 'hidden']);
    }
}
?>
