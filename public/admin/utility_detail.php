<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$utility = $db->getRow("SELECT * FROM utilities WHERE id = {$id}");
if (!$utility) {
    $_SESSION['error'] = 'Tiện nghi không tồn tại.';
    header('Location: ' . ADMIN_URL . 'utilities.php');
    exit;
}
$motelCount = (int)$db->count('motel_utilities', "utility_id = {$id}");

admin_layout_start('Chi tiết tiện nghi', 'Xem tiện nghi và số tin đang sử dụng.', 'utilities');
admin_flash_messages();
?>

<div class="wb-actions mb-3">
    <a href="<?php echo ADMIN_URL; ?>utilities.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Quay lại</a>
    <a href="<?php echo ADMIN_URL; ?>utility_edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Sửa</a>
</div>
<div class="wb-grid wb-stats-4">
    <div class="wb-card"><div class="wb-card-label">Tiện nghi</div><div class="wb-card-value">#<?php echo $id; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tên</div><div class="wb-card-value fs-4"><?php echo admin_e($utility['name'] ?? ''); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tin đang dùng</div><div class="wb-card-value"><?php echo $motelCount; ?></div></div>
</div>
<?php admin_layout_end(); ?>
