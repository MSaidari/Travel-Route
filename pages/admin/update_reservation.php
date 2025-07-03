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

// POST verilerini al
$reservation_id = intval($_POST['id']);
$new_status = $_POST['status'];

// Önce rezervasyon bilgilerini çek
$stmt = $conn->prepare("SELECT tour_id, adult, child FROM reservations WHERE id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    die("Rezervasyon bulunamadı.");
}

$tour_id = $reservation['tour_id'];
$total_people = $reservation['adult'] + $reservation['child'];

// 1. Önce rezervasyonun statüsünü güncelle
$stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $reservation_id);
$stmt->execute();
$stmt->close();

// 2. Eğer yeni durum 'confirmed' ise, tur kapasitesini azalt
if ($new_status === 'confirmed') {
    $stmt = $conn->prepare("UPDATE tours SET capacity = capacity - ? WHERE id = ?");
    $stmt->bind_param("ii", $total_people, $tour_id);
    $stmt->execute();
    $stmt->close();
}

// Admin rezervasyon listesine geri dön
header("Location: admin_reservations.php?status=$new_status");
exit();
?>
