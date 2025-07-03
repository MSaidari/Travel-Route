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

$place_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($place_id == 0) {
    die("Hatalı veya eksik mekan ID!");
}

// Mekanı sil
$delete_query = $conn->prepare("DELETE FROM places WHERE id = ?");
$delete_query->bind_param("i", $place_id);

if ($delete_query->execute()) {
    header("Location: admin_places.php?success=Mekan başarıyla silindi!");
    exit();
} else {
    echo "Hata: " . $conn->error;
}
?>
