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

admin_layout_start('Chi tiáº¿t Ä‘Ã¡nh giÃ¡', 'Xem ná»™i dung pháº£n há»“i cá»§a ngÆ°á»i thuÃª vÃ  xá»­ lÃ½ Ä‘Ã¡nh giÃ¡ khÃ´ng phÃ¹ há»£p.', 'reviews');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>reviews.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">ÄÃ¡nh giÃ¡</div><div class="wb-card-value">#<?php echo (int)($review['id'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Äiá»ƒm</div><div class="wb-card-value"><?php echo (int)($review['rating'] ?? 0); ?>/5</div></div>
    <div class="wb-card"><div class="wb-card-label">NgÃ y táº¡o</div><div class="wb-card-value fs-4"><?php echo !empty($review['created_at']) ? date('d/m/Y', strtotime((string)$review['created_at'])) : ''; ?></div></div>
    <div class="wb-card">
        <div class="wb-card-label">Thao tÃ¡c</div>
        <form method="POST" action="<?php echo ADMIN_URL; ?>reviews.php" onsubmit="return confirm('XÃ³a Ä‘Ã¡nh giÃ¡ nÃ y?');">
            <?php echo Csrf::field('admin_review_action'); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)($review['id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger mt-2"><i class="fa fa-trash"></i> XÃ³a</button>
        </form>
    </div>
</div>

<div class="wb-list-card">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">NgÆ°á»i Ä‘Ã¡nh giÃ¡</div>
            <div><?php echo admin_e($review['user_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($review['email'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">PhÃ²ng trá»</div>
            <div><?php echo admin_e($review['motel_title'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Nháº­n xÃ©t</div>
            <div><?php echo nl2br(admin_e($review['comment'] ?? 'N/A')); ?></div>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

