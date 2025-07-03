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

// Ülkeleri çek
$sql = "SELECT * FROM countries ORDER BY name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şehir Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_blogs.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar-wrapper">
        <a href="#" class="sidebar-toggle" id="menu-toggle"><i class="fas fa-bars"></i></a>
        <a href="../admin.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="admin_blogs.php"><i class="fas fa-newspaper"></i> <span>Blog Yönetimi</span></a>
        <a href="admin_places.php"><i class="fas fa-map-marked-alt"></i> <span>Mekan Yönetimi</span></a>
        <a href="admin_cities.php" class="active"><i class="fas fa-city"></i> <span>Şehir Yönetimi</span></a>
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

        <div class="container mt-5">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="fw-bold">Ülke Yönetimi</h2>
                <a href="country_ekle.php" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Ülke Ekle</a>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Ülke Adı</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <a href="country_sil.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Bu ülkeyi silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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