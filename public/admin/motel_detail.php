<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new MotelController($db);
$data = $controller->viewMotel();
$motel = $data['motel'] ?? [];
$status = (string)($motel['status'] ?? 'pending');

admin_layout_start('Chi tiết phòng trọ', 'Rà soát nội dung tin đăng, hình ảnh, tiện nghi và trạng thái kiểm duyệt.', 'motels');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>motels.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Phòng</div><div class="wb-card-value">#<?php echo (int)($motel['id'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Giá thuê</div><div class="wb-card-value fs-4"><?php echo admin_money($motel['price'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Lượt xem</div><div class="wb-card-value"><?php echo (int)($motel['count_view'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title"><?php echo admin_e($motel['title'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($motel['address'] ?? 'Chưa có địa chỉ'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Chủ phòng</div>
            <div><?php echo admin_e($motel['owner_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($motel['owner_email'] ?? ''); ?> · <?php echo admin_e($motel['owner_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Thông tin phòng</div>
            <div class="wb-muted">Danh mục: <?php echo admin_e($motel['category_name'] ?? 'N/A'); ?> · Quận: <?php echo admin_e($motel['district_name'] ?? 'N/A'); ?> · Diện tích: <?php echo admin_e((string)($motel['area'] ?? 'N/A')); ?> m²</div>
            <div class="mt-2"><?php echo nl2br(admin_e($motel['description'] ?? 'N/A')); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Liên hệ</div>
            <div><?php echo admin_e($motel['phone'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <?php if (!empty($motel['rejection_reason'])): ?>
        <div class="wb-list-row">
            <div>
                <div class="wb-title">Lý do từ chối</div>
                <div class="wb-muted"><?php echo nl2br(admin_e($motel['rejection_reason'])); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($data['images'])): ?>
    <div class="wb-section-head"><h2>Hình ảnh</h2><span class="wb-pill"><?php echo count($data['images']); ?> ảnh</span></div>
    <div class="wb-card mb-3">
        <div class="row g-3">
            <?php foreach ($data['images'] as $image): ?>
                <div class="col-md-3">
                    <img src="<?php echo UPLOAD_URL . admin_e($image['image_url'] ?? ''); ?>" alt="Hình ảnh phòng" class="img-fluid rounded">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($data['utilities'])): ?>
    <div class="wb-card mb-3">
        <div class="wb-title mb-2">Tiện nghi</div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($data['utilities'] as $utility): ?>
                <span class="wb-pill"><?php echo admin_e($utility['name'] ?? ''); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="wb-card">
    <div class="wb-actions">
        <?php if (in_array($status, ['pending', 'hidden', 'rejected'], true)): ?>
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('Duyệt/phục hồi phòng này?');">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Duyệt phòng</button>
            </form>
        <?php endif; ?>
        <?php if ($status === 'pending'): ?>
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="const reason = prompt('Nhập lý do từ chối tin phòng:'); if (!reason) return false; this.rejection_reason.value = reason; return true;">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <input type="hidden" name="rejection_reason" value="">
                <button type="submit" class="btn btn-outline-danger"><i class="fa fa-ban"></i> Từ chối</button>
            </form>
        <?php endif; ?>
        <?php if ($status !== 'hidden'): ?>
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('Ẩn phòng này?');">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="hide">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <button type="submit" class="btn btn-warning"><i class="fa fa-times"></i> Ẩn phòng</button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('Xóa phòng này?');">
            <?php echo Csrf::field('admin_motel_action'); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="fa fa-trash"></i> Xóa phòng</button>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>
