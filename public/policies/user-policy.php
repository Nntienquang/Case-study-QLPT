<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AuthMiddleware.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách người thuê - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .policy-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .policy-content h2 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .policy-content h3 {
            color: #555;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .policy-content p {
            line-height: 1.8;
            color: #666;
            margin-bottom: 10px;
        }
        .policy-content ul, .policy-content ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        .policy-content li {
            margin-bottom: 8px;
            color: #666;
        }
        .toc {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .toc h3 {
            margin-bottom: 15px;
        }
        .toc ul {
            list-style: none;
            padding-left: 0;
        }
        .toc a {
            color: #667eea;
            text-decoration: none;
        }
        .toc a:hover {
            text-decoration: underline;
        }
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">QuanLyPhongTro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Chính sách</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">Đăng xuất</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../login.php">Đăng nhập</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="policy-container">
        <div class="policy-header">
            <h1><i class="fa fa-file-text"></i> Chính sách người thuê</h1>
            <p>Hiệu lực từ: 01/01/2024</p>
        </div>

        <div class="toc">
            <h3><i class="fa fa-list"></i> Mục lục</h3>
            <ul>
                <li><a href="#general">1. Quy định chung</a></li>
                <li><a href="#registration">2. Đăng ký và xác thực</a></li>
                <li><a href="#booking">3. Quy trình đặt phòng</a></li>
                <li><a href="#payment">4. Thanh toán</a></li>
                <li><a href="#cancellation">5. Hủy đặt phòng</a></li>
                <li><a href="#conduct">6. Quy tắc ứng xử</a></li>
                <li><a href="#responsibility">7. Trách nhiệm của người thuê</a></li>
                <li><a href="#dispute">8. Giải quyết tranh chấp</a></li>
            </ul>
        </div>

        <div class="policy-content">
            <h2 id="general">1. Quy định chung</h2>
            <p>
                Chính sách này quy định các điều khoản, điều kiện và quy tắc ứng xử cho tất cả người thuê sử dụng 
                nền tảng QuanLyPhongTro. Bằng cách đăng ký tài khoản và sử dụng dịch vụ, bạn xác nhận rằng đã đọc, 
                hiểu và đồng ý với các điều khoản này.
            </p>
            <div class="highlight">
                <strong>Lưu ý:</strong> Nếu bạn không đồng ý với bất kỳ phần nào của chính sách này, vui lòng ngừng sử dụng dịch vụ.
            </div>

            <h2 id="registration">2. Đăng ký và xác thực</h2>
            <h3>2.1 Yêu cầu đăng ký</h3>
            <ul>
                <li>Bạn phải cung cấp thông tin chính xác, đầy đủ và cập nhật</li>
                <li>Bạn chịu trách nhiệm bảo mật mật khẩu tài khoản</li>
                <li>Bạn phải từ 18 tuổi trở lên để sử dụng dịch vụ</li>
                <li>Mỗi người chỉ được phép tạo một tài khoản</li>
            </ul>
            <h3>2.2 Xác thực email</h3>
            <p>
                Sau khi đăng ký, bạn sẽ nhận được email xác thực. Vui lòng click vào link trong email để hoàn thành 
                quá trình đăng ký. Nếu không xác thực, tài khoản của bạn có thể bị hạn chế.
            </p>

            <h2 id="booking">3. Quy trình đặt phòng</h2>
            <h3>3.1 Tìm kiếm và xem phòng</h3>
            <ul>
                <li>Bạn có thể tìm kiếm phòng trọ dựa trên tiêu chí: vị trí, giá cả, tiện nghi, v.v.</li>
                <li>Tất cả thông tin phòng (hình ảnh, giá, địa chỉ) được cung cấp bởi chủ phòng</li>
                <li>QuanLyPhongTro không chịu trách nhiệm về độ chính xác của thông tin được cung cấp</li>
            </ul>
            <h3>3.2 Quy trình đặt phòng</h3>
            <ol>
                <li>Chọn phòng muốn thuê và nhấp "Đặt phòng"</li>
                <li>Xác nhận thông tin đặt phòng (ngày thuê, thời gian)</li>
                <li>Thanh toán tiền cọc theo yêu cầu</li>
                <li>Chờ chủ phòng xác nhận</li>
                <li>Khi được chủ phòng xác nhận, đặt phòng của bạn sẽ được kích hoạt</li>
            </ol>
            <h3>3.3 Tiền cọc</h3>
            <p>
                Tiền cọc là số tiền người thuê phải thanh toán trước để xác nhận đặt phòng. 
                Số tiền cọc thường bằng tiền thuê 1-2 tháng đầu (theo quy định của chủ phòng).
            </p>

            <h2 id="payment">4. Thanh toán</h2>
            <h3>4.1 Phương thức thanh toán</h3>
            <ul>
                <li>Chuyển khoản ngân hàng</li>
                <li>Ví điện tử (nếu có)</li>
                <li>Thanh toán trực tiếp tại quầy (tùy theo chính sách của chủ phòng)</li>
            </ul>
            <h3>4.2 Phí dịch vụ</h3>
            <p>
                QuanLyPhongTro tính phí 5% trên mỗi giao dịch thành công. Phí này được sử dụng để 
                duy trì và phát triển nền tảng.
            </p>
            <h3>4.3 Bảo mật thanh toán</h3>
            <p>
                Tất cả thông tin thanh toán của bạn được mã hóa và bảo mật. QuanLyPhongTro không lưu 
                trữ thông tin thẻ credit/debit của bạn.
            </p>

            <h2 id="cancellation">5. Hủy đặt phòng</h2>
            <h3>5.1 Điều kiện hủy</h3>
            <ul>
                <li>Bạn có thể hủy đặt phòng trước ngày thuê ít nhất 7 ngày</li>
                <li>Hủy sớm hơn có thể sẽ mất một phần tiền cọc (theo quy định của chủ phòng)</li>
                <li>Hủy trong vòng 7 ngày trước ngày thuê: mất 50% tiền cọc</li>
                <li>Hủy trong 24 giờ trước ngày thuê: mất toàn bộ tiền cọc</li>
            </ul>
            <h3>5.2 Quy trình hủy</h3>
            <ol>
                <li>Truy cập tài khoản và chọn "Quản lý đặt phòng"</li>
                <li>Chọn phòng cần hủy</li>
                <li>Nhấp "Hủy đặt phòng" và xác nhận</li>
                <li>Tiền hoàn lại sẽ được xử lý trong 5-7 ngày làm việc</li>
            </ol>

            <h2 id="conduct">6. Quy tắc ứng xử</h2>
            <h3>6.1 Hành vi bị cấm</h3>
            <ul>
                <li>Sử dụng dịch vụ cho mục đích bất hợp pháp hoặc bất lợi</li>
                <li>Đăng tải, chia sẻ nội dung khiêu dâm, bạo lực hoặc kích động tôn giáo</li>
                <li>Qu騷rão, tấn công hoặc lạm dụng các người dùng khác</li>
                <li>Lừa đảo, giả mạo danh tính</li>
                <li>Sử dụng bot, phần mềm tự động để truy cập nền tảng</li>
            </ul>
            <h3>6.2 Hậu quả vi phạm</h3>
            <p>
                Khi vi phạm quy tắc ứng xử, QuanLyPhongTro có quyền:
            </p>
            <ul>
                <li>Cảnh báo người dùng</li>
                <li>Khóa tài khoản tạm thời</li>
                <li>Xóa tài khoản vĩnh viễn</li>
                <li>Báo cáo cho cơ quan chức năng nếu cần thiết</li>
            </ul>

            <h2 id="responsibility">7. Trách nhiệm của người thuê</h2>
            <ul>
                <li>Kiểm tra phòng kỹ lưỡng trước khi ký hợp đồng với chủ phòng</li>
                <li>Tuân thủ các quy định của chủ phòng và tòa nhà</li>
                <li>Bảo vệ tài sản của chủ phòng</li>
                <li>Thông báo cho chủ phòng về mọi sự cố hoặc hư hỏng</li>
                <li>Thanh toán tiền thuê đầy đủ và đúng hạn</li>
                <li>Không làm phiền người hàng xóm</li>
            </ul>

            <h2 id="dispute">8. Giải quyết tranh chấp</h2>
            <h3>8.1 Báo cáo vấn đề</h3>
            <p>
                Nếu phát sinh tranh chấp với chủ phòng, vui lòng:
            </p>
            <ol>
                <li>Liên hệ trực tiếp với chủ phòng để thảo luận</li>
                <li>Nếu không giải quyết được, gửi báo cáo qua nền tảng</li>
                <li>Cung cấp bằng chứng (ảnh, tin nhắn, v.v.)</li>
            </ol>
            <h3>8.2 Quy trình xử lý</h3>
            <p>
                QuanLyPhongTro sẽ:
            </p>
            <ul>
                <li>Kiểm tra báo cáo trong vòng 2-3 ngày làm việc</li>
                <li>Liên hệ cả hai bên để thu thập thông tin</li>
                <li>Đưa ra quyết định dựa trên bằng chứng</li>
                <li>Thực hiện giải pháp hoặc hoàn tiền nếu cần thiết</li>
            </ul>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999;">
            <p>© 2024 QuanLyPhongTro. Bảo lưu mọi quyền.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
