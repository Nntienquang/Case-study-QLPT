<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$district = $db->getRow("SELECT * FROM districts WHERE id = {$id}");
if (!$district) {
    $_SESSION['error'] = 'Quận không tồn tại.';
    header('Location: ' . ADMIN_URL . 'districts.php');
    exit;
}
$motelCount = (int)$db->count('motels', "district_id = {$id}");

admin_layout_start('Chi tiết quận', 'Xem khu vực và số tin đang sử dụng.', 'districts');
admin_flash_messages();
?>

<div class="wb-actions mb-3">
    <a href="<?php echo ADMIN_URL; ?>districts.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Quay lại</a>
    <a href="<?php echo ADMIN_URL; ?>district_edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Sửa</a>
</div>
<div class="wb-grid wb-stats-4">
    <div class="wb-card"><div class="wb-card-label">Quận</div><div class="wb-card-value">#<?php echo $id; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tên</div><div class="wb-card-value fs-4"><?php echo admin_e($district['name'] ?? ''); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tin đang dùng</div><div class="wb-card-value"><?php echo $motelCount; ?></div></div>
</div>
<?php admin_layout_end(); ?>
