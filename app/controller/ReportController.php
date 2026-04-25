<?php
/**
 * Report Controller - Quản lý báo cáo vi phạm
 */

class ReportController
{
    private $report;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null)
    {
        $this->report = new Report($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * Lấy danh sách báo cáo (phân trang)
     */
    public function listReports()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $reports = $this->report->getAll($page, ITEMS_PER_PAGE, $status);
        $total = $this->report->getTotal($status);
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        $stats = $this->report->getStats();
        
        return [
            'reports' => $reports,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'status' => $status,
            'stats' => $stats
        ];
    }
    
    /**
     * Xem chi tiết báo cáo
     */
    public function viewReport()
    {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'reports.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $report = $this->report->getById($id);
        
        if (!$report) {
            $_SESSION['error'] = 'Báo cáo không tồn tại';
            header('Location: ' . ADMIN_URL . 'reports.php');
            exit;
        }
        
        return ['report' => $report];
    }
    
    /**
     * Cập nhật trạng thái báo cáo
     */
    public function updateStatus()
    {
        if (!isset($_GET['id']) || !isset($_GET['status'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'reports.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $status = $_GET['status'];
        $admin_note = $_POST['admin_note'] ?? '';
        $admin_id = $_SESSION['user_id'];
        
        // Validate status
        $valid_status = ['investigating', 'resolved', 'rejected', 'closed'];
        if (!in_array($status, $valid_status)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ';
            header('Location: ' . ADMIN_URL . 'reports.php');
            exit;
        }
        
        $report = $this->report->getById($id);
        
        if ($this->report->updateStatus($id, $status, $admin_id, $admin_note)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'update_report_status',
                    'report',
                    $id,
                    ['old' => $report['status'], 'new' => $status],
                    "Cập nhật báo cáo từ {$report['status']} thành {$status}. Ghi chú: {$admin_note}"
                );
            }
            
            $_SESSION['success'] = 'Cập nhật trạng thái báo cáo thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'reports.php');
        exit;
    }
    
    /**
     * Xóa báo cáo
     */
    public function deleteReport()
    {
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'reports.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $admin_id = $_SESSION['user_id'];
        
        $report = $this->report->getById($id);
        
        if ($this->report->delete($id)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'delete_report',
                    'report',
                    $id,
                    [],
                    "Xóa báo cáo: {$report['title']}"
                );
            }
            
            $_SESSION['success'] = 'Xóa báo cáo thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'reports.php');
        exit;
    }
    
    /**
     * Tạo báo cáo từ user (cho frontend)
     */
    public function createReport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Phương thức không hợp lệ';
            return false;
        }
        
        $data = [
            'reporter_id' => $_SESSION['user_id'] ?? 0,
            'reported_user_id' => $_POST['reported_user_id'] ?? null,
            'motel_id' => $_POST['motel_id'] ?? null,
            'report_type' => $_POST['report_type'] ?? 'other',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'evidence_image' => $_POST['evidence_image'] ?? null
        ];
        
        // Validate
        if (empty($data['title']) || empty($data['description'])) {
            $_SESSION['error'] = 'Vui lòng điền tiêu đề và mô tả';
            return false;
        }
        
        if (!$data['reported_user_id'] && !$data['motel_id']) {
            $_SESSION['error'] = 'Vui lòng chọn phòng hoặc chủ trọ cần báo cáo';
            return false;
        }
        
        if ($this->report->create($data)) {
            $_SESSION['success'] = 'Báo cáo của bạn đã được gửi. Admin sẽ xem xét sớm nhất.';
            return true;
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại.';
            return false;
        }
    }
}
?>
