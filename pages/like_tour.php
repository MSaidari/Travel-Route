<?php
session_start();
header("Content-Type: application/json");

$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Veritabanı bağlantı hatası"]));
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['google_id'])) {
    echo json_encode(["status" => "error", "message" => "Giriş yapmalısınız"]);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
if ($user_id == 0) {
    echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı"]);
    exit();
}

$tour_id = isset($_POST['tour_id']) ? intval($_POST['tour_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : "";

if ($tour_id == 0 || !in_array($action, ["like", "unlike"])) {
    echo json_encode(["status" => "error", "message" => "Geçersiz işlem"]);
    exit();
}

if ($action == "like") {
    $insert = $conn->prepare("INSERT INTO favorite_tours (user_id, tours_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $tour_id);
    if ($insert->execute()) {
        echo json_encode(["status" => "liked"]); // BURAYI "liked" yaptık
    } else {
        echo json_encode(["status" => "error"]);
    }
    $insert->close();
} elseif ($action == "unlike") {
    $delete = $conn->prepare("DELETE FROM favorite_tours WHERE user_id = ? AND tours_id = ?");
    $delete->bind_param("ii", $user_id, $tour_id);
    if ($delete->execute()) {
        echo json_encode(["status" => "unliked"]); // BURAYI "unliked" yaptık
    } else {
        echo json_encode(["status" => "error"]);
    }
    $delete->close();
}

$conn->close();
?>
