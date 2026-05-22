<?php
@require_once '../config/database.php';
@require_once '../core/Database.php';
@require_once '../core/PathHelper.php';
@require_once './components/PublicNav.php';

session_start();

/** @var mysqli $conn */
$isLoggedUser = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user';

$db = new Database($conn);
$user_id = $isLoggedUser ? (int)$_SESSION['user_id'] : 0;

// Get all districts for filter
$stmt = $db->prepare('SELECT id, name FROM districts ORDER BY name');
$stmt->execute();
$districts_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$district_id = $_GET['district_id'] ?? '';
$motels = [];

// Get motels with location data
$sql = 'SELECT id, title, address, lat, lng, price, district_id FROM motels WHERE status = "approved" AND lat IS NOT NULL AND lng IS NOT NULL';
if ($district_id !== '') {
    $sql .= ' AND district_id = ' . (int)$district_id;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// If AJAX request, return JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'get_motels') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'motels' => $motels
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bản đồ Khu vực - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet">
    <link href="./assets/css/modern.css" rel="stylesheet">
    <style>
    body {
        background: #f6f8fb !important;
        font-family: 'Segoe UI', system-ui, sans-serif;
        color: #172033;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* KHẮC PHỤC LỖI MENU: Chừa khoảng trống phía trên bằng padding-top */
    .page-wrapper {
        padding-top: 110px;
        padding-bottom: 40px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .page-header h1 {
        font-weight: 900;
        font-size: 32px;
        color: #101828;
        margin-bottom: 8px;
    }

    .page-header p {
        color: #667085;
        font-size: 16px;
    }

    /* KHUNG TÌM KIẾM ĐƯỢC LÀM MỚI */
    .filter-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 15px 35px rgba(15, 23, 42, 0.04);
        border: 1px solid #e5eaf2;
        margin-bottom: 24px;
    }

    .filter-label {
        font-weight: 750;
        color: #344054;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
    }

    .form-select {
        min-height: 48px;
        border-radius: 12px;
        border: 1px solid #d0d5dd;
        font-weight: 600;
        color: #101828;
        box-shadow: 0 2px 4px rgba(16, 24, 40, 0.02);
    }

    .form-select:focus {
        border-color: #0e7490;
        box-shadow: 0 0 0 4px rgba(14, 116, 144, 0.1);
    }

    .btn-action {
        min-height: 48px;
        padding: 0 24px;
        border-radius: 12px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-search {
        background: #101828;
        color: #fff;
        width: 100%;
    }

    .btn-search:hover {
        background: #1d2939;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 24, 40, 0.15);
    }

    .btn-reset {
        background: #f1f5f9;
        color: #475467;
        width: 100%;
    }

    .btn-reset:hover {
        background: #e2e8f0;
        color: #101828;
    }

    /* KHUNG BẢN ĐỒ LÀM MỚI */
    .map-container {
        flex-grow: 1;
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf2;
        min-height: 60vh;
    }

    #map {
        width: 100%;
        min-height: 65vh;
        /* Bắt buộc phải set cứng chiều cao ở đây bản đồ mới hiện */
        z-index: 1;
    }

    /* CUSTOM LẠI POPUP CỦA BẢN ĐỒ */
    .popup-content {
        font-size: 13px;
        min-width: 280px;
        padding: 16px;
        padding-bottom: 0;
        background: #fff;
    }

    .popup-header {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        align-items: flex-start;
    }

    .popup-icon {
        width: 40px;
        height: 40px;
        background: #f0fdfa;
        border: 1px solid #ccfbf1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d9488;
        flex-shrink: 0;
        font-size: 18px;
    }

    .popup-info {
        flex: 1;
    }

    .popup-title {
        font-weight: 800;
        margin-bottom: 4px;
        color: #101828;
        line-height: 1.35;
        font-size: 15px;
        word-break: break-word;
    }

    .popup-price {
        color: #0e7490;
        font-weight: 900;
        font-size: 18px;
        margin-bottom: 0;
    }

    .popup-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 12px 0 0 0;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
    }

    .popup-meta-item {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        color: #64748b;
        font-size: 13px;
    }

    .popup-meta-icon {
        color: #0e7490;
        font-size: 12px;
        margin-top: 2px;
        flex-shrink: 0;
        width: 16px;
        text-align: center;
    }

    .popup-buttons {
        display: flex;
        gap: 8px;
        padding: 12px 16px 16px;
        background: #f8fafc;
        margin: 0 -16px;
    }

    .popup-btn {
        flex: 1;
        padding: 10px 12px;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .popup-btn-maps {
        background: #ffffff;
        border: 1px solid #d0d5dd;
        color: #344054;
    }

    .popup-btn-maps:hover {
        background: #f1f5f9;
        color: #101828;
        text-decoration: none;
    }

    .popup-btn-detail {
        background: #101828;
        color: white;
    }

    .popup-btn-detail:hover {
        background: #1d2939;
        text-decoration: none;
        color: white;
        box-shadow: 0 4px 10px rgba(16, 24, 40, 0.2);
    }

    .leaflet-popup-content-wrapper {
        border-radius: 16px !important;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15) !important;
        border: 1px solid #e5eaf2 !important;
        padding: 0 !important;
        overflow: hidden;
    }

    .leaflet-popup-content {
        margin: 0 !important;
        padding: 0 !important;
    }

    .house-icon {
        background: #101828;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(16, 24, 40, 0.3);
        border: 2px solid #fff;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 500;
    }

    .spinner {
        border: 3px solid #f3f4f6;
        border-top: 3px solid #101828;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body>
    <?php qlpt_render_public_nav(['base' => './', 'active' => 'areas']); ?>

    <main class="page-wrapper container-lg">

        <div class="page-header mb-4">
            <h1>Bản đồ khu vực</h1>
            <p>Khám phá vị trí các phòng trọ và căn hộ một cách trực quan trên bản đồ.</p>
        </div>

        <div class="filter-card">
            <div class="row align-items-end g-3">
                <div class="col-md-8 col-sm-12">
                    <label for="districtSelect" class="filter-label">Chọn Quận / Huyện</label>
                    <select id="districtSelect" class="form-select">
                        <option value="">Tất cả khu vực</option>
                        <?php foreach ($districts_list as $dist): ?>
                        <option value="<?php echo $dist['id']; ?>"
                            <?php echo (string)$district_id === (string)$dist['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dist['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <button class="btn-action btn-search" onclick="applyFilter()">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                </div>
                <div class="col-md-2 col-sm-6">
                    <button class="btn-action btn-reset" onclick="resetFilter()">
                        <i class="fas fa-redo"></i> Đặt lại
                    </button>
                </div>
            </div>
        </div>

        <div class="map-container">
            <div id="map"></div>
            <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                <div class="spinner"></div>
            </div>
        </div>

    </main>

    <?php qlpt_render_public_footer(['base' => './']); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Initialize map
    const map = L.map('map').setView([18.6791, 105.6819], 13); // Default to Vinh City (Nghệ An)

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Store markers for easy clearing
    const markers = {};

    // Create custom house icon
    function getHouseIcon() {
        return L.divIcon({
            html: '<div class="house-icon" style="width: 36px; height: 36px;"><i class="fas fa-home" style="font-size: 16px;"></i></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
            className: 'custom-icon'
        });
    }

    // Load and display motels
    function loadMotels(districtId = '') {
        showLoading(true);

        const url = new URL(window.location.href);
        const params = new URLSearchParams();
        if (districtId) {
            params.append('district_id', districtId);
        }

        fetch(`khuvuc.php?action=get_motels&${params}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                clearMarkers();

                if (data.success && data.motels.length > 0) {
                    data.motels.forEach(motel => {
                        const lat = parseFloat(motel.lat);
                        const lng = parseFloat(motel.lng);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            const marker = L.marker([lat, lng], {
                                icon: getHouseIcon()
                            }).addTo(map);

                            // Create popup content
                            const popupContent = createPopupContent(motel);
                            marker.bindPopup(popupContent, {
                                maxWidth: 320,
                                minWidth: 320,
                                className: 'custom-popup'
                            });

                            markers[motel.id] = marker;
                        }
                    });

                    // Fit map to all markers
                    if (Object.keys(markers).length > 0) {
                        const group = new L.featureGroup(Object.values(markers));
                        map.fitBounds(group.getBounds().pad(0.1));
                    }
                } else {
                    // Show default view if no motels
                    map.setView([18.6791, 105.6819], 13);
                }

                showLoading(false);
            })
            .catch(err => {
                console.error('Error loading motels:', err);
                showLoading(false);
                alert('Lỗi khi tải dữ liệu. Vui lòng thử lại.');
            });
    }

    // Create popup content HTML
    function createPopupContent(motel) {
        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${motel.lat},${motel.lng}`;
        const detailUrl = `./user/motel-detail.php?id=${motel.id}`;

        return `
                <div class="popup-content">
                    <div class="popup-header">
                        <div class="popup-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="popup-info">
                            <div class="popup-title">${escapeHtml(motel.title)}</div>
                            <div class="popup-price">${new Intl.NumberFormat('vi-VN').format(motel.price)} ₫</div>
                        </div>
                    </div>
                    
                    <div class="popup-meta">
                        <div class="popup-meta-item">
                            <div class="popup-meta-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <span>${escapeHtml(motel.address)}</span>
                        </div>
                    </div>

                    <div class="popup-buttons">
                        <a href="${mapsUrl}" target="_blank" class="popup-btn popup-btn-maps">
                            <i class="fas fa-directions"></i> Chỉ đường
                        </a>
                        <a href="${detailUrl}" class="popup-btn popup-btn-detail">
                            <i class="fas fa-info-circle"></i> Xem chi tiết
                        </a>
                    </div>
                </div>
            `;
    }

    // Clear all markers
    function clearMarkers() {
        Object.values(markers).forEach(marker => {
            map.removeLayer(marker);
        });
        for (let key in markers) {
            delete markers[key];
        }
    }

    // Show/hide loading overlay
    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = show ? 'flex' : 'none';
    }

    // Escape HTML special characters
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Apply filter
    function applyFilter() {
        const districtId = document.getElementById('districtSelect').value;
        const url = new URL(window.location.href);
        url.searchParams.set('district_id', districtId);
        window.history.pushState({}, '', url);
        loadMotels(districtId);
    }

    // Reset filter
    function resetFilter() {
        document.getElementById('districtSelect').value = '';
        const url = new URL(window.location.href);
        url.searchParams.delete('district_id');
        window.history.pushState({}, '', url);
        loadMotels('');
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const districtId = urlParams.get('district_id') || '';
        loadMotels(districtId);
    });
    </script>
</body>

</html>