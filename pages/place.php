<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password, $dbname);

// Bağlantı hatası kontrolü
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// *Admin kontrolü* (Varsayılan olarak role = 1 olanlar admin)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// GET parametresi ile mekan ID'sini al
$place_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ID kontrolü
if ($place_id == 0) {
    die("Hatalı veya eksik ID değeri!");
}

// Mekan detaylarını çek
$query = $conn->prepare("
    SELECT places.name, places.description, places.text_message, places.address, places.photo, 
           places.latitude, places.longitude, cities.id AS city_id, cities.name AS city_name, 
           countries.name AS country_name
    FROM places
    JOIN cities ON places.city_id = cities.id
    JOIN countries ON cities.country_id = countries.id
    WHERE places.id = ?
");
$query->bind_param("i", $place_id);
$query->execute();
$result = $query->get_result();
$place = $result->fetch_assoc();
$query->close();

if (!$place) {
    die("Böyle bir mekan bulunamadı.");
}
$latitude = $place['latitude'];
$longitude = $place['longitude'];

// Aynı şehre ait diğer mekanları çek 
$query_places = $conn->prepare("
    SELECT id, name, photo FROM places WHERE city_id = ? AND id != ? LIMIT 3
");
$query_places->bind_param("ii", $place['city_id'], $place_id);
$query_places->execute();
$result_places = $query_places->get_result();

// Aynı şehre ait blog yazılarını çek 
$query_blogs = $conn->prepare("
    SELECT id, title, photo, content FROM blog_posts WHERE city_id = ? LIMIT 3
");
$query_blogs->bind_param("i", $place['city_id']);
$query_blogs->execute();
$result_blogs = $query_blogs->get_result();

// Veritabanındaki galeriyi çek
$query_gallery = $conn->prepare("SELECT url FROM gallery WHERE place_id = ?");
$query_gallery->bind_param("i", $place_id);
$query_gallery->execute();
$result_gallery = $query_gallery->get_result();
$gallery_photos = [];
while ($row = $result_gallery->fetch_assoc()) {
    $gallery_photos[] = $row['url'];
}
$query_gallery->close();

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/GeziRotasi/";

//Galeri dizininin gerçek yolu
$gallery_dir_base = realpath(__DIR__ . "/../images/gallery/");
$folder_photos = [];

if ($gallery_dir_base && is_dir($gallery_dir_base)) {
    // Tüm klasörleri al
    $all_folders = scandir($gallery_dir_base);

    foreach ($all_folders as $folder) {
        if ($folder !== "." && $folder !== "..") {
            // Mekan ID'si ile eşleşen klasörü bul
            $folder_parts = explode("_", $folder);
            if ($folder_parts[0] == $place_id) {
                $full_path = $gallery_dir_base . DIRECTORY_SEPARATOR . $folder;
                $web_path = "images/gallery/" . $folder; // Web yolu

                if (is_dir($full_path) && is_readable($full_path)) {
                    $files = scandir($full_path);

                    foreach ($files as $file) {
                        if ($file !== "." && $file !== "..") {
                            $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                            if (in_array($file_ext, $valid_extensions)) {
                                // Tarayıcıda doğru yolu kullan (base_url + web yolu)
                                $folder_photos[] = $base_url . $web_path . "/" . $file;
                            }
                        }
                    }
                }
                break;
            }
        }
    }
}
// Yorum ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    $user_id = $_SESSION['user_id'] ?? null; // Kullanıcı giriş yapmış mı kontrol et
    $comment_text = trim($_POST['comment']);
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : NULL;

    if ($user_id && !empty($comment_text)) {
        $query_comment = $conn->prepare("INSERT INTO reviews_places (place_id, user_id, rating, comment, created_at, approved) VALUES (?, ?, ?, ?, NOW(), 0)");

        if (!$query_comment) {
            die("SQL Hatası: " . $conn->error);
        }

        $query_comment->bind_param("iiis", $place_id, $user_id, $rating, $comment_text);
        $query_comment->execute();
        $query_comment->close();

        // Yönlendirme ile form tekrar gönderimini engelle
        header("Location: place.php?id=" . $place_id);
        exit();
    }
}

// Admin yorumu onaylama işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_comment']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    $comment_id = intval($_POST['comment_id']);
    $query_approve = $conn->prepare("UPDATE reviews_places SET approved = 1 WHERE id = ?");
    $query_approve->bind_param("i", $comment_id);
    $query_approve->execute();
    $query_approve->close();

    // Yönlendirme ile sayfa yenile
    header("Location: place.php?id=" . $place_id);
    exit();
}

// Admin yorumu reddetme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_comment']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    $comment_id = intval($_POST['comment_id']);
    $query_reject = $conn->prepare("UPDATE reviews_places SET approved = -1 WHERE id = ?");
    $query_reject->bind_param("i", $comment_id);
    $query_reject->execute();
    $query_reject->close();

    // Yönlendirme ile sayfa yenile
    header("Location: place.php?id=" . $place_id);
    exit();
}

