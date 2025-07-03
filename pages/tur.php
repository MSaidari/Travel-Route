<?php
session_start();
$conn = new mysqli("localhost", "root", "12345678", "gezirotasi");
$conn->set_charset("utf8mb4");

// *Admin kontrolü* (Varsayılan olarak role = 1 olanlar admin)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Tur ID'si
$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Tur bilgilerini al
$sql = "SELECT t.name,t.description,t.price,t.capacity, t.start_date, t.end_date, t.departure_info,t.services, t.transport, c.name city_name
FROM tours t
JOIN cities c ON t.departure_city_id = c.id
WHERE t.id = $tour_id";
$result = $conn->query($sql);
$tour = $result->fetch_assoc();

// Galeri fotoğraflarını çek
$gallery_sql = "SELECT url FROM gallery_tours WHERE tour_id = $tour_id";
$gallery_result = $conn->query($gallery_sql);
$gallery_images = [];
while ($row = $gallery_result->fetch_assoc()) {
    $gallery_images[] = $row['url'];
}

// Kullanıcı turu beğenmiş mi kontrolü
$isFavorited = false;
if (isset($_SESSION['user_id'])) {
    $check_fav = $conn->prepare("SELECT id FROM favorite_tours WHERE user_id = ? AND tours_id = ?");
    $check_fav->bind_param("ii", $user_id, $tour_id);
    $check_fav->execute();
    $check_result = $check_fav->get_result();
    if ($check_result->num_rows > 0) {
        $isFavorited = true;
    }
    $check_fav->close();
}


// Tarih formatı ve süre hesabı
$start = new DateTime($tour['start_date']);
$end = new DateTime($tour['end_date']);
$interval = $start->diff($end);

// Gün/gece hesabı
$days = $interval->days + 1;
$nights = $days - 1;

// Türkçe tarih gösterimi
setlocale(LC_TIME, 'tr_TR.UTF-8');
$startFormatted = $start->format('j M y'); // Örnek: 4 Haz'25
$endFormatted = $end->format('j M y');

// Kullanıcının bu turu beğenip beğenmediğini kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'toggle_favorite') {
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                // ↓ EKLENDİ: POST içinde yoksa GET['id'] al
                $tour_id = isset($_POST['tour_id'])
                    ? intval($_POST['tour_id'])
                    : intval($_GET['id']);
                $check = $conn->prepare("SELECT id FROM favorite_tours WHERE user_id = ? AND tours_id = ?");
                $check->bind_param("ii", $user_id, $tour_id);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $del = $conn->prepare("DELETE FROM favorite_tours WHERE user_id = ? AND tours_id = ?");
                    $del->bind_param("ii", $user_id, $tour_id);
                    $del->execute();
                    echo json_encode(["status" => "unliked"]);
                } else {
                    $add = $conn->prepare("INSERT INTO favorite_tours (user_id, tours_id) VALUES (?, ?)");
                    $add->bind_param("ii", $user_id, $tour_id);
                    $add->execute();
                    echo json_encode(["status" => "liked"]);
                }
            } else {
                echo json_encode(["status" => "login_required"]);
            }
            exit();
        }

    }
    // Yorum silme işlemi (tur sayfası)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']); // Yorum ID'si
        $user_id = $_SESSION['user_id'] ?? null;    // Kullanıcı oturumu var mı?

        if ($user_id) {
            // Yorum gerçekten bu kullanıcıya mı ait?
            $query_check = $conn->prepare("SELECT id FROM reviews_tours WHERE id = ? AND user_id = ?");
            $query_check->bind_param("ii", $comment_id, $user_id);
            $query_check->execute();
            $result_check = $query_check->get_result();

            if ($result_check->num_rows > 0) {
                // Sahiplik doğrulandıysa yorumu sil
                $query_delete = $conn->prepare("DELETE FROM reviews_tours WHERE id = ?");
                $query_delete->bind_param("i", $comment_id);
                $query_delete->execute();
                $query_delete->close();

                // Başarıyla silindi -> sayfayı yenile
                header("Location: tur.php?id=" . $tour_id . "&deleted=1");
                exit();
            }
        }
    }
}

