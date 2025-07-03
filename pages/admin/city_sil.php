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

// Önce şehirde mekan olup olmadığını kontrol et
$check_places = $conn->query("SELECT COUNT(*) AS count FROM places WHERE city_id = $city_id");
$places_count = $check_places->fetch_assoc()['count'];

if ($places_count > 0) {
    die("Bu şehre bağlı $places_count mekan var! Önce mekanları silmelisiniz.");
}

// Şehri sil
$sql = "DELETE FROM cities WHERE id = $city_id";
if ($conn->query($sql) === TRUE) {
    header("Location: admin_cities.php?success=Şehir başarıyla silindi!");
    exit();
} else {
    echo "Hata: " . $conn->error;
}
?>
