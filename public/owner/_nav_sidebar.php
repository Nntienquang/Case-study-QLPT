<?php
/**
 * Menu chủ phòng — dùng chung.
 * Trước khi include: $ownerNavActive = 'dashboard'|'listings'|...
 * Tùy chọn: $ownerNavVariant = 'bootstrap' (mặc định) hoặc 'workbench' cho dashboard.php.
 */
if (!isset($ownerNavActive)) {
    $ownerNavActive = '';
}
$variant = $ownerNavVariant ?? 'bootstrap';

$navActive = static function (string $key) use ($ownerNavActive): string {
    return $key === $ownerNavActive ? ' active' : '';
};

$items = [
    ['key' => 'dashboard', 'href' => 'index.php', 'icon' => 'fas fa-chart-line', 'label' => 'Tổng quan'],
    ['key' => 'listings', 'href' => 'listings.php', 'icon' => 'fas fa-list', 'label' => 'Phòng của tôi'],
    ['key' => 'add_listing', 'href' => 'add-listing.php', 'icon' => 'fas fa-plus', 'label' => 'Đăng phòng'],
    ['key' => 'viewings', 'href' => 'viewing-appointments.php', 'icon' => 'fas fa-calendar-day', 'label' => 'Lịch xem'],
    ['key' => 'bookings', 'href' => 'bookings.php', 'icon' => 'fas fa-calendar-check', 'label' => 'Đơn đặt phòng'],
    ['key' => 'revenue', 'href' => 'revenue.php', 'icon' => 'fas fa-chart-column', 'label' => 'Doanh thu'],
    ['key' => 'invoices', 'href' => 'monthly-invoices.php', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'Hóa đơn điện nước'],
    ['key' => 'messages', 'href' => 'messages.php', 'icon' => 'fas fa-comments', 'label' => 'Tin nhắn'],
    ['key' => 'notifications', 'href' => 'notifications.php', 'icon' => 'fas fa-bell', 'label' => 'Thông báo'],
    ['key' => 'profile', 'href' => 'profile.php', 'icon' => 'fas fa-user', 'label' => 'Hồ sơ'],
    ['key' => 'settings', 'href' => 'settings.php', 'icon' => 'fas fa-cog', 'label' => 'Cài đặt'],
    ['key' => 'logout', 'href' => '../logout.php', 'icon' => 'fas fa-sign-out-alt', 'label' => 'Đăng xuất', 'extraClass' => 'sidebar-logout'],
];

if ($variant === 'workbench') {
    echo '<div class="wb-side-title">Chủ phòng</div>' . "\n";
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
<div class="sidebar owner-sidebar-nav">
    <h5>Chủ phòng</h5>
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
