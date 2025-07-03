<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : "";
$tour_search_query = isset($_GET['tour_q']) ? trim($_GET['tour_q']) : "";

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// *** Şehir adına göre ID bulma (Eğer mekan veya blog aranıyorsa) ***
$city_id = null;
if (!empty($search_query)) {
    $sql_city = "SELECT id FROM cities WHERE name LIKE '%$search_query%' LIMIT 1";
    $result_city = $conn->query($sql_city);
    if ($result_city->num_rows > 0) {
        $city_id = $result_city->fetch_assoc()['id'];
    }
}

// *** Mekanları & Blogları Getir (Tek Kontrol) ***
$result_places = $result_blogs = null;
if (!empty($search_query)) {
    $sql_places = "
    SELECT p.id, p.name, p.address, p.photo, p.text_message, 
           COUNT(f.id) AS fav_count,
           AVG(r.rating) AS avg_rating
    FROM places p
    LEFT JOIN reviews_places r ON p.id = r.place_id AND r.rating IS NOT NULL
    LEFT JOIN favorite_places f ON p.id = f.place_id
    WHERE p.name LIKE '%$search_query%' OR p.text_message LIKE '%$search_query%'
";

    if ($city_id) {
        $sql_places .= " OR p.city_id = $city_id";
    }

    $sql_places .= " GROUP BY p.id ";

    if ($filter == 'most_rated') {
        $sql_places .= " ORDER BY avg_rating DESC ";
    } elseif ($filter == 'least_rated') {
        $sql_places .= " ORDER BY avg_rating ASC ";
    } elseif ($filter == 'most_liked') {
        $sql_places .= " ORDER BY fav_count DESC ";
    } else {
        $sql_places .= " ORDER BY p.id DESC ";
    }

    $result_places = $conn->query($sql_places);


    // Blog Yazıları
    $sql_blogs = "
    SELECT b.id, b.title, b.content, b.photo, 
           COUNT(f.id) AS fav_count,
           AVG(r.rating) AS avg_rating
    FROM blog_posts b
    LEFT JOIN reviews_blogs r ON b.id = r.blogs_id AND r.rating IS NOT NULL
    LEFT JOIN favorite_blogs f ON b.id = f.blog_id
    WHERE b.title LIKE '%$search_query%' OR b.content LIKE '%$search_query%'
";

    if ($city_id) {
        $sql_blogs .= " OR b.city_id = $city_id";
    }

    $sql_blogs .= " GROUP BY b.id ";

    if ($filter == 'most_rated') {
        $sql_blogs .= " ORDER BY avg_rating DESC ";
    } elseif ($filter == 'least_rated') {
        $sql_blogs .= " ORDER BY avg_rating ASC ";
    } elseif ($filter == 'most_liked') {
        $sql_blogs .= " ORDER BY fav_count DESC ";
    } else {
        $sql_blogs .= " ORDER BY b.id DESC ";
    }

    $result_blogs = $conn->query($sql_blogs);


    // Blog Yazıları
    $sql_tours = "SELECT t.id, t.title, t.description, t.photo 
    FROM tours t 
    LEFT JOIN reviews_blogs r ON b.id = r.blogs_id AND t.rating IS NOT NULL
    LEFT JOIN favorite_blogs f ON b.id = f.blog_id
    WHERE b.title LIKE '%$search_query%' OR b.content LIKE '%$search_query%'";
}

//  Kullanıcının girdiği metni iki formda hazırlıyoruz:
$raw = $conn->real_escape_string($search_query);
$encoded = htmlentities($search_query, ENT_COMPAT, 'UTF-8');

//  Sorguda ikisini de LIKE’lıyorüz:
$sql = "
    SELECT *
    FROM tours
    WHERE (
        name LIKE '%{$raw}%'
        OR description LIKE '%{$raw}%'
        OR description LIKE '%{$encoded}%'
    )
    AND start_date >= CURDATE()
";

$result_tours = $conn->query($sql);




?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - Gezi Rotası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="../css/arama.css" rel="stylesheet">
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
    <!-- Arama + Filtreleme Container -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 px-3 py-2 bg-light border rounded">

        <!-- Arama Kutusu -->
        <form action="arama.php" method="GET" class="d-flex align-items-center gap-2">
            <input type="text" name="q" placeholder="Ara" value="<?= htmlspecialchars($search_query); ?>"
                class="form-control" style="min-width: 250px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>

        <!-- Filtreleme Linkleri -->
        <div class="d-flex flex-wrap gap-2">
            <a href="arama.php?q=<?= $search_query ?>&tour_q=<?= $tour_search_query ?>&filter=most_rated"
                class="btn btn-outline-secondary <?= ($filter == 'most_rated') ? 'active' : '' ?>">En Çok Puan</a>
            <a href="arama.php?q=<?= $search_query ?>&tour_q=<?= $tour_search_query ?>&filter=least_rated"
                class="btn btn-outline-secondary <?= ($filter == 'least_rated') ? 'active' : '' ?>">En Az Puan</a>
            <a href="arama.php?q=<?= $search_query ?>&tour_q=<?= $tour_search_query ?>&filter=most_liked"
                class="btn btn-outline-secondary <?= ($filter == 'most_liked') ? 'active' : '' ?>">En Çok
                Favorilenenler</a>
        </div>

    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- MEKANLAR & BLOG YAZILARI -->
            <?php if (!empty($search_query) && (($result_places && $result_places->num_rows > 0) || ($result_blogs && $result_blogs->num_rows > 0))): ?>
                <div class="row">
                    <!-- Mekanlar Bölümü -->
                    <?php if ($result_places && $result_places->num_rows > 0): ?>
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
                                                    <p><?= substr($row['text_message'], 0, 100); ?>...</p>
                                                    <span class="place-location"><?= $row['address']; ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Blog Yazıları Bölümü -->
                    <?php if ($result_blogs && $result_blogs->num_rows > 0): ?>
                        <div class="col-lg-4">
                            <h3 class="mb-3">Blog Yazıları</h3>
                            <div class="blog-container">
                                <?php while ($blog = $result_blogs->fetch_assoc()): ?>
                                    <div class="blog-item">
                                        <img src="<?= $blog['photo']; ?>" alt="<?= $blog['title']; ?>">
                                        <div class="blog-text">
                                            <h3><?= $blog['title']; ?></h3>
                                            <p><?= substr($blog['content'], 0, 100); ?>...</p>
                                            <a href="blog.php?id=<?= $blog['id']; ?>" class="read-more">Devamını Oku</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <!-- Turlar Bölümü -->
            <?php if ($result_tours && $result_tours->num_rows > 0): ?>
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="mb-3">Turlar</h3>
                        <div class="row">
                            <?php while ($tour = $result_tours->fetch_assoc()): ?>
                                <div class="col-md-4 mb-4">
                                    <a href="tur.php?id=<?= $tour['id'] ?>" class="place-card-link">
                                        <div class="place-card">
                                            <img src="<?= htmlspecialchars($tour['photo']) ?>"
                                                alt="<?= htmlspecialchars($tour['name']) ?>">
                                            <div class="place-info">
                                                <h5 class="place-title"><?= htmlspecialchars($tour['name']) ?></h5>
                                                <p><?= mb_substr(strip_tags($tour['description']), 0, 100) ?>…</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>