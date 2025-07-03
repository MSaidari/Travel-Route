<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Onay bekleyen yorumları çek
$pending_reviews = $conn->query("(
    SELECT rb.id, u.fullname AS user_name, rb.comment, rb.rating, rb.approved, b.title AS baslik, 'reviews_blogs' AS table_name
    FROM reviews_blogs rb 
    JOIN users u ON rb.user_id = u.id 
    JOIN blog_posts b ON rb.blogs_id = b.id
    WHERE rb.approved = 0
) UNION (
    SELECT rp.id, u.fullname AS user_name, rp.comment, rp.rating, rp.approved, p.name AS baslik, 'reviews_places' AS table_name
    FROM reviews_places rp 
    JOIN users u ON rp.user_id = u.id 
    JOIN places p ON rp.place_id = p.id
    WHERE rp.approved = 0
) UNION (
    SELECT rt.id, u.fullname AS user_name, rt.comment, rt.rating, rt.approved, t.name AS baslik, 'reviews_tours' AS table_name
    FROM reviews_tours rt 
    JOIN users u ON rt.user_id = u.id 
    JOIN tours t ON rt.tours_id = t.id
    WHERE rt.approved = 0
)
ORDER BY id DESC");

if (!$pending_reviews) {
    die("SQL Hatası: " . $conn->error);
}

// Yorum onaylama ve reddetme işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_review'])) {
        $review_id = intval($_POST['review_id']);
        $table = $_POST['table'];
        $conn->query("UPDATE $table SET approved = 1 WHERE id = $review_id");
    }
    if (isset($_POST['reject_review'])) {
        $review_id = intval($_POST['review_id']);
        $table = $_POST['table'];
        $conn->query("UPDATE $table SET approved = -1 WHERE id = $review_id");
    }
    header("Location: admin_reviews.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_blogs.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar-wrapper">
        <a href="#" class="sidebar-toggle" id="menu-toggle"><i class="fas fa-bars"></i></a>
        <a href="../admin.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="admin_blogs.php" class="active"><i class="fas fa-newspaper"></i> <span>Blog Yönetimi</span></a>
        <a href="admin_places.php"><i class="fas fa-map-marked-alt"></i> <span>Mekan Yönetimi</span></a>
        <a href="admin_cities.php"><i class="fas fa-city"></i> <span>Şehir Yönetimi</span></a>
        <a href="admin_countries.php"><i class="fas fa-globe"></i> <span>Ülke Yönetimi</span></a>
        <a href="admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Rezervasyonlar</span></a>
        <a href="admin_reviews.php"><i class="fas fa-comments"></i> <span>Yorumlar</span></a>
        <a href="admin_tours.php"><i class="fas fa-route"></i> <span>Turlar</span></a>
        <a href="admin_iletisim.php"><i class="fas fa-envelope"></i> <span>İletişim</span></a>
    </div>
    <!-- İçerik -->
    <div class="content">
        <nav class="navbar">
            <span class="navbar-text">Administrator</span>
            <a href="../cikis.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </nav>

        <div class="container mt-4">
            <h2 class="fw-bold">Yorum Yönetimi</h2>

            <!-- Onay Bekleyen Yorumlar -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">Onay Bekleyen Yorumlar</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>İçerik</th><th>Kullanıcı</th><th>Yorum</th><th>Puan</th><th>İşlemler</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending_reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['baslik']) ?></td>
                                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                                    <td><?= htmlspecialchars($row['comment']) ?></td>
                                    <td><?= str_repeat("★", $row['rating']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="review_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="table" value="<?= $row['table_name'] ?>">
                                            <button type="submit" name="approve_review" class="btn btn-success btn-sm">Onayla</button>
                                            <button type="submit" name="reject_review" class="btn btn-danger btn-sm">Reddet</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("menu-toggle").addEventListener("click", function () {
            document.getElementById("sidebar-wrapper").classList.toggle("collapsed");
        });
    </script>
</body>
</html>