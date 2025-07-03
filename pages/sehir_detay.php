<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$city_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql_city = "SELECT name FROM cities WHERE id = $city_id";
$result_city = $conn->query($sql_city);
$city_name = ($result_city->num_rows > 0) ? $result_city->fetch_assoc()['name'] : "Bilinmeyen Şehir";

$sql_blogs = "SELECT id, title, content, photo FROM blog_posts WHERE city_id = $city_id ORDER BY id DESC";
$result_blogs = $conn->query($sql_blogs);

$sql_places = "SELECT id, name, address, photo, text_message FROM places WHERE city_id = $city_id";
$result_places = $conn->query($sql_places);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $city_name; ?> - Gezilecek Yerler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="../css/sehir_detay.css" rel="stylesheet">
</head>

<body>
<!-- Header -->
<div class="header">
        <div class="logo">
            <a href="anasayfa.php">
                <img src="../images/logo.jpg" alt="Gezi Rotası">
            </a>
        </div>
        <div class="nav-links">
            <a href="anasayfa.php">ANASAYFA</a>
            <a href="iletisim.php">İLETİŞİM</a>
        </div>

        <!-- Kullanıcı İkonu -->
        <div class="user-icon" id="userIcon">
            <?php if (isset($_SESSION['user_id'])): ?>
                <i class="fas fa-user-circle" style="color: #555; font-size: 30px; cursor:pointer;"></i>
                <div class="user-menu" id="userMenu">
                    <a href="profil.php">Profil</a>
                    <a href="anasayfa.php?logout=true">Çıkış Yap</a> <!-- Çıkış butonu -->
                </div>
            <?php else: ?>
                <a href="giris.php"><i class="fas fa-user-circle" style="color: #555; font-size: 30px;"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let userIcon = document.getElementById("userIcon");
            let userMenu = document.getElementById("userMenu");

            if (userIcon && userMenu) {
                userIcon.addEventListener("click", function (event) {
                    userMenu.style.display = (userMenu.style.display === "block") ? "none" : "block";
                    event.stopPropagation();
                });

                document.addEventListener("click", function (event) {
                    if (!userIcon.contains(event.target)) {
                        userMenu.style.display = "none";
                    }
                });
            }
        });
    </script>
    <!-- Başlık -->
    <div class="container mt-4">
        <h1 class="text-center fw-bold"><?php echo $city_name; ?></h1>
    </div>

    <div class="container mt-4">
    <div class="row">
        <!-- Mekanlar (Sol Taraf) -->
        <div class="col-lg-8">
            <h3 class="mb-3">Mekanlar</h3>
            <div class="row">
                <?php while ($row = $result_places->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <a href="place.php?id=<?= $row['id']; ?>" class="place-card-link">
                            <div class="place-card">
                                <img src="<?= $row['photo']; ?>" alt="<?= $row['name']; ?>">
                                <div class="place-info">
                                    <h5 class="place-title"><?= $row['name']; ?></h5>
                                    <p><?= $row['text_message']; ?></p>
                                    <span class="place-location"><?= $row['address']; ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Blog Yazıları (Sağ Taraf) -->
        <div class="col-lg-4">
            <h3 class="mb-3">Blog Yazıları</h3>
            <div class="blog-container">
                <?php while ($blog = $result_blogs->fetch_assoc()): ?>
                    <div class="blog-item">
                        <img src="<?= $blog['photo']; ?>" alt="<?= $blog['title']; ?>">
                        <div class="blog-text">
                            <h3><?= $blog['title']; ?></h3>
                            <p><?= substr($blog['content'], 0, 200); ?>...</p>
                            <a href="blog.php?id=<?= $blog['id']; ?>" class="read-more">Devamını Oku</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

</body>

</html>
