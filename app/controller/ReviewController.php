<?php
/**
 * Review Controller
 */

class ReviewController {
    private $review;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->review = new Review($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all reviews
     */
    public function listReviews() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $reviews = $this->review->getAll($page, ITEMS_PER_PAGE);
        $total = $this->review->getTotal();
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        
        return [
            'reviews' => $reviews,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages
        ];
    }
    
    /**
     * View review details
     */
    public function viewReview() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'reviews.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $review = $this->review->getById($id);
        
        if (!$review) {
            header('Location: ' . ADMIN_URL . 'reviews.php');
            exit;
        }
        
        return ['review' => $review];
    }
    
    /**
     * Delete review
     */
    public function deleteReview() {
        if (!isset($_POST['id'])) {
            header('Location: ' . ADMIN_URL . 'reviews.php');
            exit;
        }
        
        $id = (int)$_POST['id'];
        $review = $this->review->getById($id);
        
        if ($this->review->delete($id)) {
            if ($this->activityLog && $review) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_review',
                    'review',
                    $id,
                    [],
                    "Xóa đánh giá sao {$review['rating']} của khách {$review['user_name']}"
                );
            }
            $_SESSION['success'] = 'Xóa đánh giá thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'reviews.php');
        exit;
    }

    public function updateReviewStatus(): void {
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $review = $this->review->getById($id);

        if (!$review || !in_array($status, ['visible', 'hidden'], true)) {
            $_SESSION['error'] = 'Trạng thái đánh giá không hợp lệ.';
            header('Location: ' . ADMIN_URL . 'reviews.php');
            exit;
        }

        if ($this->review->setStatus($id, $status)) {
            if ($this->activityLog) {
                $this->activityLog->log(
                    (int)($_SESSION['user_id'] ?? 0),
                    $status === 'hidden' ? 'hide_review' : 'restore_review',
                    'review',
                    $id,
                    ['old' => $review['status'] ?? null, 'new' => $status],
                    "Cập nhật đánh giá #{$id} sang {$status}"
                );
            }
            $_SESSION['success'] = $status === 'hidden' ? 'Đã ẩn đánh giá.' : 'Đã phục hồi đánh giá.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật đánh giá.';
        }

        header('Location: ' . ADMIN_URL . 'reviews.php');
        exit;
    }
}

?>
