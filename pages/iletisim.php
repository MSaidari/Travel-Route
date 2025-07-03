<?php
session_start();
$conn = new mysqli("localhost", "root", "12345678", "gezirotasi");
$conn->set_charset("utf8mb4");

$errors = [];
$success = false;

// POST geldiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Form verilerini al
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // 2) Basit validasyon
    if ($name === '')
        $errors[] = 'İsim alanı boş bırakılamaz.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    if ($subject === '')
        $errors[] = 'Konu alanı boş bırakılamaz.';
    if ($message === '')
        $errors[] = 'Mesaj alanı boş bırakılamaz.';

    // 3) Eğer validasyon geçtiyse, önce kullanıcıyı bul
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $errors[] = 'Bu e-posta ile kayıtlı bir kullanıcı bulunamadı.';
        } else {
            $row = $res->fetch_assoc();
            $found_user_id = $row['id'];
        }
        $stmt->close();
    }

    // 4) Kullanıcı bulunduysa, communication tablosuna kaydet
    if (empty($errors)) {
        $ins = $conn->prepare("
            INSERT INTO communication (name, subject, message, user_id)
            VALUES (?, ?, ?, ?)
        ");
        // DİKKAT: PHP değişken adı $message; SQL sütunu message
        $ins->bind_param("sssi", $name, $subject, $message, $found_user_id);
        if (!$ins->execute()) {
            // MySQL hatasını ekrana basarak debug yap
            $errors[] = 'Mesajınız kaydedilirken bir hata oluştu: ' . $ins->error;
        } else {
            $success = true;
        }
        $ins->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İletişim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0px 50px;
    background-color: #f8f8f8;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}

.logo img {
    width: 70px;
    /* Logo boyutu */
    cursor: pointer;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    text-decoration: none;
    color: #555;
    font-weight: bold;
    transition: 0.3s;
}

.nav-links a:hover {
    color: #000;
}

.user-icon {
    cursor: pointer;
}

.user-icon img {
    width: 30px;
    height: 30px;
    border-radius: 50%;
}

.user-menu {
    display: none;
    position: absolute;
    right: 10px;
    top: 50px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    padding: 10px;
}
.user-menu a {
    display: block;
    padding: 8px 15px;
    text-decoration: none;
    color: #333;
}
.user-menu a:hover {
    background: #f0f0f0;
}

.nav-links a {
    text-decoration: none;
    color: #333;
    font-weight: bold;
    margin-left: 20px;
    transition: 0.3s;
}

.nav-links a:hover {
    color: #007bff;
}
    </style>
</head>

<body >
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

    <div class="container py-5">
        <div class="row gy-4">
            <!-- form -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="mb-4">Bizimle İletişime Geçin</h3>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                ✅ Mesajınız başarıyla iletildi..
                            </div>
                        <?php elseif (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">İsim</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    value="<?= htmlspecialchars($name ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Konu</label>
                                <input type="text" name="subject" id="subject" class="form-control"
                                    value="<?= htmlspecialchars($subject ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Mesajınız</label>
                                <textarea name="message" id="message" rows="6" class="form-control"
                                    required><?= htmlspecialchars($text ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Gönder</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- iletişim bilgileri + harita -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">İletişim Bilgileri</h5>
                        <p class="mb-1"><strong>Adres:</strong> Köyceğiz Cad. Demeç Sk. Mühendislik Fakültesi Konya/Meram</p>
                        <p class="mb-1"><strong>Telefon:</strong> +90 544 951 79 34</p>
                        <p class="mb-0"><strong>E-posta:</strong> sait1223ari@gmail.com</p>
                    </div>
                </div>
                <div class="ratio ratio-16x9 shadow-sm">
                    
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3149.76!2d32.41!3d37.86!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d087a8cfc8d275%3A0x8e366741d0695abe!2sNecmettin%20Erbakan%20Üniversitesi!5e0!3m2!1str!2str!4v1746723901630!5m2!1str!2str"
                        style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 bg-white shadow-sm">
        <small>© 2025 Gezi Rotası. Tüm hakları saklıdır.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
