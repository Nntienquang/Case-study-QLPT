<?php

function qlpt_render_public_nav(array $options = []): void
{
    static $stylePrinted = false;

    $base = (string)($options['base'] ?? '');
    $active = (string)($options['active'] ?? '');
    $name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'User';
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
    $loggedIn = isset($_SESSION['user_id']);

    $url = static function (string $path) use ($base): string {
        return htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8');
    };
    $activeClass = static function (string $key) use ($active): string {
        return $key === $active ? ' active' : '';
    };

    if (!$stylePrinted) {
        $stylePrinted = true;
?>
<style>
.home-nav {
    width: 100%;
    position: absolute;
    top: 20px;
    left: 0;
    z-index: 100;
    display: flex;
    justify-content: center;
}

.nav-container {
    background-color: #ffffff;
    width: 90%;
    max-width: 1200px;
    border-radius: 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px 10px 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #101828;
    text-decoration: none;
    font-weight: 900;
}

.brand-mark {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #101828;
    color: #fff;
}

.nav-links {
    display: flex;
    gap: 45px;
    margin-left: auto;
    margin-right: auto;
}

.nav-item {
    text-decoration: none;
    color: #1f2937;
    font-size: 18px;
    font-weight: 600;
    padding: 10px 0;
    transition: color 0.2s ease, transform 0.2s ease;
    display: inline-block;
    position: relative;
}

.nav-item:hover,
.nav-item.active {
    color: #000;
    transform: translateY(-1px);
}

.nav-item::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #1185a1;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.nav-item:hover::after,
.nav-item.active::after {
    transform: scaleX(1);
}

.nav-actions {
    min-width: 200px;
    display: flex;
    justify-content: flex-end;
    gap: 20px;
    align-items: center;
}

.login-text {
    color: #111827;
    text-decoration: none;
    font-weight: 600;
}

.btn-post {
    background-color: #111827;
    color: #ffffff !important;
    text-decoration: none;
    padding: 10px 24px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
}

.btn-post:hover {
    background-color: #1f2937;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
}

/* --- CSS MỚI CHO NÚT USER VÀ LOGOUT --- */
.user-profile-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f3f4f6;
    border: none;
    color: #111827;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.user-btn:hover {
    background-color: #e5e7eb;
    color: #000;
}

.user-btn i:first-child {
    font-size: 18px;
}

.btn-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #fee2e2;
    color: #dc2626;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    background-color: #fca5a5;
    color: #991b1b;
}

.hamburger-btn {
    display: none;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #111827;
    cursor: pointer;
}

