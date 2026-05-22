<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new ReviewController($db);
$data = $controller->viewReview();
$review = $data['review'] ?? [];

admin_layout_start('Chi tiết đánh giá', 'Xem nội dung phản hồi của người thuê và xử lý đánh giá không phù hợp.', 'reviews');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>reviews.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Đánh giá</div><div class="wb-card-value">#<?php echo (int)($review['id'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Điểm</div><div class="wb-card-value"><?php echo (int)($review['rating'] ?? 0); ?>/5</div></div>
    <div class="wb-card"><div class="wb-card-label">Ngày tạo</div><div class="wb-card-value fs-4"><?php echo !empty($review['created_at']) ? date('d/m/Y', strtotime((string)$review['created_at'])) : ''; ?></div></div>
    <div class="wb-card">
        <div class="wb-card-label">Thao tác</div>
        <form method="POST" action="<?php echo ADMIN_URL; ?>reviews.php" onsubmit="return confirm('Xóa đánh giá này?');">
            <?php echo Csrf::field('admin_review_action'); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)($review['id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger mt-2"><i class="fa fa-trash"></i> Xóa</button>
        </form>
        <form method="POST" action="<?php echo ADMIN_URL; ?>reviews.php">
            <?php echo Csrf::field('admin_review_action'); ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?php echo (int)($review['id'] ?? 0); ?>">
            <input type="hidden" name="status" value="<?php echo ($review['status'] ?? 'visible') === 'hidden' ? 'visible' : 'hidden'; ?>">
            <button type="submit" class="btn btn-outline-secondary mt-2"><i class="bi <?php echo ($review['status'] ?? 'visible') === 'hidden' ? 'bi-eye' : 'bi-eye-slash'; ?>"></i> <?php echo ($review['status'] ?? 'visible') === 'hidden' ? 'Phục hồi' : 'Ẩn'; ?></button>
        </form>
    </div>
</div>

<div class="wb-list-card">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Người đánh giá</div>
            <div><?php echo admin_e($review['user_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($review['email'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Phòng trọ</div>
            <div><?php echo admin_e($review['motel_title'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Nhận xét</div>
            <div><?php echo nl2br(admin_e($review['comment'] ?? 'N/A')); ?></div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