// Yorum silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id) {
        $query_delete = $conn->prepare("DELETE FROM reviews_places WHERE id = ? AND user_id = ?");
        $query_delete->bind_param("ii", $comment_id, $user_id);
        $query_delete->execute();
        $query_delete->close();

        // Yönlendirme ile sayfa yenile
        header("Location: place.php?id=" . $place_id);
        exit();
    }
}

// Mevcut onaylanmış yorumları çek
$query_comments = $conn->prepare("SELECT r.id, u.fullname, r.rating, r.comment, r.created_at, r.user_id, r.approved FROM reviews_places r JOIN users u ON r.user_id = u.id WHERE r.place_id = ? AND r.approved = 1 ORDER BY r.created_at DESC");
$query_comments->bind_param("i", $place_id);
$query_comments->execute();
$result_comments = $query_comments->get_result();

$lat = $place['latitude'];
$lon = $place['longitude'];
// 1) Nominatim ile ters‑geokodlama: ilçeyi (county) bul
$nomUrl = "https://nominatim.openstreetmap.org/reverse?"
    . "lat={$latitude}&lon={$longitude}&format=json&addressdetails=1";
$nomRes = @file_get_contents($nomUrl);
$nom = $nomRes ? json_decode($nomRes, true) : [];
$district = $nom['address']['county']
    ?? $nom['address']['city_district']
    ?? $place['city_name'];

// 2) Open‑Meteo’dan hem saatlik hem günlük veri çek
$weatherUrl = "https://api.open-meteo.com/v1/forecast?"
    . "latitude={$latitude}&longitude={$longitude}"
    . "&hourly=temperature_2m,precipitation_probability,weathercode"
    . "&daily=temperature_2m_max,temperature_2m_min,weathercode"
    . "&timezone=Europe/Istanbul";
$weatherJson = @file_get_contents($weatherUrl);
$weather = $weatherJson ? json_decode($weatherJson, true) : [];

// 3) İkon eşlemesi (weathercode → FontAwesome)
$iconMap = [
    0 => 'fa-sun',
    1 => 'fa-cloud-sun',
    2 => 'fa-cloud-sun',
    3 => 'fa-cloud',
    45 => 'fa-smog',
    48 => 'fa-smog',
    51 => 'fa-cloud-drizzle',
    53 => 'fa-cloud-drizzle',
    55 => 'fa-cloud-drizzle',
    61 => 'fa-cloud-showers-heavy',
    63 => 'fa-cloud-showers-heavy',
    65 => 'fa-cloud-showers-heavy',
    71 => 'fa-snowflake',
    73 => 'fa-snowflake',
    75 => 'fa-snowflake',
    80 => 'fa-cloud-showers-heavy',
    81 => 'fa-cloud-showers-heavy',
    82 => 'fa-cloud-showers-heavy',
    95 => 'fa-bolt',
    96 => 'fa-bolt',
    99 => 'fa-bolt'
];

// kolaylık için verileri değişkenlere ata
$hourly_times = $weather['hourly']['time'] ?? [];
$hourly_temp = $weather['hourly']['temperature_2m'] ?? [];
$hourly_prec = $weather['hourly']['precipitation_probability'] ?? [];
$hourly_code = $weather['hourly']['weathercode'] ?? [];

$daily_times = $weather['daily']['time'] ?? [];
$daily_max = $weather['daily']['temperature_2m_max'] ?? [];
$daily_min = $weather['daily']['temperature_2m_min'] ?? [];
$daily_code = $weather['daily']['weathercode'] ?? [];

