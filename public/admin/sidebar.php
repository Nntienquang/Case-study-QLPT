<?php
/**
 * Legacy admin sidebar partial.
 *
 * New admin pages should use layout.php directly. This partial delegates to
 * admin_nav_items() so any older include still renders the same menu.
 */
require_once __DIR__ . '/layout.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$pages = admin_nav_items();
?>

<div class="sidebar">
    <div class="logo">
        <h2>Admin</h2>
        <p>QuanLyPhongTro</p>
    </div>

    <ul class="nav-menu">
        <?php foreach ($pages as $key => $page): ?>
            <li>
                <a href="<?php echo ADMIN_URL . $page['url']; ?>"
                   class="<?php echo ($current_page === $key) ? 'active' : ''; ?>">
                    <i class="<?php echo admin_e($page['icon']); ?>"></i>
                    <?php echo admin_e($page['label']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
