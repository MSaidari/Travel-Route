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


$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'confirmed', 'cancelled'])) {
    $statusFilter = 'pending';
}

// Verileri çek
$sql = "
  SELECT 
    r.id,
    r.tour_id,
    t.name           AS tour_name,
    u.fullname       AS user_name,
    r.created_at,
    r.departure_point,
    r.adult,
    r.child,
    r.status,
    r.reservation_start_date, -- BURAYI EKLİYORUZ
    p.amount         AS payment_amount
  FROM reservations r
  JOIN users u ON r.user_id = u.id
  JOIN tours t ON r.tour_id = t.id
  LEFT JOIN payments p ON p.reservation_id = r.id
  WHERE r.status = ?
  ORDER BY t.name, r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $statusFilter);
$stmt->execute();
$result = $stmt->get_result();

// Bütün verileri diziye aktar
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
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

        <!-- Sekmeler -->
        <div class="container mt-4">
            <h2>Rezervasyon Yönetimi</h2>

            <div class="mb-3">
                <?php foreach (['pending' => 'Bekleyen', 'confirmed' => 'Onaylanan', 'cancelled' => 'İptal Edilen'] as $key => $label): ?>
                    <a href="?status=<?= $key ?>"
                        class="btn tab-btn <?= $statusFilter == $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($rows)): ?>
                <div class="alert alert-info">Bu durumda hiçbir kayıt yok.</div>
            <?php else: ?>
                <?php
                $currentTour = null;
                foreach ($rows as $r):
                    // Eğer rezervasyon tarihi geçmişse gösterme
                    if (isset($r['reservation_start_date']) && strtotime($r['reservation_start_date']) < strtotime(date('Y-m-d'))) {
                        continue;
                    }

                    if ($currentTour !== $r['tour_name']):
                        // Eğer daha önce tablo açıksa kapat
                        if ($currentTour !== null) {
                            echo "</tbody></table>";
                        }
                        $currentTour = $r['tour_name'];
                        ?>
                        <div class="tour-group">
                            <?= htmlspecialchars($currentTour) ?>
                            <small>(<?= date("d.m.Y", strtotime($r['reservation_start_date'])) ?>)</small>
                        </div>
                        <table class="table table-sm mb-4">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Kullanıcı</th>
                                    <th>Oluşturma</th>
                                    <th>Kalkış Noktası</th>
                                    <th>Yetişkin</th>
                                    <th>Çocuk</th>
                                    <th>Tutar</th> <!-- yeni ekledik -->
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            </thead>
                            <tbody>
                            <?php endif; ?>

                            <tr>
                                <td><?= $r['id'] ?></td>
                                <td><?= htmlspecialchars($r['user_name']) ?></td>
                                <td><?= $r['created_at'] ?></td>
                                <td><?= htmlspecialchars($r['departure_point']) ?></td>
                                <td><?= $r['adult'] ?></td>
                                <td><?= $r['child'] ?></td>
                                <td>
                                    <?= $r['payment_amount'] !== null ? number_format($r['payment_amount'], 2) . " ₺" : "Ödeme Yok" ?>
                                </td>
                                <td>
                                    <?php if ($statusFilter === 'pending'): ?>
                                        <form method="POST" action="update_reservation.php" class="d-flex gap-1">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button name="status" value="confirmed" class="btn btn-sm btn-success">Onayla</button>
                                            <button name="status" value="cancelled" class="btn btn-sm btn-danger">İptal Et</button>
                                        </form>
                                    <?php else: ?>
                                        <em><?= ucfirst($r['status']) ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>


        <script>
            document.getElementById("menu-toggle").addEventListener("click", function () {
                document.getElementById("sidebar-wrapper").classList.toggle("collapsed");
            });
        </script>
</body>

</html>