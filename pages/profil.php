<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Kullanıcı adı çek
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

function getProfilePhoto()
{
    if (isset($_SESSION['google_photo']) && !empty($_SESSION['google_photo'])) {
        return $_SESSION['google_photo']; // Google fotoğrafı
    } else {
        // Hayvan fotoğraflarından rastgele
        $avatars = [
            'https://cdn.pixabay.com/photo/2024/01/05/10/53/rabbit-8489271_640.png', // Kedi
            'https://media.istockphoto.com/id/953893978/tr/foto%C4%9Fraf/bukalemun-a%C4%9Fa%C3%A7-%C3%BCzerinde.jpg?s=612x612&w=0&k=20&c=9HaXf8Lh-B_zrvYd1VMQ4kJqqjHe4HPX1PNdDbMl_1o=',
            'https://cdn.pixabay.com/photo/2014/10/01/10/44/animal-468228_640.jpg',
            'https://cdn.pixabay.com/photo/2022/07/13/16/25/cat-7319589_640.jpg',
            'https://cdn.pixabay.com/photo/2023/08/18/15/02/dog-8198719_640.jpg'
        ];
        return $avatars[array_rand($avatars)];
    }
}


// beğendiğim mekanlar
$stmt = $conn->prepare("SELECT p.id, p.name, p.photo FROM favorite_places f JOIN places p ON f.place_id = p.id WHERE f.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_visited = $stmt->get_result();
$visited_places = $result_visited->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Beğenilen bloglar
$stmt = $conn->prepare("SELECT b.id, b.title, b.photo FROM favorite_blogs fb JOIN blog_posts b ON fb.blog_id = b.id WHERE fb.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_blogs = $stmt->get_result();
$fav_blogs = $result_blogs->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Beğenilen turlar
$stmt = $conn->prepare("SELECT t.id, t.name, t.photo FROM favorite_tours ft JOIN tours t ON ft.tours_id = t.id WHERE ft.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_tours = $stmt->get_result();
$fav_tours = $result_tours->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Katılım sağlanan turlar
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.photo 
    FROM reservations r 
    JOIN tours t ON r.tour_id = t.id 
    WHERE r.user_id = ? AND r.status = 'confirmed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_attended = $stmt->get_result();
$attended_tours = $result_attended->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="../css/profil.css" rel="stylesheet">

</head>

<body class="bg-light">

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
                    <a href="anasayfa.php?logout=true">Çıkış Yap</a>
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


    <!-- Profil Bilgileri -->
    <div class="container mt-5">
        <div class="text-center mb-4">
            <img src="<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Profil" class="rounded-circle"
                style="width:120px;height:120px;object-fit:cover;">
            <h2><?= htmlspecialchars($user['fullname']) ?></h2>
        </div>

        <!-- Sekmeler -->
        <ul class="nav nav-tabs justify-content-center mb-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-visited" type="button"
                    role="tab">Beğenilen Mekanlar (<?= count($visited_places) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-blogs" type="button"
                    role="tab">Beğenilen Bloglar (<?= count($fav_blogs) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tours" type="button"
                    role="tab">Beğenilen Turlar (<?= count($fav_tours) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attended" type="button"
                    role="tab">Katıldığım Turlar (<?= count($attended_tours) ?>)</button>
            </li>

        </ul>

        <!-- İçerikler -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-visited" role="tabpanel">
                <div class="row">
                    <?php foreach ($visited_places as $place): ?>
                        <div class="col-md-4 mb-4">
                            <a href="place.php?id=<?= $place['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?= htmlspecialchars($place['photo']) ?>" class="card-img-top"
                                        alt="<?= htmlspecialchars($place['name']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($place['name']) ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-blogs" role="tabpanel">
                <div class="row">
                    <?php foreach ($fav_blogs as $blog): ?>
                        <div class="col-md-4 mb-4">
                            <a href="blog.php?id=<?= $blog['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?= htmlspecialchars($blog['photo']) ?>" class="card-img-top"
                                        alt="<?= htmlspecialchars($blog['title']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($blog['title']) ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-tours" role="tabpanel">
                <div class="row">
                    <?php foreach ($fav_tours as $tour): ?>
                        <div class="col-md-4 mb-4">
                            <a href="tur.php?id=<?= $tour['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?= htmlspecialchars($tour['photo']) ?>" class="card-img-top"
                                        alt="<?= htmlspecialchars($tour['name']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($tour['name']) ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-attended" role="tabpanel">
                <div class="row">
                    <?php foreach ($attended_tours as $tour): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <a href="tur.php?id=<?= $tour['id'] ?>">
                                    <img src="<?= htmlspecialchars($tour['photo']) ?>" class="card-img-top"
                                        alt="<?= htmlspecialchars($tour['name']) ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($tour['name']) ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>