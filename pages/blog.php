<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Blog ID'yi al
$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($blog_id == 0) {
    die("Hatalı veya eksik ID değeri!");
}

// Blog detaylarını çek
$query = $conn->prepare("
    SELECT blog_posts.title, blog_posts.content, blog_posts.description, blog_posts.photo, blog_posts.city_id, 
           cities.name AS city_name, cities.country_id, 
           countries.name AS country_name
    FROM blog_posts
    JOIN cities ON blog_posts.city_id = cities.id
    JOIN countries ON cities.country_id = countries.id
    WHERE blog_posts.id = ?
");
$query->bind_param("i", $blog_id);
$query->execute();
$result = $query->get_result();
$blog = $result->fetch_assoc();
$query->close();

if (!$blog) {
    die("Böyle bir blog yazısı bulunamadı.");
}

// *Admin kontrolü* (Varsayılan olarak role = 1 olanlar admin)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($user_id == 0 && isset($_SESSION['google_id'])) {
    $google_id = $_SESSION['google_id'];
    $query = $conn->prepare("SELECT id FROM users WHERE google_id = ?");
    $query->bind_param("s", $google_id);
    $query->execute();
    $result = $query->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $_SESSION['user_id'] = $user_id;  // Oturuma doğru ID'yi ekleyelim
    }
    $query->close();
}

// Kullanıcı beğenmiş mi kontrolü
$isLiked = false;
if ($user_id) {
    $check_like_query = $conn->prepare("SELECT id FROM favorite_blogs WHERE user_id = ? AND blog_id = ?");
    $check_like_query->bind_param("ii", $user_id, $blog_id);
    $check_like_query->execute();
    $like_result = $check_like_query->get_result();
    if ($like_result->num_rows > 0) {
        $isLiked = true;
    }
    $check_like_query->close();
}
// Yorum ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    $user_id = $_SESSION['user_id'] ?? null; // Kullanıcı giriş yapmış mı kontrolü
    $comment_text = trim($_POST['comment']);
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : NULL;

    if ($user_id && !empty($comment_text)) {
        $query_comment = $conn->prepare("INSERT INTO reviews_blogs (blogs_id, user_id, rating, comment, created_at, approved) VALUES (?, ?, ?, ?, NOW(), 0)");

        if (!$query_comment) {
            die("SQL Hatası: " . $conn->error);
        }

        $query_comment->bind_param("iiis", $blog_id, $user_id, $rating, $comment_text);
        $query_comment->execute();
        $query_comment->close();

        // **PRG Yöntemi: Sayfanın tekrar POST işlemi yapmasını önlemek için yönlendiriyoruz**
        header("Location: blog.php?id=" . $blog_id . "&success=1");
        exit();
    }
}

// Yorum silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']); // Yorum ID'sini al
    $user_id = $_SESSION['user_id'] ?? null; // Kullanıcı giriş yapmış mı kontrol et

    if ($user_id) {
        // Yorumun kullanıcının olup olmadığını kontrol et
        $query_check = $conn->prepare("SELECT id FROM reviews_blogs WHERE id = ? AND user_id = ?");
        $query_check->bind_param("ii", $comment_id, $user_id);
        $query_check->execute();
        $result_check = $query_check->get_result();

        if ($result_check->num_rows > 0) {
            // Yorum sahibiyse sil
            $query_delete = $conn->prepare("DELETE FROM reviews_blogs WHERE id = ?");
            $query_delete->bind_param("i", $comment_id);
            $query_delete->execute();
            $query_delete->close();

            // Yorum silindi, sayfayı yenile
            header("Location: blog.php?id=" . $blog_id);
            exit();
        }
    }
}

// Onaylanmış yorumları çek
$query_comments = $conn->prepare("SELECT r.id, u.fullname, r.rating, r.comment, r.created_at, r.user_id, r.approved 
                                  FROM reviews_blogs r 
                                  JOIN users u ON r.user_id = u.id 
                                  WHERE r.blogs_id = ? AND r.approved = 1 
                                  ORDER BY r.created_at DESC");
$query_comments->bind_param("i", $blog_id);
$query_comments->execute();
$result_comments = $query_comments->get_result();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($blog['title']) ?> - Gezi Rotası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="stylesheet" href="../css/blog.css">
    <style>
        .hero {
            background-image: url('<?= htmlspecialchars($blog['photo']) ?>');
        }

        .like-button {
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            transition: color 0.3s ease-in-out;
            color:
                <?= $isLiked ? 'red' : 'gray' ?>
            ;
        }
    </style>
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

    <div class="hero">
        <!-- Başlık -->
        <h1><?= htmlspecialchars($blog['title']) ?></h1>
    </div>
    <div class="breadcrumb-container">
        <a href="anasayfa.php"><i class="fas fa-home"></i></a> /
        <a href=""><?= htmlspecialchars($blog['country_name']) ?></a> /
        <a href="sehir_detay.php?id=<?php echo $blog['city_id']; ?>"><?= htmlspecialchars($blog['city_name']) ?></a> /
        <span><?= htmlspecialchars($blog['title']) ?></span>

        <!-- Kalp butonu -->
        <?php if (!$isAdmin): ?>
            <div class="like-button-container">
                <button class="like-button" id="likeButton" data-liked="<?= $isLiked ? 'true' : 'false' ?>"
                    data-blog="<?= $blog_id ?>">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>


    <div class="container content-container">
        <p><?= html_entity_decode($blog['description']) ?></p>
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
                    <textarea name="comment" class="form-control" rows="3" required style="border-radius: 5px;"></textarea>
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
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success" role="alert">
                ✅ Yorumunuz alınmıştır. Onaylandıktan sonra yayınlanacaktır.
            </div>
        <?php endif; ?>

    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let likeButton = document.getElementById("likeButton");
            if (likeButton) {
                likeButton.addEventListener("click", function () {
                    let isLiked = likeButton.getAttribute("data-liked") === "true";
                    let blogId = likeButton.getAttribute("data-blog");

                    fetch("like_blog.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded" // gonderilen verinin formatın belirler
                        },
                        body: "blog_id=" + blogId + "&action=" + (isLiked ? "unlike" : "like")
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                isLiked = !isLiked;
                                likeButton.setAttribute("data-liked", isLiked);
                                likeButton.style.color = isLiked ? "red" : "white";
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => console.error("Hata:", error));
                });
            }
        });
    </script>
</body>

</html>