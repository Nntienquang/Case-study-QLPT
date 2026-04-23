<?php
/**
 * PHASE 7: UserController
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $model;

    public function __construct() {
        $this->model = new User();
    }

    public function index($page = 1) {
        $total = $this->model->count();
        $perPage = 10;
        $users = $this->model->paginate($page, $perPage);

        return [
            'users' => $users,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return [];
    }

    // Store user
    public function store($data) {
        $result = $this->model->create([
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'password' => password_hash($data['password'] ?? '', PASSWORD_BCRYPT),
            'role' => $data['role'] ?? 'user'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo người dùng thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo người dùng!');
        }
    }

    // Show edit form
    public function edit($id) {
        $user = $this->model->find($id);
        
        if (!$user) {
            setFlash('error', 'Người dùng không tồn tại!');
            return null;
        }
        
        return ['user' => $user];
    }

    // Update user
    public function update($id, $data) {
        $updateData = [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'role' => $data['role'] ?? 'user'
        ];
        
        // Only update password if provided
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        $result = $this->model->update($id, $updateData);
        
        if ($result) {
            setFlash('success', 'Cập nhật người dùng thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật người dùng!');
        }
    }

    // Delete user
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa người dùng thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa người dùng!');
        }
    }
}
?>
