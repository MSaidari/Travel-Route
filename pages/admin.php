<?php
session_start();
// Veritabanı bağlantısı
$host     = "localhost";
$dbname   = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Genel İstatistikler
$counts = [];
$queries = [
    'blogs'        => "SELECT COUNT(id) FROM blog_posts",
    'places'       => "SELECT COUNT(id) FROM places",
    'tours'        => "SELECT COUNT(id) FROM tours",
    'reservations' => "SELECT COUNT(id) FROM reservations WHERE status!='cancelled'",
    'users'        => "SELECT COUNT(id) FROM users",
    'comments'     => "SELECT (SELECT COUNT(id) FROM reviews_blogs) + (SELECT COUNT(id) FROM reviews_tours) + (SELECT COUNT(id) FROM reviews_places)"
];
foreach ($queries as $key => $sql) {
    $res = $conn->query($sql);
    $counts[$key] = $res->fetch_row()[0];
}

// Haftalık veriler (son 7 gün)
$labels = [];
$userWeek = [];
$resWeek  = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('D d M', strtotime($date));
    $userWeek[$date] = 0;
    $resWeek[$date]  = 0;
}

$sqlUsers = "SELECT DATE(created_at) AS d, COUNT(id) AS cnt
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY d";
$resultUsers = $conn->query($sqlUsers);
while ($row = $resultUsers->fetch_assoc()) {
    $userWeek[$row['d']] = intval($row['cnt']);
}

$sqlRes = "SELECT DATE(created_at) AS d, COUNT(id) AS cnt
           FROM reservations
           WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
           GROUP BY d";
$resultRes = $conn->query($sqlRes);
while ($row = $resultRes->fetch_assoc()) {
    $resWeek[$row['d']] = intval($row['cnt']);
}

// Son 3 rezervasyon
$sqlRecent = "SELECT r.id, u.fullname, t.name, r.status, r.created_at
              FROM reservations r
              JOIN users u ON r.user_id=u.id
              JOIN tours t ON r.tour_id=t.id
              ORDER BY r.created_at DESC LIMIT 3";
$recentReservations = $conn->query($sqlRecent);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform .2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 2.5rem; color: #007bff; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar-wrapper">
        <a href="#" class="sidebar-toggle" id="menu-toggle"><i class="fas fa-bars"></i></a>
        <a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="admin/admin_blogs.php"><i class="fas fa-newspaper"></i> <span>Blog Yönetimi</span></a>
        <a href="admin/admin_places.php"><i class="fas fa-map-marked-alt"></i> <span>Mekan Yönetimi</span></a>
        <a href="admin/admin_cities.php"><i class="fas fa-city"></i> <span>Şehir Yönetimi</span></a>
        <a href="admin/admin_countries.php"><i class="fas fa-globe"></i> <span>Ülke Yönetimi</span></a>
        <a href="admin/admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Rezervasyonlar</span></a>
        <a href="admin/admin_reviews.php"><i class="fas fa-comments"></i> <span>Yorumlar</span></a>
        <a href="admin/admin_tours.php"><i class="fas fa-route"></i> <span>Turlar</span></a>
        <a href="admin/admin_iletisim.php"><i class="fas fa-envelope"></i> <span>İletişim</span></a>
    </div>

    <!-- İçerik -->
    <div class="content">
        <nav class="navbar navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <span class="navbar-text">Administrator</span>
                <a href="cikis.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
            </div>
        </nav>

        <div class="container mt-4">
            <h2 class="fw-bold mb-4">Yönetim Paneli</h2>
            <div class="row g-4">
                <?php foreach (['blogs'=>'newspaper','places'=>'map-marker-alt','tours'=>'route','reservations'=>'calendar-check','users'=>'user','comments'=>'comments'] as $key=>$icon): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card stat-card shadow-sm p-3 text-center">
                        <i class="stat-icon fas fa-<?= $icon ?> mb-2"></i>
                        <h6 class="text-muted">Toplam <?= ucfirst($key) ?></h6>
                        <h3 class="fw-bold"><?= $counts[$key] ?></h3>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grafikler (Haftalık) -->
            <div class="row mt-5">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm p-3">
                        <h5>Haftalık Kullanıcı Kayıtları</h5>
                        <canvas id="usersWeekChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm p-3">
                        <h5>Haftalık Rezervasyonlar</h5>
                        <canvas id="resWeekChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Son 3 rezervasyon -->
            <div class="card shadow-sm p-3 mt-4">
                <h5>Son 3 Rezervasyon</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>ID</th><th>Kullanıcı</th><th>Tur</th><th>Durum</th><th>Tarih</th></tr></thead>
                        <tbody>
                        <?php while($rr = $recentReservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= $rr['id'] ?></td>
                                <td><?= htmlspecialchars($rr['fullname']) ?></td>
                                <td><?= htmlspecialchars($rr['name']) ?></td>
                                <td><?= ucfirst($rr['status']) ?></td>
                                <td><?= $rr['created_at'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const labels = <?= json_encode(array_values($labels)) ?>;
        const usersData = <?= json_encode(array_values($userWeek)) ?>;
        const resData = <?= json_encode(array_values($resWeek)) ?>;

        new Chart(document.getElementById('usersWeekChart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Kayıtlar', data: usersData, borderColor: '#007bff', fill: false }] },
            options: {
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        new Chart(document.getElementById('resWeekChart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Rezervasyon', data: resData, borderColor: '#28a745', fill: false }] },
            options: {
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        document.getElementById("menu-toggle").addEventListener("click", () => {
            document.getElementById("sidebar-wrapper").classList.toggle("collapsed");
        });
    </script>
</body>
</html>
