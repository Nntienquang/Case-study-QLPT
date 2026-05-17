<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../../core/PathHelper.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new MotelController($db);
$data = $controller->viewMotel();
$motel = $data['motel'] ?? [];
$status = (string)($motel['status'] ?? 'pending');

admin_layout_start('Chi tiáº¿t phÃ²ng trá»', 'RÃ  soÃ¡t ná»™i dung tin Ä‘Äƒng, hÃ¬nh áº£nh, tiá»‡n nghi vÃ  tráº¡ng thÃ¡i kiá»ƒm duyá»‡t.', 'motels');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>motels.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">PhÃ²ng</div><div class="wb-card-value">#<?php echo (int)($motel['id'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">GiÃ¡ thuÃª</div><div class="wb-card-value fs-4"><?php echo admin_money($motel['price'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">LÆ°á»£t xem</div><div class="wb-card-value"><?php echo (int)($motel['count_view'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tráº¡ng thÃ¡i</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title"><?php echo admin_e($motel['title'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($motel['address'] ?? 'ChÆ°a cÃ³ Ä‘á»‹a chá»‰'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Chá»§ phÃ²ng</div>
            <div><?php echo admin_e($motel['owner_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($motel['owner_email'] ?? ''); ?> Â· <?php echo admin_e($motel['owner_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">ThÃ´ng tin phÃ²ng</div>
            <div class="wb-muted">Danh má»¥c: <?php echo admin_e($motel['category_name'] ?? 'N/A'); ?> Â· Quáº­n: <?php echo admin_e($motel['district_name'] ?? 'N/A'); ?> Â· Diá»‡n tÃ­ch: <?php echo admin_e((string)($motel['area'] ?? 'N/A')); ?> mÂ²</div>
            <div class="mt-2"><?php echo nl2br(admin_e($motel['description'] ?? 'N/A')); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">LiÃªn há»‡</div>
            <div><?php echo admin_e($motel['phone'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <?php if (!empty($motel['rejection_reason'])): ?>
        <div class="wb-list-row">
            <div>
                <div class="wb-title">LÃ½ do tá»« chá»‘i</div>
                <div class="wb-muted"><?php echo nl2br(admin_e($motel['rejection_reason'])); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($data['images'])): ?>
    <div class="wb-section-head"><h2>HÃ¬nh áº£nh</h2><span class="wb-pill"><?php echo count($data['images']); ?> áº£nh</span></div>
    <div class="wb-card mb-3">
        <div class="row g-3">
            <?php foreach ($data['images'] as $image): ?>
                <div class="col-md-3">
                    <img src="<?php echo admin_e(qlpt_public_asset_url($image['image_url'] ?? '')); ?>" alt="HÃ¬nh áº£nh phÃ²ng" class="img-fluid rounded">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($data['utilities'])): ?>
    <div class="wb-card mb-3">
        <div class="wb-title mb-2">Tiá»‡n nghi</div>
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
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('Duyá»‡t/phá»¥c há»“i phÃ²ng nÃ y?');">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Duyá»‡t phÃ²ng</button>
            </form>
        <?php endif; ?>
        <?php if ($status === 'pending'): ?>
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="const reason = prompt('Nháº­p lÃ½ do tá»« chá»‘i tin phÃ²ng:'); if (!reason) return false; this.rejection_reason.value = reason; return true;">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <input type="hidden" name="rejection_reason" value="">
                <button type="submit" class="btn btn-outline-danger"><i class="fa fa-ban"></i> Tá»« chá»‘i</button>
            </form>
        <?php endif; ?>
        <?php if ($status !== 'hidden'): ?>
            <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('áº¨n phÃ²ng nÃ y?');">
                <?php echo Csrf::field('admin_motel_action'); ?>
                <input type="hidden" name="action" value="hide">
                <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
                <button type="submit" class="btn btn-warning"><i class="fa fa-times"></i> áº¨n phÃ²ng</button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" class="d-inline" onsubmit="return confirm('XÃ³a phÃ²ng nÃ y?');">
            <?php echo Csrf::field('admin_motel_action'); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)($motel['id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="fa fa-trash"></i> XÃ³a phÃ²ng</button>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>

