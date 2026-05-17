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
    <title>Chính sách chủ phòng - QuanLyPhongTro</title>
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
            <h1><i class="fa fa-file-text"></i> Chính sách chủ phòng</h1>
            <p>Hiệu lực từ: 01/01/2024</p>
        </div>

        <div class="toc">
            <h3><i class="fa fa-list"></i> Mục lục</h3>
            <ul>
                <li><a href="#general">1. Quy định chung</a></li>
                <li><a href="#registration">2. Đăng ký và xác thực</a></li>
                <li><a href="#listing">3. Đăng tin và quản lý phòng</a></li>
                <li><a href="#booking">4. Quản lý đặt phòng</a></li>
                <li><a href="#payment">5. Nhận thanh toán</a></li>
                <li><a href="#conduct">6. Quy tắc ứng xử</a></li>
                <li><a href="#responsibility">7. Trách nhiệm của chủ phòng</a></li>
                <li><a href="#dispute">8. Giải quyết tranh chấp</a></li>
            </ul>
        </div>

        <div class="policy-content">
            <h2 id="general">1. Quy định chung</h2>
            <p>
                Chính sách này quy định các điều khoản, điều kiện và yêu cầu cho tất cả chủ phòng sử dụng 
                nền tảng QuanLyPhongTro. Bằng cách đăng ký tài khoản và đăng tin, bạn xác nhận rằng đã đọc, 
                hiểu và đồng ý với các điều khoản này.
            </p>
            <div class="highlight">
                <strong>Lưu ý:</strong> Chủ phòng phải tuân thủ tất cả các quy định pháp luật hiện hành của Việt Nam.
            </div>

            <h2 id="registration">2. Đăng ký và xác thực</h2>
            <h3>2.1 Yêu cầu đăng ký</h3>
            <ul>
                <li>Bạn phải cung cấp thông tin chính xác, đầy đủ và cập nhật</li>
                <li>Bạn phải có trên 18 tuổi và có năng lực pháp lý</li>
                <li>Bạn phải xác thực danh tính thông qua CCCD/CMND</li>
                <li>Bạn phải cung cấp thông tin tài khoản ngân hàng hợp lệ</li>
                <li>Mỗi chủ phòng chỉ được phép tạo một tài khoản</li>
            </ul>
            <h3>2.2 Xác thực danh tính</h3>
            <p>
                Để được phép đăng tin, bạn phải hoàn thành quá trình xác thực danh tính:
            </p>
            <ul>
                <li>Tải lên ảnh CCCD/CMND mặt trước và mặt sau</li>
                <li>Cung cấp thông tin chính xác khớp với CCCD</li>
                <li>Đợi QuanLyPhongTro xác thực (thường 1-2 ngày)</li>
                <li>Sau khi được duyệt, bạn có thể bắt đầu đăng tin</li>
            </ul>
            <div class="highlight">
                <strong>Cảnh báo:</strong> Nếu cung cấp thông tin sai hoặc gian dối, tài khoản của bạn sẽ bị khóa vĩnh viễn.
            </div>

            <h2 id="listing">3. Đăng tin và quản lý phòng</h2>
            <h3>3.1 Yêu cầu thông tin tin đăng</h3>
            <p>Mỗi tin đăng phải bao gồm:</p>
            <ul>
                <li>Tiêu đề rõ ràng, chính xác (tối thiểu 10 ký tự, tối đa 100 ký tự)</li>
                <li>Mô tả chi tiết về phòng (diện tích, tiện nghi, quy tắc, v.v.)</li>
                <li>Giá thuê rõ ràng (giá phòng + các khoản phí khác nếu có)</li>
                <li>Ít nhất 3 ảnh chất lượng cao của phòng</li>
                <li>Địa chỉ chính xác, cụ thể</li>
                <li>Thông tin liên hệ hợp lệ</li>
            </ul>
            <h3>3.2 Tiêu chuẩn hình ảnh</h3>
            <ul>
                <li>Ảnh phải sạch sẽ, rõ ràng, đúng màu sắc thực tế</li>
                <li>Không được chỉnh sửa quá mức hoặc lừa dối</li>
                <li>Phải có ảnh toàn cảnh phòng</li>
                <li>Phải có ảnh các tiện nghi chính (phòng tắm, bếp, v.v.)</li>
                <li>Kích thước ảnh tối thiểu 600x400px</li>
            </ul>
            <h3>3.3 Quản lý tin đăng</h3>
            <ul>
                <li>Bạn có thể chỉnh sửa, cập nhật hoặc xóa tin đăng bất cứ lúc nào</li>
                <li>Cập nhật thông tin phòng kịp thời khi có thay đổi</li>
                <li>Nếu phòng đã cho thuê, hãy tắt tin để tránh nhầm lẫn</li>
                <li>Xóa tin sau khi phòng không còn cho thuê</li>
            </ul>

            <h2 id="booking">4. Quản lý đặt phòng</h2>
            <h3>4.1 Xác nhận đặt phòng</h3>
            <ul>
                <li>Khi có người nhập, bạn sẽ nhận được thông báo</li>
                <li>Bạn có thể xác nhận hoặc từ chối đặt phòng trong vòng 24 giờ</li>
                <li>Nếu không xác nhận trong 24 giờ, đặt phòng sẽ bị hủy tự động</li>
                <li>Sau khi xác nhận, người thuê sẽ có thể liên hệ với bạn để ký hợp đồng</li>
            </ul>
            <h3>4.2 Tiền cọc</h3>
            <p>
                Tiền cọc là số tiền người thuê thanh toán trước để xác nhận ý định thuê. 
                Bạn có quyền quyết định mức tiền cọc (thường 1-2 tháng tiền thuê).
            </p>

            <h2 id="payment">5. Nhận thanh toán</h2>
            <h3>5.1 Phương thức thanh toán</h3>
            <p>
                Chủ phòng nhận tiền từ người thuê thông qua:
            </p>
            <ul>
                <li>Chuyển khoản ngân hàng (được cập nhật trong hồ sơ)</li>
                <li>Thanh toán qua nền tảng (nếu QuanLyPhongTro hỗ trợ)</li>
                <li>Thanh toán trực tiếp (tùy theo thỏa thuận cá nhân)</li>
            </ul>
            <h3>5.2 Phí nền tảng</h3>
            <p>
                QuanLyPhongTro tính phí 5% trên mỗi giao dịch thành công qua nền tảng. 
                Phí này được sử dụng để duy trì và phát triển nền tảng.
            </p>
            <h3>5.3 Hoàn tiền</h3>
            <ul>
                <li>Nếu người thuê hủy đặt phòng, bạn phải hoàn lại tiền cọc theo chính sách hủy</li>
                <li>Hoàn tiền sẽ được xử lý trong 5-7 ngày làm việc</li>
                <li>Bạn có thể giữ một phần tiền cọc nếu người thuê hủy trễ</li>
            </ul>

            <h2 id="conduct">6. Quy tắc ứng xử</h2>
            <h3>6.1 Hành vi bị cấm</h3>
            <ul>
                <li>Đăng tin giả mạo, lừa dối hoặc sai lệch thông tin</li>
                <li>Sử dụng hình ảnh không phải của phòng thực tế</li>
                <li>Quấy rối, tấn công hoặc lạm dụng người thuê</li>
                <li>Yêu cầu thanh toán ngoài nền tảng mà không được phép</li>
                <li>Phân biệt chủng tộc, tôn giáo hoặc giới tính</li>
                <li>Sử dụng bot, phần mềm tự động để quản lý tin</li>
                <li>Những hoạt động bất hợp pháp khác</li>
            </ul>
            <h3>6.2 Hậu quả vi phạm</h3>
            <ul>
                <li>Cảnh báo lần đầu</li>
                <li>Khóa tin đăng hoặc tài khoản tạm thời</li>
                <li>Xóa tài khoản vĩnh viễn</li>
                <li>Thu hồi tiền cọc hoặc tiền thanh toán</li>
                <li>Báo cáo cho cơ quan chức năng nếu cần thiết</li>
            </ul>

            <h2 id="responsibility">7. Trách nhiệm của chủ phòng</h2>
            <ul>
                <li>Cung cấp phòng đúng như mô tả trong tin đăng</li>
                <li>Đảm bảo phòng sạch sẽ, an toàn và đủ tiện nghi</li>
                <li>Tuân thủ các quy định pháp luật về cho thuê nhà ở</li>
                <li>Ký hợp đồng hợp lý với người thuê</li>
                <li>Tôn trọng quyền riêng tư của người thuê</li>
                <li>Không phân biệt chủng tộc, tôn giáo hoặc giới tính</li>
                <li>Giải quyết các sự cố và bảo trì phòng kịp thời</li>
                <li>Trả lại tiền cọc hoặc tiền thuế hợp lý sau khi kết thúc hợp đồng</li>
            </ul>

            <h2 id="dispute">8. Giải quyết tranh chấp</h2>
            <h3>8.1 Báo cáo vấn đề</h3>
            <p>
                Nếu phát sinh tranh chấp với người thuê, hãy:
            </p>
            <ol>
                <li>Cố gắng giải quyết trực tiếp với người thuê</li>
                <li>Nếu không thành công, gửi báo cáo qua nền tảng</li>
                <li>Cung cấp bằng chứng (ảnh, tin nhắn, hợp đồng, v.v.)</li>
                <li>Mô tả chi tiết vấn đề và giải pháp mong muốn</li>
            </ol>
            <h3>8.2 Quy trình xử lý</h3>
            <p>
                QuanLyPhongTro sẽ:
            </p>
            <ul>
                <li>Kiểm tra báo cáo trong vòng 2-3 ngày làm việc</li>
                <li>Liên hệ cả chủ phòng và người thuê</li>
                <li>Thu thập bằng chứng và thông tin chi tiết</li>
                <li>Đưa ra quyết định công bằng</li>
                <li>Thực hiện giải pháp hoặc hoàn tiền nếu cần thiết</li>
            </ul>
            <div class="highlight">
                <strong>Lưu ý:</strong> Quyết định của QuanLyPhongTro là cuối cùng và bắt buộc phải tuân theo.
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999;">
            <p>© 2024 QuanLyPhongTro. Bảo lưu mọi quyền.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
