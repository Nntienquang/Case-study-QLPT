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
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'reviews.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
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
}

?>
