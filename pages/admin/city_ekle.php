<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $country_id = $_POST['country_id'];
    $photo = $_POST['photo'];

    $sql = "INSERT INTO cities (name, country_id, photo) VALUES ('$name', '$country_id', '$photo')";
    if ($conn->query($sql) === TRUE) {
        header("Location: admin_cities.php?success=Şehir başarıyla eklendi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
}

$countries = $conn->query("SELECT * FROM countries");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Şehir Ekle</title>
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
        <h2 class="text-center">Yeni Şehir Ekle</h2>
        <form action="city_ekle.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Şehir Adı</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ülke</label>
                <select class="form-control" name="country_id" required>
                    <?php while ($row = $countries->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Fotoğraf URL</label>
                <input type="text" class="form-control" name="photo">
            </div>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </form>
    </div>
</body>
</html>
