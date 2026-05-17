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
            .qlpt-public-nav {
                position: sticky;
                top: 0;
                z-index: 1030;
                background: rgba(255, 255, 255, .94);
                border-bottom: 1px solid #e5eaf2;
                box-shadow: 0 10px 35px rgba(15, 23, 42, .06);
                backdrop-filter: blur(16px);
            }

            .qlpt-public-nav .nav-wrap {
                min-height: 72px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
            }

            .qlpt-public-nav .brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                color: #101828;
                text-decoration: none;
                font-weight: 900;
                white-space: nowrap;
            }

            .qlpt-public-nav .brand-mark {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                display: grid;
                place-items: center;
                background: #101828;
                color: #fff;
            }

            .qlpt-public-nav .nav-links,
            .qlpt-public-nav .nav-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .qlpt-public-nav .nav-link-main,
            .qlpt-public-nav .user-btn {
                color: #475467;
                text-decoration: none;
                font-weight: 800;
                padding: 10px 12px;
                border-radius: 12px;
                border: 0;
                background: transparent;
            }

            .qlpt-public-nav .nav-link-main:hover,
            .qlpt-public-nav .nav-link-main.active,
            .qlpt-public-nav .user-btn:hover {
                color: #101828;
                background: #f1f5f9;
            }

            .qlpt-public-nav .nav-cta {
                color: #fff !important;
                background: #101828 !important;
                padding-inline: 18px;
            }

            .qlpt-public-nav .user-dropdown {
                position: relative;
            }

            .qlpt-public-nav .user-menu {
                position: absolute;
                top: calc(100% + 10px);
                right: 0;
                min-width: 230px;
                padding: 8px;
                border-radius: 14px;
                border: 1px solid #e5eaf2;
                background: #fff;
                box-shadow: 0 18px 55px rgba(15, 23, 42, .16);
                display: none;
            }

            .qlpt-public-nav .user-menu.active {
                display: block;
            }

            .qlpt-public-nav .dropdown-item {
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 10px;
                padding: 10px 12px;
                color: #101828;
                text-decoration: none;
                font-weight: 700;
            }

            .qlpt-public-nav .dropdown-item:hover {
                background: #f8fafc;
            }

            .qlpt-public-nav .logout {
                color: #dc2626;
            }

            .qlpt-public-nav .mobile-toggle {
                display: none;
                width: 40px;
                height: 40px;
                border-radius: 12px;
                border: 1px solid #e5eaf2;
                background: #fff;
                color: #101828;
            }

            @media (max-width: 991px) {
                .qlpt-public-nav .mobile-toggle {
                    display: inline-grid;
                    place-items: center;
                }

                .qlpt-public-nav .menu-content {
                    position: absolute;
                    left: 16px;
                    right: 16px;
                    top: 78px;
                    display: none;
                    flex-direction: column;
                    align-items: stretch;
                    gap: 14px;
                    padding: 16px;
                    border-radius: 18px;
                    border: 1px solid #e5eaf2;
                    background: #fff;
                    box-shadow: 0 22px 60px rgba(15, 23, 42, .14);
                }

                .qlpt-public-nav .menu-content.show,
                .qlpt-public-nav .nav-links,
                .qlpt-public-nav .nav-actions {
                    display: flex;
                    flex-direction: column;
                    align-items: stretch;
                }

                .qlpt-public-nav .user-menu {
                    position: static;
                    margin-top: 8px;
                    box-shadow: none;
                }
            }
        </style>
        <script>
            function qlptTogglePublicMenu(button) {
                const nav = button.closest('.qlpt-public-nav');
                const menu = nav ? nav.querySelector('.menu-content') : null;
                const icon = button.querySelector('i');
                if (!menu) return;
                menu.classList.toggle('show');
                if (icon) {
                    icon.classList.toggle('fa-bars', !menu.classList.contains('show'));
                    icon.classList.toggle('fa-times', menu.classList.contains('show'));
                }
            }

            function qlptToggleUserMenu(event) {
                event.stopPropagation();
                const menu = event.currentTarget.closest('.user-dropdown')?.querySelector('.user-menu');
                if (menu) menu.classList.toggle('active');
            }

            document.addEventListener('click', function(event) {
                document.querySelectorAll('.qlpt-public-nav .user-menu.active').forEach(function(menu) {
                    if (!event.target.closest('.user-dropdown')) {
                        menu.classList.remove('active');
                    }
                });
            });
        </script>
        <?php
    }
    ?>
    <nav class="qlpt-public-nav">
        <div class="container-lg nav-wrap">
            <a href="<?php echo $url('index.php'); ?>" class="brand">
                <span class="brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>

            <button class="mobile-toggle" type="button" onclick="qlptTogglePublicMenu(this)" aria-label="Mở menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="menu-content d-lg-flex align-items-lg-center justify-content-lg-between flex-lg-grow-1">
                <div class="nav-links">
                    <a href="<?php echo $url('phongtro.php'); ?>" class="nav-link-main<?php echo $activeClass('rooms'); ?>">Phòng trọ</a>
                    <a href="<?php echo $url('khuvuc.php'); ?>" class="nav-link-main<?php echo $activeClass('areas'); ?>">Khu vực</a>
                    <a href="<?php echo $url('blog.php'); ?>" class="nav-link-main<?php echo $activeClass('blog'); ?>">Tin tức</a>
                    <a href="<?php echo $url('trogiup.php'); ?>" class="nav-link-main<?php echo $activeClass('help'); ?>">Trợ giúp</a>
                </div>

                <div class="nav-actions">
                    <?php if ($loggedIn): ?>
                        <div class="user-dropdown">
                            <button class="user-btn" type="button" onclick="qlptToggleUserMenu(event)">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="user-menu">
                                <?php if ($role === 'owner'): ?>
                                    <a href="<?php echo $url('owner/dashboard.php'); ?>" class="dropdown-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                                    <a href="<?php echo $url('owner/listings.php'); ?>" class="dropdown-item"><i class="fas fa-home"></i> Phòng của tôi</a>
                                    <a href="<?php echo $url('owner/bookings.php'); ?>" class="dropdown-item"><i class="fas fa-calendar"></i> Booking</a>
                                    <a href="<?php echo $url('owner/profile.php'); ?>" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ</a>
                                <?php else: ?>
                                    <a href="<?php echo $url('user/dashboard.php'); ?>" class="dropdown-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                                    <a href="<?php echo $url('user/search.php'); ?>" class="dropdown-item"><i class="fas fa-search"></i> Tìm phòng</a>
                                    <a href="<?php echo $url('user/my-bookings.php'); ?>" class="dropdown-item"><i class="fas fa-calendar"></i> Booking của tôi</a>
                                    <a href="<?php echo $url('user/saved-motels.php'); ?>" class="dropdown-item"><i class="fas fa-heart"></i> Yêu thích</a>
                                    <a href="<?php echo $url('user/profile.php'); ?>" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ</a>
                                <?php endif; ?>
                                <hr class="dropdown-divider">
                                <a href="<?php echo $url('logout.php'); ?>" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $url('login.php'); ?>" class="nav-link-main">Đăng nhập</a>
                        <a href="<?php echo $url('owner-register.php'); ?>" class="nav-link-main nav-cta">Đăng phòng</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}
