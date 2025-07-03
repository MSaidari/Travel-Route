<?php
session_start();
header("Content-Type: application/json");

$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Veritabanı bağlantı hatası"]));
}

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id']) && !isset($_SESSION['google_id'])) {
    echo json_encode(["status" => "error", "message" => "Giriş yapmalısınız"]);
    exit();
}

// Kullanıcı ID'yi al
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Eğer hala kullanıcı ID yoksa hata ver
if ($user_id == 0) {
    echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı"]);
    exit();
}

// Blog ID ve aksiyonu al
$blog_id = isset($_POST['blog_id']) ? intval($_POST['blog_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : "";

if ($blog_id == 0 || !in_array($action, ["like", "unlike"])) {
    echo json_encode(["status" => "error", "message" => "Geçersiz işlem"]);
    exit();
}

if ($action == "like") {
    
    // Beğenme işlemi
    $insert = $conn->prepare("INSERT INTO favorite_blogs (user_id, blog_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $blog_id);
    if ($insert->execute()) {
        echo json_encode(["status" => "success", "message" => "Beğenildi"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Beğenme işlemi başarısız"]);
    }
    $insert->close();
} elseif ($action == "unlike") {
    // Beğeniyi kaldırma işlemi
    $delete = $conn->prepare("DELETE FROM favorite_blogs WHERE user_id = ? AND blog_id = ?");
    $delete->bind_param("ii", $user_id, $blog_id);
    if ($delete->execute()) {
        echo json_encode(["status" => "success", "message" => "Beğeni kaldırıldı"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Beğeni kaldırma başarısız"]);
    }
    $delete->close();
}

$conn->close();