// Yorum gönderme
if (isset($_POST['submit_comment'])) {
    if (isset($_SESSION['user_id'])) {
        $comment = trim($_POST['comment']);
        $rating = intval($_POST['rating']);
        if (!empty($comment) && $rating > 0) {
            $add_comment = $conn->prepare("INSERT INTO reviews_tours (tours_id, user_id, rating, comment, created_at, approved) VALUES (?, ?, ?, ?, NOW(), 0)");
            $add_comment->bind_param("iiis", $tour_id, $user_id, $rating, $comment);
            $add_comment->execute();
            header("Location: tur.php?id=$tour_id&success=1");
            exit();
        }
    }
}

// Yorumları çek
$yorumlar = $conn->prepare("
    SELECT r.id, r.comment, r.rating, r.created_at, u.fullname, r.user_id
    FROM reviews_tours r
    JOIN users u ON r.user_id = u.id
    WHERE r.tours_id = ? AND r.approved = 1
    ORDER BY r.created_at DESC
");
$yorumlar->bind_param("i", $tour_id);
$yorumlar->execute();
$yorum_sonuc = $yorumlar->get_result();

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Likya Turu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script src="    https://fontawesome.com/icons/van-shuttle?f=classic&s=light"></script>
    <link href="../css/tur.css" rel="stylesheet">

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
    <div class="top-section">
        <!-- Fotoğraf galerisi -->
        <div class="gallery-wrapper">
            <div class="gallery-left">
                <?php if (!empty($gallery_images)): ?>
                    <img src="<?= htmlspecialchars($gallery_images[0]) ?>" alt="tur foto">
                <?php endif; ?>
            </div>
            <div class="gallery-right">
                <?php for ($i = 1; $i < count($gallery_images); $i++): ?>
                    <img src="<?= htmlspecialchars($gallery_images[$i]) ?>" alt="tur foto">
                <?php endfor; ?>
            </div>
        </div>

        <!-- Bilgi kutusu -->
        <div class="details">
            <h1><?= htmlspecialchars($tour['name']) ?></h1>
            <div style="background: #fff; padding: 20px; border-radius: 10px; font-family: Arial; max-width: 400px;">
                <p>📍 <strong><?= htmlspecialchars($tour['city_name']) ?></strong> Hareketli</p>
                <p>📅 <?= $startFormatted ?> - <?= $endFormatted ?> arası</p>
                <p>🌙 <strong><?= $nights ?> Gece <?= $days ?> Gün</strong></p>
                <p><i class="fa-light fa-van-shuttle"></i><strong>Gidiş-Dönüş:</strong>
                    <?= htmlspecialchars($tour['transport']) ?></p>
            </div>
            <div style="text-align: right; margin-top:10px;">
                <button id="likeButton" class="like-button" data-liked="<?= $isFavorited ? 'true' : 'false' ?>"
                    data-tour="<?= $tour_id ?>" style="color: <?= $isFavorited ? 'red' : 'gray' ?>">
                    <i class="fas fa-heart"></i>
                </button>
            </div>



        </div>
    </div>

    <div class="tab">
        <div class="tab-menu">
            <div class="tab-item active" onclick="showTab(0)">Tur Programı</div>
            <div class="tab-item" onclick="showTab(1)">Fiyatlar & Tarih</div>
            <div class="tab-item" onclick="showTab(2)">Hizmetler</div>
            <div class="tab-item" onclick="showTab(3)">Tur Kalkış Noktaları</div>
            <div class="tab-item" onclick="showTab(4)">Rezervasyon Yap</div>
        </div>

        <div class="tab-content active">
            <h3>Tur Programı</h3>
            <!-- Yeni hali -->
            <p><?= $tour['description'] ?></p>
        </div>

        <div class="tab-content">
            <h3>Fiyatlar & Tarih</h3>
            <p><strong>YETİŞKİN: </strong><?= htmlspecialchars($tour['price']) ?></p>
            <p><strong>ÇOCUK: </strong><?= htmlspecialchars($tour['price'] / 2) ?>(%50 indirim)</p>
            <p>Bankanıza bağlı olarak farklı kampanyalardan yararlanabilirsiniz.</p>
        </div>

        <div class="tab-content">
            <h3>Hizmetler</h3>
            <p><?= $tour['services'] ?></p>
        </div>

        <div class="tab-content">
            <h3>Tur Kalkış Noktaları</h3>
            <p><?= $tour['departure_info'] ?></p>
        </div>
        <div class="tab-content">
            <h3>Rezervasyon Yap</h3>
            <div class="reservation-bar">
                <div class="select-box">
                    <label><i class="fas fa-location-dot"></i> HAREKET NOKTASI</label>
                    <select id="departureCity">
                        <?php
                        // departure_info içinden <li> öğelerini ayıklama
                        preg_match_all('/<li>(.*?)<\/li>/', $tour['departure_info'], $matches);
                        foreach ($matches[1] as $departure) {
                            echo '<option value="' . htmlspecialchars($departure) . '">' . htmlspecialchars(html_entity_decode($departure)) . '</option>';

                        }
                        ?>
                    </select>

                </div>

                <!-- ODA VE KİŞİ SAYISI -->
                <div class="select-box" style="position: relative;">
                    <label><i class="fas fa-bed"></i> ODA VE KİŞİ SAYISI</label>
                    <div id="guestSummary" onclick="toggleGuestPanel()">
                        1 Oda, 2 Yetişkin
                    </div>

                    <!-- Dropdown tam altında çıkacak -->
                    <div id="guestDropdown">

                        <div class="counter-row d-flex justify-content-between align-items-center mb-2">
                            <span>Yetişkin</span>
                            <div class="counter d-flex align-items-center">
                                <button onclick="changeCount('adult', -1)">-</button>
                                <span id="adultCount" class="mx-2">2</span>
                                <button onclick="changeCount('adult', 1)">+</button>
                            </div>
                        </div>
                        <div class="counter-row d-flex justify-content-between align-items-center">
                            <span>Çocuk</span>
                            <div class="counter d-flex align-items-center">
                                <button onclick="changeCount('child', -1)">-</button>
                                <span id="childCount" class="mx-2">0</span>
                                <button onclick="changeCount('child', 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn-update" onclick="updateReservation()">GÜNCELLE</button>
            </div>

            <!-- Tur Fiyat Kartı -->
            <div class="tour-price-card">
                <div class="tour-title">
                    <?= mb_strimwidth(htmlspecialchars($tour['name']), 0, 30, '...') ?>
                </div>

                <div id="totalPersons">2 Kişi Toplam</div>
                <div class="price-box">
                    <div class="new-price" id="calculatedPrice"> 0 TL</div>
                    <small>Toplam tur fiyatıdır.</small>
                </div>
                <form action="rezervasyon.php" method="POST" id="rezForm">
                    <input type="hidden" name="tour_id" value="<?= $tour_id ?>">
                    <input type="hidden" name="departure" id="selectedDeparture">
                    <input type="hidden" name="adults" id="formAdultCount">
                    <input type="hidden" name="children" id="formChildCount">
                    <button type="submit" class="btn btn-update">REZERVASYON YAP</button>
                </form>

            </div>
        </div>

    </div>

    <!-- ► Yorum & Beğeni Bölümü (blog.php’den alındı) -->
    <div class="comments-container">

        <!-- Yorum sayısı -->
        <h3><?= $yorum_sonuc->num_rows ?> Yorum</h3>

        <!-- Yorum gönderme formu -->
        <?php if ($user_id): ?>
            <form method="POST" class="comment-form">
                <div class="rating-stars">
                    <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
                </div>
                <textarea name="comment" class="form-control" rows="3" placeholder="Yorumunuzu yazın..."
                    required></textarea>
                <button type="submit" name="submit_comment" class="btn btn-primary mt-2">Gönder</button>
            </form>
        <?php else: ?>
            <p>Yorum yapmak için <a href="giris.php">giriş yapın</a>.</p>
        <?php endif; ?>

        <!-- Gönderme sonrası mesaj -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                ✅ Yorumunuz iletilmiştir. Onaylandıktan sonra yayınlanacaktır.
            </div>
        <?php endif; ?>

        <!-- Yorum listesi -->
        <div class="comment-list">
            <?php while ($c = $yorum_sonuc->fetch_assoc()): ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <strong><?= htmlspecialchars($c['fullname']) ?></strong>
                        <small><?= date("d F Y, H:i", strtotime($c['created_at'])) ?></small>
                        <div class="user-rating"><?= str_repeat('★', $c['rating']) ?></div>
                        <?php if ($c['user_id'] == $user_id): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                <button type="submit" name="delete_comment" class="delete-button">Sil</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <script>
        function showTab(index) {
            const tabs = document.querySelectorAll('.tab-item');
            const contents = document.querySelectorAll('.tab-content');
            tabs.forEach((tab, i) => {
                tab.classList.toggle('active', i === index);
                contents[i].classList.toggle('active', i === index);
            });
        }
        let basePrice = <?= $tour['price'] ?>; // PHP'den alınan yetişkin fiyatı

        function toggleGuestPanel() {
            const panel = document.getElementById("guestDropdown");
            panel.style.display = panel.style.display === "block" ? "none" : "block";
        }

        function changeCount(type, delta) {
            const countEl = document.getElementById(type + "Count");
            let value = parseInt(countEl.innerText);
            value = Math.max(0, value + delta);
            countEl.innerText = value;
            updateGuestSummary();
        }

        function updateGuestSummary() {
            const adult = parseInt(document.getElementById("adultCount").innerText);
            const child = parseInt(document.getElementById("childCount").innerText);
            const guestText = `1 Oda, ${adult} Yetişkin${child > 0 ? `, ${child} Çocuk` : ''}`;
            document.getElementById("guestSummary").innerText = guestText;
        }

        function updateReservation() {
            const adult = parseInt(document.getElementById("adultCount").innerText);
            const child = parseInt(document.getElementById("childCount").innerText);
            const departure = document.getElementById("departureCity").value;

            // Form alanlarına yaz
            document.getElementById("formAdultCount").value = adult;
            document.getElementById("formChildCount").value = child;
            document.getElementById("selectedDeparture").value = departure;

            // Fiyatı göster
            const totalPeople = adult + child;
            const totalPrice = (adult * basePrice) + (child * basePrice / 2);
            document.getElementById("totalPersons").innerText = `${totalPeople} Kişi Toplam`;
            document.getElementById("calculatedPrice").innerText = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(totalPrice);
        }
        function checkLoginStatus() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert("Rezervasyon yapabilmek için lütfen giriş yapınız.");
                return false;
            <?php else: ?>
                return true;
            <?php endif; ?>
        }

        document.addEventListener("click", function (event) {
            const panel = document.getElementById("guestDropdown");
            const summary = document.getElementById("guestSummary");
            if (!panel.contains(event.target) && !summary.contains(event.target)) {
                panel.style.display = "none";
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            updateReservation();

        });
        document.addEventListener('DOMContentLoaded', () => {
            // Like button
            document.querySelectorAll('.like-button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tourId = btn.dataset.tour;
                    fetch(`tur.php?id=${tourId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=toggle_favorite&tour_id=${tourId}`
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'liked' || data.status === 'unliked') {
                                btn.style.color = data.status === 'liked' ? 'red' : 'gray';
                            } else if (data.status === 'login_required') {
                                alert('Beğeni için giriş yapmalısınız.');
                            }
                        })
                        .catch(console.error);
                });
            });
        });

    </script>

</body>

</html>