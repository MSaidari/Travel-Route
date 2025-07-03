<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// ID ile şehri al
$city_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($city_id == 0) {
    die("Geçersiz şehir ID!");
}

$city_query = "SELECT * FROM cities WHERE id = $city_id";
$city_result = $conn->query($city_query);
$city = $city_result->fetch_assoc();

if (!$city) {
    die("Şehir bulunamadı!");
}

// Ülkeleri getir
$countries = $conn->query("SELECT * FROM countries");

// Form gönderildiyse şehir bilgilerini güncelle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $country_id = $_POST['country_id'];
    $photo = $_POST['photo'];

    $sql = "UPDATE cities SET name='$name', country_id='$country_id', photo='$photo' WHERE id=$city_id";
    if ($conn->query($sql) === TRUE) {
        header("Location: admin_cities.php?success=Şehir başarıyla güncellendi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Şehir Düzenle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            margin-top: 50px;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 600;
        }
        .btn-custom {
            background: #4CAF50;
            color: white;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background: #45a049;
        }
        .btn-secondary {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Şehir Düzenle</h2>
        <form action="city_duzenle.php?id=<?= $city_id ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Şehir Adı</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($city['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ülke</label>
                <select class="form-control" name="country_id" required>
                    <?php while ($row = $countries->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $city['country_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Fotoğraf URL</label>
                <input type="text" class="form-control" name="photo" value="<?= htmlspecialchars($city['photo']) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="admin_cities.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
</body>
</html>
