<?php
/**
 * Menu người thuê — dùng chung.
 * Trước khi include: $userNavActive = 'dashboard'|'search'|...
 * Tùy chọn: $userNavVariant = 'bootstrap' (mặc định) hoặc 'workbench' cho dashboard.php.
 */
if (!isset($userNavActive)) {
    $userNavActive = '';
}
$variant = $userNavVariant ?? 'bootstrap';

$navActive = static function (string $key) use ($userNavActive): string {
    return $key === $userNavActive ? ' active' : '';
};

$items = [
    ['key' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fas fa-home', 'label' => 'Tổng quan'],
    ['key' => 'search', 'href' => 'search.php', 'icon' => 'fas fa-search', 'label' => 'Tìm phòng'],
    ['key' => 'saved_searches', 'href' => 'saved-searches.php', 'icon' => 'fas fa-bell', 'label' => 'Bộ lọc đã lưu'],
    ['key' => 'bookings', 'href' => 'my-bookings.php', 'icon' => 'fas fa-calendar-check', 'label' => 'Đơn đặt của tôi'],
    ['key' => 'favorites', 'href' => 'saved-motels.php', 'icon' => 'fas fa-heart', 'label' => 'Phòng đã lưu'],
    ['key' => 'messages', 'href' => 'messages.php', 'icon' => 'fas fa-comments', 'label' => 'Tin nhắn'],
    ['key' => 'invoices', 'href' => 'my-invoices.php', 'icon' => 'fas fa-file-invoice', 'label' => 'Hóa đơn điện nước'],
    ['key' => 'notifications', 'href' => 'notifications.php', 'icon' => 'fas fa-bell', 'label' => 'Thông báo'],
    ['key' => 'profile', 'href' => 'profile.php', 'icon' => 'fas fa-user', 'label' => 'Hồ sơ'],
    ['key' => 'settings', 'href' => 'settings.php', 'icon' => 'fas fa-cog', 'label' => 'Cài đặt'],
    ['key' => 'logout', 'href' => '../logout.php', 'icon' => 'fas fa-sign-out-alt', 'label' => 'Đăng xuất', 'extraClass' => 'sidebar-logout'],
];

if ($variant === 'workbench') {
    echo '<div class="wb-side-title">Người thuê</div>' . "\n";
    foreach ($items as $it) {
        $extra = $it['extraClass'] ?? '';
        $cls = 'wb-side-link' . $navActive($it['key']) . ($extra !== '' ? ' ' . $extra : '');
        echo '<a class="' . htmlspecialchars(trim($cls), ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8') . '">';
        echo '<i class="' . htmlspecialchars($it['icon'], ENT_QUOTES, 'UTF-8') . '"></i> ';
        echo htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8');
        echo "</a>\n";
    }
    return;
}
?>
<div class="sidebar user-sidebar-nav">
    <h5>Người thuê</h5>
    <?php foreach ($items as $it): ?>
        <?php
        $extra = $it['extraClass'] ?? '';
        $cls = 'sidebar-nav-item' . $navActive($it['key']) . ($extra !== '' ? ' ' . $extra : '');
        ?>
        <a class="<?php echo htmlspecialchars(trim($cls), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8'); ?>">
            <i class="<?php echo htmlspecialchars($it['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <?php echo htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endforeach; ?>
</div>
