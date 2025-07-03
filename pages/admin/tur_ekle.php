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
    $description = $_POST['description'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];
    $region_id = $_POST['region_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $departure_city_id = $_POST['departure_city_id'];
    $departure_info = $_POST['departure_info'];
    $services = $_POST['services'];
    $transport = $_POST['transport'];
    $photo = $_POST['photo'];

    $sql = "INSERT INTO tours (name, description, price, capacity, regions_id, start_date, end_date, departure_city_id, departure_info, services, transport, photo)
            VALUES ('$name', '$description', '$price', '$capacity', '$region_id', '$start_date', '$end_date', '$departure_city_id', '$departure_info', '$services', '$transport', '$photo')";

    if ($conn->query($sql) === TRUE) {
        header("Location: admin_tours.php?success=Tur başarıyla eklendi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
}

$regions = $conn->query("SELECT * FROM regions");
$cities = $conn->query("SELECT * FROM cities");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Tur Ekle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/full/ckeditor.js"></script>
</head>
<body>
<div class="container mt-5 bg-white p-4 rounded shadow" style="max-width:800px;">
    <h3 class="mb-4 text-center">Yeni Tur Ekle</h3>
    <form action="tur_ekle.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Tur Adı</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-control" rows="4" id="desc_editor"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fiyat</label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Kapasite</label>
                <input type="number" name="capacity" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Bölge</label>
                <select name="region_id" class="form-select" required>
                    <option value="">Seçiniz</option>
                    <?php while($row = $regions->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Kalkış Şehri</label>
                <select name="departure_city_id" class="form-select" required>
                    <option value="">Seçiniz</option>
                    <?php while($row = $cities->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Kalkış Noktaları</label>
            <textarea name="departure_info" class="form-control" rows="3" id="departure_editor"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Hizmetler</label>
            <textarea name="services" class="form-control" rows="3" id="services_editor"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Ulaşım Türü</label>
            <input type="text" name="transport" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fotoğraf URL</label>
            <input type="text" name="photo" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Ekle</button>
            <a href="admin_tours.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </form>
</div>
<script>
  CKEDITOR.replace('desc_editor');
  CKEDITOR.replace('departure_editor');
  CKEDITOR.replace('services_editor');
</script>
</body>
</html>
