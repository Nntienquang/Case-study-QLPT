<?php
require_once '../config/database.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: blog.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT a.*, c.name AS category_name
    FROM articles a
    LEFT JOIN news_categories c ON a.category_id = c.id
    WHERE a.slug = ? AND a.status = 'published'
    LIMIT 1
");
$stmt->bind_param('s', $slug);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title'] ?? 'Không tìm thấy bài viết'); ?> - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 920px;">
        <a href="blog.php" class="btn btn-outline-secondary mb-4">Quay lại tin tức</a>
        <?php if (!$article): ?>
            <div class="alert alert-warning">Bài viết không tồn tại hoặc chưa được xuất bản.</div>
        <?php else: ?>
            <article class="bg-white border rounded-4 shadow-sm p-4 p-lg-5">
                <div class="text-primary fw-bold mb-2"><?php echo htmlspecialchars($article['category_name'] ?? 'Tin tức'); ?></div>
                <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                <?php if (!empty($article['summary'])): ?>
                    <p class="lead text-muted"><?php echo htmlspecialchars($article['summary']); ?></p>
                <?php endif; ?>
                <?php if (!empty($article['thumbnail'])): ?>
                    <img class="img-fluid rounded-3 mb-4" src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                <?php endif; ?>
                <div class="lh-lg">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>
            </article>
        <?php endif; ?>
    </main>
</body>
</html>
