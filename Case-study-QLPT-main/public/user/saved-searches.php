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
            $message = 'Da xoa bo loc da luu.';
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
            $message = 'Da cap nhat trang thai nhan thong bao.';
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
    <title>Bo loc da luu - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06); }
        .layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 22px; }
        .side-panel, .content-card, .search-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .side-panel { padding: 18px; height: fit-content; position: sticky; top: 88px; }
        .side-link { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 12px; color: #475467; text-decoration: none; font-weight: 750; }
        .side-link:hover, .side-link.active { background: #f2f4f7; color: #101828; }
        .content-card { padding: 24px; margin-bottom: 18px; }
        .search-card { padding: 18px; margin-bottom: 14px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; }
        .search-title { font-weight: 900; font-size: 18px; color: #101828; }
        .meta { color: #667085; font-size: 14px; display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 8px; }
        .pill { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: #ecfeff; color: #0e7490; font-weight: 800; font-size: 12px; }
        .pill.off { background: #f2f4f7; color: #667085; }
        .empty-state { text-align: center; padding: 44px 20px; color: #667085; }
        @media (max-width: 991px) { .layout, .search-card { grid-template-columns: 1fr; } .side-panel { position: static; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="../notifications.php"><i class="fas fa-bell"></i> Thong bao</a>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Dang xuat</a>
            </div>
        </div>
    </nav>

    <main class="app-shell">
        <div class="container-lg layout">
            <aside class="side-panel">
                <div class="fw-bold mb-3">Tai khoan</div>
                <a class="side-link" href="dashboard.php"><i class="fas fa-home"></i> Tong quan</a>
                <a class="side-link" href="search.php"><i class="fas fa-search"></i> Tim phong</a>
                <a class="side-link active" href="saved-searches.php"><i class="fas fa-bell"></i> Bo loc da luu</a>
                <a class="side-link" href="my-bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>
                <a class="side-link" href="saved-motels.php"><i class="fas fa-heart"></i> Phong da luu</a>
                <a class="side-link" href="profile.php"><i class="fas fa-user"></i> Ho so</a>
            </aside>

            <section>
                <div class="content-card">
                    <h1 class="fw-bold mb-2">Bo loc da luu</h1>
                    <p class="text-muted mb-0">Mo lai bo loc nhanh, bat/tat thong bao phong moi phu hop.</p>
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
                                        <?php echo (int)$search['alert_enabled'] === 1 ? 'Dang nhan thong bao' : 'Da tat thong bao'; ?>
                                    </span>
                                </div>
                                <div class="meta">
                                    <span><i class="fas fa-keyboard"></i> <?php echo htmlspecialchars($search['keyword'] ?: 'Bat ky tu khoa'); ?></span>
                                    <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($search['district_name'] ?: 'Tat ca quan'); ?></span>
                                    <span><i class="fas fa-list"></i> <?php echo htmlspecialchars($search['category_name'] ?: 'Tat ca loai phong'); ?></span>
                                    <span><i class="fas fa-wallet"></i> <?php echo $search['price_min'] ? number_format((int)$search['price_min']) : '0'; ?> - <?php echo $search['price_max'] ? number_format((int)$search['price_max']) : 'khong gioi han'; ?> VND</span>
                                    <span><i class="fas fa-ruler-combined"></i> Tu <?php echo $search['area_min'] ? (int)$search['area_min'] : 0; ?> m2</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(search_url($search)); ?>">Tim lai</a>
                                <form method="POST">
                                    <input type="hidden" name="search_id" value="<?php echo (int)$search['id']; ?>">
                                    <button class="btn btn-outline-primary btn-sm" name="toggle_alert" type="submit">Bat/tat thong bao</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Xoa bo loc nay?');">
                                    <input type="hidden" name="search_id" value="<?php echo (int)$search['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm" name="delete_search" type="submit">Xoa</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card empty-state">
                        <h4 class="fw-bold">Chua co bo loc nao</h4>
                        <p>Hay tim phong va bam "Luu bo loc" de quay lai nhanh hon.</p>
                        <a href="search.php" class="btn btn-primary">Tim phong</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
