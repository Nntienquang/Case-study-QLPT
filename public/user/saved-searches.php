<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';


session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = (int)$_SESSION['user_id'];
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_id = (int)($_POST['search_id'] ?? 0);

    if (isset($_POST['delete_search'])) {
        $stmt = $db->prepare('DELETE FROM saved_searches WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $search_id, $user_id);
        if ($stmt->execute()) {
            $message = 'Đã xóa bộ lọc đã lưu.';
        }
        $stmt->close();
    }

    if (isset($_POST['toggle_alert'])) {
        $stmt = $db->prepare('
            UPDATE saved_searches
            SET alert_enabled = CASE WHEN alert_enabled = 1 THEN 0 ELSE 1 END
            WHERE id = ? AND user_id = ?
        ');
        $stmt->bind_param('ii', $search_id, $user_id);
        if ($stmt->execute()) {
            $message = 'Đã cập nhật trạng thái nhận thông báo.';
        }
        $stmt->close();
    }
}

$stmt = $db->prepare('
    SELECT ss.*, d.name AS district_name, c.name AS category_name
    FROM saved_searches ss
    LEFT JOIN districts d ON ss.district_id = d.id
    LEFT JOIN categories c ON ss.category_id = c.id
    WHERE ss.user_id = ?
    ORDER BY ss.created_at DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$saved_searches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function search_url(array $search): string
{
    $query = array_filter([
        'keyword' => $search['keyword'] ?? '',
        'district_id' => $search['district_id'] ?? '',
        'category_id' => $search['category_id'] ?? '',
        'min_price' => $search['price_min'] ?? '',
        'max_price' => $search['price_max'] ?? '',
        'area_min' => $search['area_min'] ?? '',
        'sort' => 'match',
    ], fn($value) => $value !== null && $value !== '');

    return 'search.php?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bộ lọc đã lưu - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06); }
        .content-card, .search-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .content-card { padding: 24px; margin-bottom: 18px; }
        .search-card { padding: 18px; margin-bottom: 14px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; }
        .search-title { font-weight: 900; font-size: 18px; color: #101828; }
        .meta { color: #667085; font-size: 14px; display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 8px; }
        .pill { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: #ecfeff; color: #0e7490; font-weight: 800; font-size: 12px; }
        .pill.off { background: #f2f4f7; color: #667085; }
        .empty-state { text-align: center; padding: 44px 20px; color: #667085; }
        @media (max-width: 991px) { .search-card { grid-template-columns: 1fr; } }
    </style>
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <?php 
    @require_once __DIR__ . '/../components/PublicNav.php'; 
    qlpt_render_public_nav(['base' => '../', 'active' => 'user']); 
    ?>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <?php
                $userNavActive = 'saved_searches';
                $userNavVariant = 'workbench';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </aside>

            <section>
                <div class="content-card">
                    <h1 class="fw-bold mb-2">Bộ lọc đã lưu</h1>
                    <p class="text-muted mb-0">Mở lại bộ lọc nhanh, bật/tắt thông báo phòng mới phù hợp.</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($saved_searches): ?>
                    <?php foreach ($saved_searches as $search): ?>
                        <article class="search-card">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="search-title"><?php echo htmlspecialchars($search['name']); ?></div>
                                    <span class="pill <?php echo (int)$search['alert_enabled'] === 1 ? '' : 'off'; ?>">
                                        <?php echo (int)$search['alert_enabled'] === 1 ? 'Đang nhận thông báo' : 'Đã tắt thông báo'; ?>
                                    </span>
                                </div>
                                <div class="meta">
                                    <span><i class="fas fa-keyboard"></i> <?php echo htmlspecialchars($search['keyword'] ?: 'Bất kỳ từ khóa'); ?></span>
                                    <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($search['district_name'] ?: 'Tất cả quận'); ?></span>
                                    <span><i class="fas fa-list"></i> <?php echo htmlspecialchars($search['category_name'] ?: 'Tất cả loại phòng'); ?></span>
                                    <span><i class="fas fa-wallet"></i> <?php echo $search['price_min'] ? number_format((int)$search['price_min']) : '0'; ?> - <?php echo $search['price_max'] ? number_format((int)$search['price_max']) : 'không giới hạn'; ?> VNĐ</span>
                                    <span><i class="fas fa-ruler-combined"></i> Từ <?php echo $search['area_min'] ? (int)$search['area_min'] : 0; ?> m²</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(search_url($search)); ?>">Tìm lại</a>
                                <form method="POST">
                                    <input type="hidden" name="search_id" value="<?php echo (int)$search['id']; ?>">
                                    <button class="btn btn-outline-primary btn-sm" name="toggle_alert" type="submit">Bật/tắt thông báo</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Xóa bộ lọc này?');">
                                    <input type="hidden" name="search_id" value="<?php echo (int)$search['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm" name="delete_search" type="submit">Xóa</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card empty-state">
                        <h4 class="fw-bold">Chưa có bộ lọc nào</h4>
                        <p>Hãy tìm phòng và bấm Lưu bộ lọc trên trang tìm kiếm để quay lại nhanh hơn.</p>
                        <a href="search.php" class="btn btn-primary">Tìm phòng</a>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
