<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/track_page_view.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($fullname) || empty($email) || empty($content)) {
        $error_message = 'Vui lòng điền đầy đủ tất cả các trường trong form.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Định dạng email không hợp lệ.';
    } else {
        $success_message = 'Cảm ơn bạn! Yêu cầu hỗ trợ của bạn đã được gửi đi. Chúng tôi sẽ phản hồi qua email trong thời gian sớm nhất.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trợ giúp & Liên hệ - QuanLyPhongTro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        a {
            text-decoration: none !important;
        }

        .home-nav {
            width: 100%;
            position: fixed;
            top: 20px;
            left: 0;
            z-index: 999;
            display: flex;
            justify-content: center;
        }

        .rooms-section {
            padding-top: 120px !important;
            padding-bottom: 50px;
        }

        .grid-rooms {
            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }


        .btn-load-more {
            background-color: #ffffff;
            border: 1px solid #111827;
            color: #111827;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }



        body {
            background: #f6f8fb !important;
            color: #172033;
            overflow-x: hidden;
        }

        .home-nav {
            position: fixed;
            inset: 18px 0 auto;
            z-index: 30;
        }

        .nav-frame {
            height: 66px;
            padding: 0 16px 0 20px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(255, 255, 255, .78);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .12);
            backdrop-filter: blur(18px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
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

        box-shadow: 0 18px 50px rgba(15, 23, 42, .08);

        .footer {
            padding: 34px 0;
            border-top: 1px solid #e5eaf2;
            color: #667085;
        }

        .footer a {
            color: #475467;
            text-decoration: none;
            margin-left: 18px;
            font-weight: 750;
        }


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

        

        

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .login-text {
            color: #111827;
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
            bo x-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
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

        .menu-content {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }


        


        


        


        .nav-actions {
            min-width: 200px;
            display: flex;
            justify-content: flex-end;
            gap: 20px;
        }


        @media (max-width: 991px) {
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


            


            .nav-actions {
                margin-top: 15px;
                padding-top: 20px;
                border-top: 1px solid #f3f4f6;
                align-items: stretch;
            }

            .btn-post {
                text-align: center;
            }
        }

        .bg-light-gray {
            background-color: #f9fafb;
        }

        .text-dark-blue {
            color: #111827;
        }

        .text-teal {
            color: #1185a1;
        }
    </style>
</head>
<body class="bg-light">
    <?php
require_once __DIR__ . '/components/PublicNav.php';
qlpt_render_public_nav(['base' => './', 'active' => 'help']);
?>

    <main class="container" style="padding-top: 130px; padding-bottom: 2rem;">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="fw-bold text-dark mb-3">Trung tâm Trợ giúp</h1>
                    <p class="text-secondary" style="font-size: 1.1rem;">Khám phá các hướng dẫn sử dụng hệ thống hoặc gửi yêu cầu trực tiếp cho chúng tôi.</p>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <a class="d-block h-100 p-4 bg-white border rounded-4 shadow-sm text-decoration-none text-dark" style="transition: transform 0.2s;" href="user/search.php" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-search fs-2 text-primary mb-3"></i>
                            <h5 class="fw-bold">Tìm phòng</h5>
                            <p class="text-muted mb-0 small">Xem danh sách phòng đã duyệt và lọc theo nhu cầu.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a class="d-block h-100 p-4 bg-white border rounded-4 shadow-sm text-decoration-none text-dark" style="transition: transform 0.2s;" href="owner-register.php" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-house-user fs-2 text-success mb-3"></i>
                            <h5 class="fw-bold">Chủ phòng</h5>
                            <p class="text-muted mb-0 small">Đăng ký owner, cập nhật hồ sơ và đăng tin cho thuê.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a class="d-block h-100 p-4 bg-white border rounded-4 shadow-sm text-decoration-none text-dark" style="transition: transform 0.2s;" href="policies/payment-policy.php" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-shield-halved fs-2 text-warning mb-3"></i>
                            <h5 class="fw-bold">Chính sách</h5>
                            <p class="text-muted mb-0 small">Xem quy định thanh toán, người thuê và chủ phòng.</p>
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-5 text-white p-4 p-md-5 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                            <h3 class="fw-bold mb-4">Liên hệ với hệ thống</h3>
                            <p class="mb-4 text-light" style="opacity: 0.9;">Điền thông tin vào biểu mẫu, đội ngũ vận hành sẽ hỗ trợ bạn giải quyết mọi vấn đề nhanh nhất có thể.</p>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-25 p-2 rounded-circle me-3"><i class="fas fa-phone-alt"></i></div>
                                <span>1900 6868</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-25 p-2 rounded-circle me-3"><i class="fas fa-envelope"></i></div>
                                <span>hotro@quanlyphongtro.vn</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-25 p-2 rounded-circle me-3"><i class="fas fa-map-marker-alt"></i></div>
                                <span>Trường Đại học Vinh, Nghệ An</span>
                            </div>
                        </div>
                        <div class="col-md-7 p-4 p-md-5 bg-white">
                            <h4 class="fw-bold mb-4 text-dark">Gửi yêu cầu hỗ trợ</h4>
                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <form action="trogiup.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="fullname" class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Nhập tên của bạn" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email liên hệ <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required>
                                </div>
                                <div class="mb-4">
                                    <label for="content" class="form-label fw-semibold">Nội dung cần hỗ trợ <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="4" placeholder="Mô tả chi tiết vấn đề bạn đang gặp phải..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-dark w-100 py-2 fw-bold rounded-pill shadow-sm">
                                    Gửi yêu cầu <i class="fas fa-paper-plane ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer py-5 mt-5 bg-dark text-white">
            <style>
                /* Tối ưu nền và độ sáng */
                .footer {
                    background-color: #121416 !important;
                    /* Đen sâu hơn để chữ nổi lên */
                    border-top: 1px solid #2d3238;
                }

                /* Làm sáng mô tả và thông tin liên hệ */
                .footer-description,
                .footer-info,
                .footer-copyright {
                    color: #ced4da !important;
                    /* Màu xám bạc sáng, rất rõ trên nền đen */
                    font-size: 0.95rem;
                    line-height: 1.6;
                }

                /* Tùy chỉnh các đường link */
                .footer-link {
                    color: #adb5bd !important;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    display: inline-block;
                    padding: 2px 0;
                }

                .footer-link:hover {
                    color: #ffffff !important;
                    /* Khi di chuột vào sẽ sáng trắng rực rỡ */
                    transform: translateX(5px);
                    /* Hiệu ứng trượt nhẹ sang phải */
                }

                /* Đường kẻ ngăn cách */
                .footer-divider {
                    border-top: 1px solid rgba(255, 255, 255, 0.1);
                }

                /* Icon mạng xã hội */
                .social-links a {
                    color: #ffffff;
                    font-size: 1.2rem;
                    opacity: 0.7;
                    transition: 0.3s;
                }

                .social-links a:hover {
                    opacity: 1;
                    color: #0d6efd;
                    /* Đổi màu xanh khi hover */
                }

                /* Nút Admin sáng hơn */
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
            <div class="container-lg">
                <div class="row gy-4">
                    <!-- Cột 1: Giới thiệu -->
                    <div class="col-lg-4 col-md-6">
                        <h5 class="fw-bold mb-3 text-white">🏠 QuanLyPhongTro</h5>
                        <p class="footer-description">Tìm phòng sạch đẹp, quản lý thuê trọ dễ dàng và minh bạch. Nền tảng kết nối chủ trọ và người thuê hàng đầu.</p>
                        <div class="social-links mt-3">
                            <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="me-3"><i class="bi bi-instagram"></i></a>
                            <a href="#"><i class="bi bi-twitter-x"></i></a>
                        </div>
                    </div>

                    <!-- Cột 2: Dành cho người thuê -->
                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Người Thuê</h6>
                        <ul class="list-unstyled">
                            <li><a href="index.php" class="footer-link">Tìm phòng trọ</a></li>
                            <li><a href="register.php" class="footer-link">Đăng ký tài khoản</a></li>
                            <li><a href="login.php" class="footer-link">Đăng nhập</a></li>
                        </ul>
                    </div>


                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Chủ Phòng</h6>
                        <ul class="list-unstyled">
                            <li><a href="owner-register.php" class="footer-link">Đăng tin cho thuê</a></li>
                            <li><a href="login.php" class="footer-link">Quản lý phòng</a></li>
                            <li><a href="policies/owner-policy.php" class="footer-link">Chính sách chủ trọ</a></li>
                        </ul>
                    </div>


                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Liên hệ hệ thống</h6>
                        <p class="footer-info"><i class="bi bi-geo-alt-fill me-2"></i> Trường Đại học Vinh, Nghệ An</p>
                        <p class="footer-info"><i class="bi bi-envelope-fill me-2"></i> ho-tro@quanlyphongtro.vn</p>
                        <a href="login.php?area=admin" class="btn btn-light btn-sm fw-bold px-3 mt-2">Quản trị viên (Admin)</a>
                    </div>
                </div>

                <hr class="my-4 footer-divider">

                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="footer-copyright">
                        © 2026 <strong>QuanLyPhongTro</strong>. Bản quyền thuộc về Team dự án.
                    </div>
                    <div class="footer-bottom-links">
                        <a href="policies/user-policy.php" class="footer-link me-3">Chính sách người thuê</a>
                        <a href="policies/payment-policy.php" class="footer-link">Chính sách thanh toán</a>
                    </div>
                </div>
            </div>
        </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
            
                }
                document.addEventListener('click', function(e) {
                    const dropdown = document.getElementById('public-user-dropdown');
                    if (dropdown && !e.target.closest('.user-dropdown')) {
                        dropdown.classList.remove('active');
                    }
                });
            });
        </script>
</body>
</html>
