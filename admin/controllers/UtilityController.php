<?php

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Utility.php';

class UtilityController {
    private $model;

    public function __construct() {
        $this->model = new Utility();
    }

    // List utilities
    public function index($page = 1) {
        $perPage = 10;
        $data = $this->model->paginate($page, $perPage);
        $total = $this->model->count();
        
        return [
            'utilities' => $data,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return [];
    }

    // Store utility
    public function store($data) {
        $result = $this->model->create([
            'name' => $data['name'] ?? ''
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo tiện ích thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo tiện ích!');
        }
    }

    // Show edit form
    public function edit($id) {
        $utility = $this->model->find($id);
        
        if (!$utility) {
            setFlash('error', 'Tiện ích không tồn tại!');
            return null;
        }
        
        return ['utility' => $utility];
    }

    // Update utility
    public function update($id, $data) {
        $result = $this->model->update($id, [
            'name' => $data['name'] ?? ''
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật tiện ích thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật tiện ích!');
        }
    }

    // Delete utility
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa tiện ích thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa tiện ích!');
        }
    }
}
