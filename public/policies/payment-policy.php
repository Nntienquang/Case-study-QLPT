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
    <title>Chính sách thanh toán - QuanLyPhongTro</title>
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
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .fee-table th, .fee-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .fee-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
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
            <h1><i class="fa fa-file-text"></i> Chính sách thanh toán</h1>
            <p>Hiệu lực từ: 01/01/2024</p>
        </div>

        <div class="toc">
            <h3><i class="fa fa-list"></i> Mục lục</h3>
            <ul>
                <li><a href="#general">1. Quy định chung</a></li>
                <li><a href="#methods">2. Phương thức thanh toán</a></li>
                <li><a href="#fees">3. Phí và khoản phí</a></li>
                <li><a href="#process">4. Quy trình thanh toán</a></li>
                <li><a href="#security">5. Bảo mật thanh toán</a></li>
                <li><a href="#refund">6. Hoàn tiền</a></li>
                <li><a href="#dispute">7. Giải quyết tranh chấp</a></li>
                <li><a href="#liability">8. Trách nhiệm pháp lý</a></li>
            </ul>
        </div>

        <div class="policy-content">
            <h2 id="general">1. Quy định chung</h2>
            <p>
                Chính sách thanh toán này quy định các điều khoản, điều kiện và quy trình thanh toán 
                trên nền tảng QuanLyPhongTro. Tất cả các giao dịch trên nền tảng đều phải tuân thủ chính sách này.
            </p>
            <div class="highlight">
                <strong>Lưu ý:</strong> Bằng cách sử dụng dịch vụ thanh toán của chúng tôi, bạn đồng ý với tất cả các điều khoản trong chính sách này.
            </div>

            <h2 id="methods">2. Phương thức thanh toán</h2>
            <h3>2.1 Các phương thức được hỗ trợ</h3>
            <p>QuanLyPhongTro hỗ trợ các phương thức thanh toán sau:</p>
            <ul>
                <li><strong>Chuyển khoản ngân hàng:</strong> Chuyển trực tiếp vào tài khoản của chủ phòng (phí ngân hàng tính bởi ngân hàng)</li>
                <li><strong>Ví điện tử:</strong> Các ứng dụng ví kỹ thuật số được phép (nếu nền tảng tích hợp)</li>
                <li><strong>Thanh toán trực tiếp:</strong> Người thuê và chủ phòng có thể thỏa thuận thanh toán trực tiếp ngoài nền tảng</li>
            </ul>
            <h3>2.2 Yêu cầu thông tin thanh toán</h3>
            <ul>
                <li>Thông tin tài khoản ngân hàng phải chính xác và hợp lệ</li>
                <li>Tên chủ tài khoản phải khớp với tên được xác thực</li>
                <li>Đối với chủ phòng: Phải cập nhật thông tin tài khoản để nhận tiền</li>
                <li>Đối với người thuê: Phải cung cấp phương thức thanh toán hợp lệ</li>
            </ul>

            <h2 id="fees">3. Phí và khoản phí</h2>
            <h3>3.1 Phí nền tảng</h3>
            <p>QuanLyPhongTro tính phí trên mỗi giao dịch thanh toán thành công:</p>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Loại giao dịch</th>
                        <th>Phí</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Platform fee (5% tiền cọc/tiền thuê)</td>
                        <td>5%</td>
                        <td>Được tính khi payment status = released</td>
                    </tr>
                    <tr>
                        <td>Phí chuyển khoản ngân hàng</td>
                        <td>Theo ngân hàng</td>
                        <td>Tính bởi ngân hàng, không phải nền tảng</td>
                    </tr>
                    <tr>
                        <td>Phí ghi danh</td>
                        <td>Miễn phí</td>
                        <td>Không tính phí ghi danh</td>
                    </tr>
                </tbody>
            </table>
            <h3>3.2 Cách tính platform fee</h3>
            <p>
                Platform fee được tính theo công thức:
            </p>
            <p style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;">
                Platform fee = Total amount × 5%<br>
                Owner amount = Total amount - Platform fee
            </p>
            <p>
                <strong>Ví dụ:</strong> Nếu tiền cọc là 1.000.000 VND, platform fee = 50.000 VND, 
                chủ phòng nhận 950.000 VND.
            </p>
            <h3>3.3 Không hoàn lại phí</h3>
            <p>
                Các khoản phí đã trích không được hoàn lại ngay cả khi giao dịch bị hủy hoặc từ chối 
                (trừ trường hợp lỗi hệ thống).
            </p>

            <h2 id="process">4. Quy trình thanh toán</h2>
            <h3>4.1 Quy trình đặt phòng và thanh toán</h3>
            <ol>
                <li><strong>Bước 1:</strong> Người thuê chọn phòng và nhập thông tin đặt phòng</li>
                <li><strong>Bước 2:</strong> Chọn phương thức thanh toán</li>
                <li><strong>Bước 3:</strong> Nhập thông tin thanh toán (tài khoản ngân hàng, ví điện tử, v.v.)</li>
                <li><strong>Bước 4:</strong> Xác nhận giao dịch</li>
                <li><strong>Bước 5:</strong> Gửi đơn đặt phòng</li>
                <li><strong>Bước 6:</strong> Chủ phòng xác nhận đặt phòng</li>
                <li><strong>Bước 7:</strong> Admin xác nhận payment status = released</li>
                <li><strong>Bước 8:</strong> Tiền được chuyển cho chủ phòng (trừ 5% platform fee)</li>
            </ol>
            <h3>4.2 Thời gian xử lý</h3>
            <ul>
                <li>Thanh toán qua nền tảng: 1-3 ngày làm việc</li>
                <li>Chuyển khoản ngân hàng: 1-5 ngày làm việc (tùy ngân hàng)</li>
                <li>Ví điện tử: 1-24 giờ</li>
            </ul>

            <h2 id="security">5. Bảo mật thanh toán</h2>
            <h3>5.1 Bảo vệ thông tin</h3>
            <ul>
                <li>Tất cả thông tin thanh toán được mã hóa SSL/TLS</li>
                <li>Không lưu trữ thông tin thẻ credit/debit</li>
                <li>Tuân thủ tiêu chuẩn PCI DSS Level 1</li>
                <li>Không chia sẻ thông tin thanh toán cho bên thứ ba</li>
            </ul>
            <h3>5.2 Xác thực giao dịch</h3>
            <ul>
                <li>Bắt buộc xác thực danh tính trước khi thanh toán</li>
                <li>Sử dụng OTP hoặc xác thực hai yếu tố nếu cần</li>
                <li>Kiểm tra ghi nhận IP lạ hoặc hoạt động bất thường</li>
            </ul>
            <h3>5.3 Chịu trách nhiệm của người dùng</h3>
            <ul>
                <li>Không chia sẻ mã OTP hoặc mật khẩu với ai</li>
                <li>Kiểm tra thông tin giao dịch trước khi xác nhận</li>
                <li>Báo cáo ngay nếu phát hiện giao dịch lạ</li>
                <li>Đổi mật khẩu định kỳ</li>
            </ul>

            <h2 id="refund">6. Hoàn tiền</h2>
            <h3>6.1 Điều kiện hoàn tiền</h3>
            <p>
                QuanLyPhongTro sẽ hoàn tiền trong các trường hợp:
            </p>
            <ul>
                <li>Giao dịch bị từ chối do lỗi hệ thống</li>
                <li>Người thuê hủy đặt phòng (theo chính sách hủy)</li>
                <li>Chủ phòng từ chối xác nhận đặt phòng</li>
                <li>Giao dịch trùng lặp</li>
                <li>Bất kỳ lỗi hoặc sự cố khác do nền tảng gây ra</li>
            </ul>
            <h3>6.2 Quy trình hoàn tiền</h3>
            <ol>
                <li>Yêu cầu hoàn tiền phải được gửi trong vòng 30 ngày từ giao dịch</li>
                <li>Cung cấp bằng chứng (giao dịch ID, ảnh chụp, v.v.)</li>
                <li>QuanLyPhongTro sẽ kiểm tra trong 2-3 ngày làm việc</li>
                <li>Nếu được phê duyệt, tiền sẽ được hoàn lại trong 5-7 ngày làm việc</li>
                <li>Phí ngân hàng hoặc ví không được hoàn lại</li>
            </ol>

            <h2 id="dispute">7. Giải quyết tranh chấp</h2>
            <h3>7.1 Tranh chấp thanh toán</h3>
            <p>
                Nếu phát sinh tranh chấp liên quan đến thanh toán:
            </p>
            <ul>
                <li>Liên hệ trực tiếp với QuanLyPhongTro trong vòng 30 ngày</li>
                <li>Cung cấp đầy đủ thông tin và bằng chứng</li>
                <li>QuanLyPhongTro sẽ điều tra trong 5-7 ngày làm việc</li>
                <li>Thông báo quyết định và thực hiện giải pháp</li>
            </ul>
            <h3>7.2 Chargeback</h3>
            <p>
                Nếu người thuê yêu cầu chargeback từ ngân hàng:
            </p>
            <ul>
                <li>Hành vi chargeback coi là vi phạm chính sách</li>
                <li>Tài khoản có thể bị khóa hoặc xóa</li>
                <li>Phí chargeback sẽ được trừ vào tài khoản</li>
            </ul>

            <h2 id="liability">8. Trách nhiệm pháp lý</h2>
            <h3>8.1 Trách nhiệm của QuanLyPhongTro</h3>
            <ul>
                <li>Đảm bảo bảo mật và toàn vẹn giao dịch</li>
                <li>Xử lý giao dịch theo chính sách đã công bố</li>
                <li>Hỗ trợ khách hàng khi phát sinh sự cố</li>
                <li>Tuân thủ các quy định pháp luật về thanh toán</li>
            </ul>
            <h3>8.2 Không chịu trách nhiệm</h3>
            <p>
                QuanLyPhongTro không chịu trách nhiệm trong các trường hợp:
            </p>
            <ul>
                <li>Lỗi do phía người dùng (sai thông tin tài khoản, mất mật khẩu, v.v.)</li>
                <li>Giao dịch được phê duyệt nhưng ngân hàng từ chối</li>
                <li>Mất mát do bên thứ ba (tin tặc, người giả mạo, v.v.)</li>
                <li>Gián đoạn dịch vụ do sự kiện bất khả kháng</li>
                <li>Tranh chấp giữa người thuê và chủ phòng (ngoài lĩnh vực thanh toán)</li>
            </ul>
            <div class="highlight">
                <strong>Lưu ý:</strong> QuanLyPhongTro là đơn vị cung cấp nền tảng, không phải nhà cung cấp dịch vụ thanh toán trực tiếp.
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999;">
            <p>© 2024 QuanLyPhongTro. Bảo lưu mọi quyền.</p>
            <p>Để được hỗ trợ, vui lòng liên hệ: <strong>support@quanlyphongto.local</strong></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
