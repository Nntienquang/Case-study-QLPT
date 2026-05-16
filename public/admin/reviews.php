<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new ReviewController($db, $activityLog);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    if (!Csrf::validateRequest('admin_review_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'reviews.php');
        exit;
    }

    $controller->deleteReview();
}

$data = $controller->listReviews();

admin_layout_start('Quản lý đánh giá', 'Theo dõi phản hồi của người thuê để phát hiện phòng kém chất lượng hoặc nội dung không phù hợp.', 'reviews');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sách đánh giá</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> đánh giá</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['reviews'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người dùng</th>
                    <th>Phòng trọ</th>
                    <th>Điểm</th>
                    <th>Nhận xét</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['reviews'] as $review): ?>
                    <tr>
                        <td>#<?php echo (int)$review['id']; ?></td>
                        <td><?php echo admin_e($review['user_name'] ?? 'N/A'); ?></td>
                        <td class="wb-title"><?php echo admin_e(substr((string)($review['motel_title'] ?? 'N/A'), 0, 50)); ?></td>
                        <td><span class="wb-pill warning"><?php echo (int)($review['rating'] ?? 0); ?>/5</span></td>
                        <td><?php echo admin_e(substr((string)($review['comment'] ?? 'N/A'), 0, 70)); ?></td>
                        <td><?php echo !empty($review['created_at']) ? date('d/m/Y', strtotime((string)$review['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'review_detail.php?id=' . (int)$review['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa đánh giá này?');">
                                <?php echo Csrf::field('admin_review_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$review['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có đánh giá nào.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'reviews.php?page=' . $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>
