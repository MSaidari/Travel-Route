<?php
session_start();
$conn = new mysqli("localhost", "root", "12345678", "gezirotasi");

// Veritabanı bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanına bağlanırken hata oluştu: " . $conn->connect_error);
}

$error_message = "";
$show_register_form = isset($_GET["register"]) ? true : false; // Eğer "register" GET parametresi varsa kayıt formunu aç

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["fullname"])) {
        // Kayıt İşlemi
        $fullname = $_POST["fullname"];
        $email = $_POST["email"];
        $password = $_POST["password"];

        // Şifre uzunluk kontrolü
        if (strlen($password) < 6 || strlen($password) > 12) {
            $error_message = "Şifre en az 6, en fazla 12 karakter olmalıdır.";
            $show_register_form = true;
        } else {
            $password_hashed = password_hash($password, PASSWORD_BCRYPT);

            // E-posta kontrolü
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Bu e-posta zaten kayıtlı!";
                $show_register_form = true;
            } else {
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $fullname, $email, $password_hashed);

                if ($stmt->execute()) {
                    header("Location: giris.php?success=1"); // Kayıt başarılı olunca giriş formuna yönlendir
                    exit();
                } else {
                    $error_message = "Kayıt sırasında hata oluştu.";
                    $show_register_form = true;
                }
            }
        }
    } elseif (isset($_POST["email"]) && isset($_POST["password"])) {
        // Giriş İşlemi
        $email = $_POST["email"];
        $password = $_POST["password"];

        $stmt = $conn->prepare("SELECT id, fullname, password,role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["role"] = $user["role"]; // Role bilgisini oturumda sakla
                // Eğer kullanıcı giriş yaptıysa ve profil fotoğrafı yoksa rastgele hayvan fotoğrafı ata
                if (!isset($_SESSION["profile_photo"])) {
                    $animal_avatars = [
                        'https://cdn.pixabay.com/photo/2024/01/05/10/53/rabbit-8489271_640.png', // Kedi
                        'https://media.istockphoto.com/id/953893978/tr/foto%C4%9Fraf/bukalemun-a%C4%9Fa%C3%A7-%C3%BCzerinde.jpg?s=612x612&w=0&k=20&c=9HaXf8Lh-B_zrvYd1VMQ4kJqqjHe4HPX1PNdDbMl_1o=',
                        'https://cdn.pixabay.com/photo/2014/10/01/10/44/animal-468228_640.jpg',
                        'https://cdn.pixabay.com/photo/2022/07/13/16/25/cat-7319589_640.jpg',
                        'https://cdn.pixabay.com/photo/2023/08/18/15/02/dog-8198719_640.jpg'
                    ];
                    
                    $_SESSION["profile_photo"] = $animal_avatars[array_rand($animal_avatars)];
                }


                // Kullanıcı admin mi?
                if ($user["role"] == 1) {
                    header("Location: admin.php"); // Admin sayfasına yönlendir
                } else {
                    header("Location: anasayfa.php"); // Normal kullanıcılar için ana sayfa
                }
                exit();
            } else {
                $error_message = "Şifre yanlış!";
            }
        } else {
            $error_message = "Böyle bir kullanıcı bulunamadı!";
        }

    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş & Kayıt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="../css/giris.css" rel="stylesheet">
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
    </div>

    <div class="container">
        <div class="form-container">
            <?php if (!$show_register_form): ?>
                <!-- Giriş Yap Formu -->
                <h2 class="mb-3">Giriş Yap</h2>
                <p class="text-muted">Hesabınıza giriş yaparak devam edin.</p>

                <!-- Hata Mesajı -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="giris.php" method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="E-Posta" required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                <!-- Google ile Giriş -->
                <div class="social-login">
                    <a href="../google-login.php">
                        <button>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/2048px-Google_%22G%22_logo.svg.png"
                                alt="Google">
                            Google ile Giriş Yap
                        </button>
                    </a>
                </div>

                <p class="toggle-link"><a href="giris.php?register=1">Üye değil misin? Kayıt Ol</a></p>
            <?php else: ?>
                <!-- Kayıt Ol Formu -->
                <h2 class="mb-3">Kayıt Ol</h2>
                <p class="text-muted">Hemen bir hesap oluşturun.</p>

                <!-- Hata Mesajı -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Başarı Mesajı -->
                <?php if (isset($_GET["success"])): ?>
                    <div class="alert alert-success">Kayıt başarılı! Giriş yapabilirsiniz.</div>
                <?php endif; ?>

                <form action="giris.php" method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="fullname" class="form-control" placeholder="Ad Soyad" required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="E-Posta" required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                </form>
                <div class="social-login">
                    <a href="../google-login.php">
                        <button>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/2048px-Google_%22G%22_logo.svg.png"
                                alt="Google">
                            Google ile Giriş Yap
                        </button>
                    </a>
                </div>
                <p class="toggle-link"><a href="giris.php">Zaten üye misin? Giriş Yap</a></p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>