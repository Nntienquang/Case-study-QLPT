<?php
/**
 * PHASE 7: TransactionController with CRUD
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/User.php';

class TransactionController {
    private $model;
    private $userModel;

    public function __construct() {
        $this->model = new Transaction();
        $this->userModel = new User();
    }

    public function index($page = 1) {
        $total = $this->model->count();
        $perPage = 10;

        $sql = "SELECT t.*, u1.name as from_user_name, u2.name as to_user_name
                FROM transactions t
                LEFT JOIN users u1 ON t.from_user = u1.id
                LEFT JOIN users u2 ON t.to_user = u2.id
                ORDER BY t.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $transactions = $this->model->query($sql, [$offset, $perPage]);

        return [
            'transactions' => $transactions,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return ['users' => $this->userModel->all()];
    }

    // Store transaction
    public function store($data) {
        $result = $this->model->create([
            'from_user' => $data['from_user'] ?? 0,
            'to_user' => $data['to_user'] ?? 0,
            'amount' => floatval($data['amount'] ?? 0),
            'fee' => floatval($data['fee'] ?? 0),
            'type' => $data['type'] ?? 'transfer'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo giao dịch thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo giao dịch!');
        }
    }

    // Delete transaction
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa giao dịch thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa giao dịch!');
        }
    }
}
?>
