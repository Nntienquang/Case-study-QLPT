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
        <?php if ($status === 'pending'): ?>
            <a href="<?php echo ADMIN_URL . 'motels.php?action=approve&id=' . (int)($motel['id'] ?? 0); ?>" class="btn btn-success" onclick="return confirm('Duyệt phòng này?');"><i class="fa fa-check"></i> Duyệt phòng</a>
        <?php endif; ?>
        <?php if ($status !== 'hidden'): ?>
            <a href="<?php echo ADMIN_URL . 'motels.php?action=hide&id=' . (int)($motel['id'] ?? 0); ?>" class="btn btn-warning" onclick="return confirm('Ẩn phòng này?');"><i class="fa fa-times"></i> Ẩn phòng</a>
        <?php endif; ?>
        <a href="<?php echo ADMIN_URL . 'motels.php?action=delete&id=' . (int)($motel['id'] ?? 0); ?>" class="btn btn-outline-danger" onclick="return confirm('Xóa phòng này?');"><i class="fa fa-trash"></i> Xóa phòng</a>
    </div>
</div>

<?php admin_layout_end(); ?>
