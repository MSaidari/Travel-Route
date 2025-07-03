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

// ID ile ülkeyi al
$country_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($country_id == 0) {
    die("Geçersiz ülke ID!");
}

// Önce ülkeye bağlı şehir olup olmadığını kontrol et
$check_cities = $conn->query("SELECT COUNT(*) AS count FROM cities WHERE country_id = $country_id");
$cities_count = $check_cities->fetch_assoc()['count'];

if ($cities_count > 0) {
    die("Bu ülkeye bağlı $cities_count şehir var! Önce şehirleri silmelisiniz.");
}

// Ülkeyi sil
$sql = "DELETE FROM countries WHERE id = $country_id";
if ($conn->query($sql) === TRUE) {
    header("Location: admin_countries.php?success=Ülke başarıyla silindi!");
    exit();
} else {
    echo "Hata: " . $conn->error;
}
?>
