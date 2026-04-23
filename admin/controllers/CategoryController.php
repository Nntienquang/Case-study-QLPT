<?php

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $model;

    public function __construct() {
        $this->model = new Category();
    }

    // List categories
    public function index($page = 1) {
        $perPage = 10;
        $data = $this->model->paginate($page, $perPage);
        $total = $this->model->count();
        
        return [
            'categories' => $data,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return [];
    }

    // Store category
    public function store($data) {
        $result = $this->model->create([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? ''
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo danh mục thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo danh mục!');
        }
    }

    // Show edit form
    public function edit($id) {
        $category = $this->model->find($id);
        
        if (!$category) {
            setFlash('error', 'Danh mục không tồn tại!');
            return null;
        }
        
        return ['category' => $category];
    }

    // Update category
    public function update($id, $data) {
        $result = $this->model->update($id, [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? ''
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật danh mục thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật danh mục!');
        }
    }

    // Delete category
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa danh mục thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa danh mục!');
        }
    }
}