$user_id = $_SESSION['user_id'] ?? null;
$isLiked = false;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM favorite_places WHERE user_id = ? AND place_id = ?");
    $stmt->bind_param("ii", $user_id, $place_id);
    $stmt->execute();
    $stmt->store_result();
    $isLiked = $stmt->num_rows > 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($place['name']) ?> - Gezi Rotası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="stylesheet" href="../css/place.css">
</head>

<body>
    <?php if (!$isAdmin): ?>
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
    <?php else: ?>
        <div class="preview-mode">
            Bu sayfa admin tarafından yalnızca önizleme modunda görüntülenmektedir.
        </div>
    <?php endif; ?>

    <!-- İçerik Alanı -->
    <div class="container mt-4">
        <div class="row">
            <!-- Sol Taraf: Mekan Detayları -->
            <div class="col-lg-8">
                <div class="place-box">
                    <img src="<?= htmlspecialchars($place['photo']) ?>" alt="<?= htmlspecialchars($place['name']) ?>">
                    <!-- Fotoğrafın hemen altı -->
                    <div class="breadcrumb-like-container">
                        <!-- Sol tarafta: yol gösterici bağlantılar -->
                        <div class="breadcrumb-container">
                            <a href="index.php"><i class="fas fa-home"></i></a>
                            <span>/</span>
                            <a href="#"><?= htmlspecialchars($place['country_name']) ?></a>
                            <span>/</span>
                            <a
                                href="sehir_detay.php?id=<?= $place['city_id'] ?>"><?= htmlspecialchars($place['city_name']) ?></a>
                            <span>/</span>
                            <span><?= htmlspecialchars($place['name']) ?></span>
                        </div>

                        <!-- Sağ tarafta: beğeni butonu -->
                        <div class="like-place-container">
                            <?php if ($user_id): ?>
                                <button id="likePlaceBtn" data-place="<?= $place_id ?>"
                                    data-liked="<?= $isLiked ? 'true' : 'false' ?>" class="btn-like-place">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php else: ?>
                                <a href="giris.php" title="Beğenmek için giriş yapın">
                                    <i class="fas fa-heart btn-like-place inactive"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2 class="mt-3"><?= htmlspecialchars($place['name']) ?></h2>
                    <p><?= html_entity_decode($place['description']) ?></p>
                    <div class="map-container text-center mt-5">
                        <h4>📍 Konum</h4>
                        <iframe width="100%" height="450" style="border:0" loading="lazy" allowfullscreen
                            src="https://www.google.com/maps?q=<?= $latitude ?>,<?= $longitude ?>&hl=tr&z=14&output=embed">
                        </iframe>
                    </div>
                    <!-- 5 Günlük + Saatlik Hava Tahmini -->
                    <div class="forecast-container">
                        <h4>📅 5 Günlük Hava Tahmini — <?= htmlspecialchars($district) ?></h4>

                        <!-- Saatlik (ilk 12 saati gösterelim) -->
                        <div class="hourly-forecast">
                            <?php for ($i = 0; $i < 12 && isset($hourly_times[$i]); $i++):
                                $t = date("H:i", strtotime($hourly_times[$i]));
                                $tmp = round($hourly_temp[$i]);
                                $pc = $hourly_prec[$i];
                                $code = $hourly_code[$i];
                                $icon = $iconMap[$code] ?? 'fa-question';
                                ?>
                                <div class="hour-card">
                                    <div class="hour-time"><?= $t ?></div>
                                    <i class="fas <?= $icon ?> weather-icon"></i>
                                    <div class="hour-temp"><?= $tmp ?>°C</div>
                                    <div class="hour-prec"><?= $pc ?>%</div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Günlük (5 gün) -->
                        <div class="daily-forecast">
                            <?php for ($i = 0; $i < 5 && isset($daily_times[$i]); $i++):
                                $day = date("D d M", strtotime($daily_times[$i]));
                                $max = round($daily_max[$i]);
                                $min = round($daily_min[$i]);
                                $code = $daily_code[$i];
                                $icon = $iconMap[$code] ?? 'fa-question';
                                ?>
                                <div class="day-card">
                                    <div class="day-name"><?= $day ?></div>
                                    <i class="fas <?= $icon ?> weather-icon"></i>
                                    <div class="day-temps">
                                        <span class="max"><?= $max ?>°</span> /
                                        <span class="min"><?= $min ?>°</span>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ Taraf: Önerilen Mekanlar ve Blog Yazıları -->
            <div class="col-lg-4">
                <div class="recommendation-box">
                    <div class="recommendation-title">Önerilen Mekanlar</div>
                    <div class="recommendation-container">
                        <?php while ($rec_place = $result_places->fetch_assoc()): ?>
                            <div class="recommendation-item">
                                <a href="place.php?id=<?= $rec_place['id'] ?>">
                                    <img src="<?= htmlspecialchars($rec_place['photo']) ?>"
                                        alt="<?= htmlspecialchars($rec_place['name']) ?>">
                                    <?= htmlspecialchars($rec_place['name']) ?>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="recommendation-box ">
                    <div class="recommendation-title">Önerilen Blog Yazıları</div>
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
                <!--Galeri Bölümü -->
                <div class="gallery-container">
                    <div class="gallery-title">📸 Mekan Galerisi</div>
                    <div class="gallery-grid">
                        <?php if (!empty($all_photos) || !empty($folder_photos)): ?>

                            <!--Veritabanındaki fotoğraflar -->
                            <?php foreach ($all_photos as $photo): ?>
            <img src="<?= htmlspecialchars($photo) ?>" alt="Mekan Fotoğrafı"
                 onerror="this.style.display='none';"
                 onclick="openLightbox('<?= htmlspecialchars($photo) ?>')">
          <?php endforeach; ?>

                            <!--Dosya sistemindeki fotoğraflar -->
                            <?php foreach ($folder_photos as $photo): ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="Mekan Fotoğrafı"
                                    onerror="this.style.display='none';"
                                    onclick="openLightbox('<?= htmlspecialchars($photo) ?>')">
                            <?php endforeach; ?>

                        <?php else: ?>
                            <p>📌 Bu mekana ait galeri fotoğrafı bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Yorum Bölümü -->
                <div class="comments-container"
                    style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
                    <h3 style="color: #333; font-weight: bold;"> <?= $result_comments->num_rows ?> Yorum</h3>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="" style="margin-bottom: 20px;">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Yorumunuz:</label>
                                <!-- Yorum Puanlama Bölümü -->
                                <div class="rating-stars">
                                    <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                                    <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                                    <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                                    <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                                    <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                                </div>
                                <textarea name="comment" class="form-control" rows="3" required
                                    style="border-radius: 5px;"></textarea>
                            </div>
                            <button type="submit" name="submit_comment" class="btn btn-primary">Gönder</button>
                        </form>
                    <?php else: ?>
                        <p>Yorum yapabilmek için <a href="giris.php">giriş yapın</a>.</p>
                    <?php endif; ?>

                    <div class="comment-list">
                        <?php while ($comment = $result_comments->fetch_assoc()): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div>
                                        <strong style="color: #333; font-size: 16px;">
                                            <?= htmlspecialchars($comment['fullname']) ?> dedi ki:</strong>
                                        <small style="color: gray; display: block;">
                                            <?= date("d F Y, H:i", strtotime($comment['created_at'])) ?> </small>
                                    </div>
                                    <div class="user-rating">Puan: <?= str_repeat("★", $comment['rating']) ?>
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <button type="submit" name="delete_comment" class="delete-button">Sil</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p style="margin-top: 5px; font-size: 14px;">
                                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fotoğraf Büyütme Lightbox -->
    <div class="lightbox" id="lightbox">
        <span class="close-btn" onclick="closeLightbox()">✖</span>
        <img id="lightbox-img">
    </div>

    <script>
        function openLightbox(imageSrc) {
            document.getElementById("lightbox-img").src = imageSrc;
            document.getElementById("lightbox").style.display = "flex";
        }

        function closeLightbox() {
            document.getElementById("lightbox").style.display = "none";
        }

        document.addEventListener("DOMContentLoaded", function () {
            const btn = document.getElementById("likePlaceBtn");
            if (!btn) return;

            btn.classList.toggle("liked", btn.dataset.liked === "true");

            btn.addEventListener("click", function () {
                const placeId = btn.dataset.place;
                const action = btn.dataset.liked === "true" ? "unlike" : "like";

                fetch("favorite_place.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `place_id=${placeId}&action=${action}`
                })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            const nowLiked = action === "like";
                            btn.dataset.liked = nowLiked;
                            btn.classList.toggle("liked", nowLiked);
                        } else {
                            alert("İşlem sırasında hata: " + json.error);
                        }
                    })
                    .catch(err => console.error(err));
            });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>