.menu-content {
    display: flex;
    flex-grow: 1;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 991px) {
    .home-nav {
        inset: 10px 0 auto;
    }

    .hamburger-btn {
        display: block;
    }

    .menu-content {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: #ffffff;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        padding: 20px;
        margin-top: 15px;
    }

    .menu-content.show {
        display: flex;
    }

    .nav-links,
    .nav-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        width: 100%;
    }

    .nav-actions {
        margin-top: 15px;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
        align-items: stretch;
    }

    .btn-post {
        text-align: center;
    }

    .user-profile-group {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const menuContent = document.getElementById('mobile-menu');

    if (menuBtn && menuContent) {
        const icon = menuBtn.querySelector('i');
        menuBtn.addEventListener('click', function() {
            menuContent.classList.toggle('show');
            if (menuContent.classList.contains('show')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
});
</script>
<?php
    }
    ?>

<nav class="home-nav">
    <div class="nav-container">

        <a href="<?php echo $url('index.php'); ?>" class="brand">
            <span class="brand-mark"><i class="fas fa-house-chimney"></i></span>
            <span class="brand-name">QuanLyPhongTro</span>
        </a>

        <button class="hamburger-btn" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>

        <div class="menu-content" id="mobile-menu">
            <div class="nav-links">
                <a href="<?php echo $url('user/search.php'); ?>"
                    class="nav-item<?php echo $activeClass('rooms'); ?>">Phòng
                    trọ</a>
                <a href="<?php echo $url('khuvuc.php'); ?>" class="nav-item<?php echo $activeClass('areas'); ?>">Khu
                    vực</a>
                <a href="<?php echo $url('blog.php'); ?>" class="nav-item<?php echo $activeClass('blog'); ?>">Tin
                    tức</a>
                <a href="<?php echo $url('trogiup.php'); ?>" class="nav-item<?php echo $activeClass('help'); ?>">Trợ
                    giúp</a>
            </div>

            <div class="nav-actions">
                <?php if ($loggedIn): ?>
                <?php
                        // Xác định link dashboard dựa vào role
                        $dashboardLink = 'user/dashboard.php';
                        if ($role === 'owner') {
                            $dashboardLink = 'owner/dashboard.php';
                        } elseif ($role === 'admin') {
                            $dashboardLink = 'admin/index.php';
                        }
                        ?>
                <div class="user-profile-group">
                    <a href="<?php echo $url($dashboardLink); ?>" class="user-btn" title="Vào trang quản lý">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($name); ?></span>
                    </a>
                    <a href="<?php echo $url('logout.php'); ?>" class="btn-logout" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <?php else: ?>
                <a href="<?php echo $url('login.php'); ?>" class="nav-item login-text">Đăng nhập</a>
                <a href="<?php echo $url('owner-register.php'); ?>" class="btn-post">Đăng phòng</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</nav>
<?php
}


function qlpt_render_public_footer(array $options = []): void
{
    static $footerStylePrinted = false;
    $base = (string)($options['base'] ?? '');

    $url = static function (string $path) use ($base): string {
        return htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8');
    };

    if (!$footerStylePrinted) {
        $footerStylePrinted = true;
    ?>
<style>
.footer {
    background-color: #121416 !important;
    border-top: 1px solid #2d3238;
}

.footer-description,
.footer-info,
.footer-copyright {
    color: #ced4da !important;
    font-size: 0.95rem;
    line-height: 1.6;
}

.footer-link {
    color: #adb5bd !important;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
    padding: 2px 0;
}

.footer-link:hover {
    color: #ffffff !important;
    transform: translateX(5px);
}

.footer-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.social-links a {
    color: #ffffff;
    font-size: 1.2rem;
    opacity: 0.7;
    transition: 0.3s;
    text-decoration: none;
}

.social-links a:hover {
    opacity: 1;
    color: #0d6efd;
}

.btn-light {
    background-color: #f8f9fa;
    border: none;
    color: #212529 !important;
}

.btn-light:hover {
    background-color: #ffffff;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
}
</style>
<?php
    }
    ?>
<footer class="footer py-5 mt-5 bg-dark text-white">
    <div class="container-lg">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold mb-3 text-white">🏠 QuanLyPhongTro</h5>
                <p class="footer-description">Tìm phòng sạch đẹp, quản lý thuê trọ dễ dàng và minh bạch. Nền tảng kết
                    nối chủ trọ và người thuê hàng đầu.</p>
                <div class="social-links mt-3">
                    <a href="<?php echo $url('trogiup.php'); ?>" class="me-3" aria-label="Facebook"><i
                            class="fab fa-facebook"></i></a>
                    <a href="<?php echo $url('blog.php'); ?>" class="me-3" aria-label="Instagram"><i
                            class="fab fa-instagram"></i></a>
                    <a href="<?php echo $url('trogiup.php'); ?>" aria-label="X"><i class="fab fa-x-twitter"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-white text-uppercase small">Người Thuê</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url('index.php'); ?>" class="footer-link">Tìm phòng trọ</a></li>
                    <li><a href="<?php echo $url('register.php'); ?>" class="footer-link">Đăng ký tài khoản</a></li>
                    <li><a href="<?php echo $url('login.php'); ?>" class="footer-link">Đăng nhập</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-white text-uppercase small">Chủ Phòng</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $url('owner-register.php'); ?>" class="footer-link">Đăng tin cho thuê</a>
                    </li>
                    <li><a href="<?php echo $url('login.php'); ?>" class="footer-link">Quản lý phòng</a></li>
                    <li><a href="<?php echo $url('policies/owner-policy.php'); ?>" class="footer-link">Chính sách chủ
                            trọ</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3 text-white text-uppercase small">Liên hệ hệ thống</h6>
                <p class="footer-info"><i class="fas fa-location-dot me-2"></i> Trường Đại học Vinh, Nghệ An</p>
                <p class="footer-info"><i class="fas fa-envelope me-2"></i> ho-tro@quanlyphongtro.vn</p>
                <a href="<?php echo $url('login.php?area=admin'); ?>"
                    class="btn btn-light btn-sm fw-bold px-3 mt-2">Quản trị viên (Admin)</a>
            </div>
        </div>

        <hr class="my-4 footer-divider">

        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="footer-copyright">
                © 2026 <strong>QuanLyPhongTro</strong>. Bản quyền thuộc về Team dự án.
            </div>
            <div class="footer-bottom-links">
                <a href="<?php echo $url('policies/user-policy.php'); ?>" class="footer-link me-3">Chính sách người
                    thuê</a>
                <a href="<?php echo $url('policies/owner-policy.php'); ?>" class="footer-link me-3">Chính sách chủ
                    phòng</a>
                <a href="<?php echo $url('policies/payment-policy.php'); ?>" class="footer-link me-3">Chính sách thanh
                    toán</a>
                <a href="<?php echo $url('policies/user-policy.php'); ?>" class="footer-link">Điều khoản sử dụng</a>
            </div>
        </div>
    </div>
</footer>
<?php
}