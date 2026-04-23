<?php
/**
 * PHASE 7: WithdrawController with CRUD
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/WithdrawRequest.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/User.php';

class WithdrawController {
    private $model;
    private $walletModel;
    private $userModel;

    public function __construct() {
        $this->model = new WithdrawRequest();
        $this->walletModel = new Wallet();
        $this->userModel = new User();
    }

    public function index($page = 1) {
        $total = $this->model->count();
        $perPage = 10;

        $sql = "SELECT wr.*, u.name as user_name, u.email
                FROM withdraw_requests wr
                LEFT JOIN users u ON wr.user_id = u.id
                ORDER BY wr.created_at DESC
                LIMIT ?, ?";
        
        $offset = ($page - 1) * $perPage;
        $withdraws = $this->model->query($sql, [$offset, $perPage]);

        return [
            'withdraws' => $withdraws,
            'pagination' => getPagination($total, $page, $perPage)
        ];
    }

    // Show create form
    public function create() {
        return ['users' => $this->userModel->all()];
    }

    // Store withdraw request
    public function store($data) {
        $result = $this->model->create([
            'user_id' => $data['user_id'] ?? 0,
            'amount' => floatval($data['amount'] ?? 0),
            'status' => 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Tạo yêu cầu rút tiền thành công!');
        } else {
            setFlash('error', 'Lỗi khi tạo yêu cầu rút tiền!');
        }
    }

    // Show edit form
    public function edit($id) {
        $withdraw = $this->model->find($id);
        
        if (!$withdraw) {
            setFlash('error', 'Yêu cầu rút tiền không tồn tại!');
            return null;
        }
        
        return [
            'withdraw' => $withdraw,
            'users' => $this->userModel->all()
        ];
    }

    // Update withdraw request
    public function update($id, $data) {
        $result = $this->model->update($id, [
            'user_id' => $data['user_id'] ?? 0,
            'amount' => floatval($data['amount'] ?? 0),
            'status' => $data['status'] ?? 'pending'
        ]);
        
        if ($result) {
            setFlash('success', 'Cập nhật yêu cầu rút tiền thành công!');
        } else {
            setFlash('error', 'Lỗi khi cập nhật yêu cầu rút tiền!');
        }
    }

    // Delete withdraw request
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            setFlash('success', 'Xóa yêu cầu rút tiền thành công!');
        } else {
            setFlash('error', 'Lỗi khi xóa yêu cầu rút tiền!');
        }
    }

    public function approve($id) {
        $withdraw = $this->model->find($id);
        if ($withdraw) {
            // Deduct from wallet using update query
            $sql = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            $stmt = $GLOBALS['db']->prepare($sql);
            $stmt->execute([$withdraw['amount'], $withdraw['user_id']]);

            // Update withdraw status
            $this->model->update($id, ['status' => 'approved']);
            return true;
        }
        return false;
    }

    public function reject($id) {
        return $this->model->update($id, ['status' => 'rejected']);
    }
}
?>
