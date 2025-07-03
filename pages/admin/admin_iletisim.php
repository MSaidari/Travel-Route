<?php
session_start();
$host     = "localhost";
$dbname   = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Tüm iletişim mesajlarını çek (kullanıcı adıyla birlikte)
$sql = "
    SELECT 
      c.id,
      c.name,
      c.subject,
      c.message,
      c.user_id,
      u.fullname AS user_name
    FROM communication c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.id DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>İletişim Mesajları</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
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
    <nav class="navbar navbar-light mb-4">
      <span class="navbar-text">Administrator</span>
      <a href="../cikis.php" class="btn btn-outline-danger">
        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
      </a>
    </nav>

    <div class="container-fluid">
      <h2 class="fw-bold mb-4">Gelen İletişim Mesajları</h2>
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Kullanıcı</th>
                <th>İsim</th>
                <th>Konu</th>
                <th>Mesaj</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['user_name'] ?: '—') ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['subject']) ?></td>
                  <td>
                    <span class="msg-snippet"
                          data-fulltext="<?= htmlspecialchars($row['message']) ?>">
                      <?= htmlspecialchars(mb_substr($row['message'], 0, 50)) ?>...
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="5" class="text-center py-3">Kayıtlı mesaj yok.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Mesaj Detay Modal -->
  <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Mesaj Detayı</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalMessage"></div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    document.querySelector('.sidebar-toggle').addEventListener('click', () =>
      document.getElementById('sidebar-wrapper').classList.toggle('collapsed')
    );

    // Mesaj snippet tıklama → modal
    document.querySelectorAll('.msg-snippet').forEach(el => {
      el.addEventListener('click', () => {
        document.getElementById('modalMessage').innerText = el.dataset.fulltext;
        new bootstrap.Modal(document.getElementById('messageModal')).show();
      });
    });
  </script>
</body>
</html>
