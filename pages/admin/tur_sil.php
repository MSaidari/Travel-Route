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

if (isset($_GET['id'])) {
    $tour_id = intval($_GET['id']);
    $sql = "DELETE FROM tours WHERE id = $tour_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: admin_tours.php?success=Tur başarıyla silindi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
} else {
    echo "Geçerli bir tur ID'si bulunamadı.";
}
?>
