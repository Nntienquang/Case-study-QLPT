<?php

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Review.php';

class ReviewController {
    private $model;

    public function __construct() {
        $this->model = new Review();
    }

    // List reviews
    public function index($page = 1) {
        $perPage = 10;
        $sql = "SELECT r.*, u.name as user_name, m.title as motel_title 
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN motels m ON r.motel_id = m.id
                ORDER BY r.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $data = $this->model->query($sql, [$offset, $perPage]);
        $total = $this->model->count();
        
        return [
            'reviews' => $data,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Delete review
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa đánh giá thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa đánh giá!');
        }
    }
}
