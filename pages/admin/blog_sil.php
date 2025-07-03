<?php
session_start();

// Hata ayıklama modunu aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit();
}

// Veritabanı bağlantısını dahil et
$conn = new mysqli("localhost", "root", "12345678", "gezirotasi");

// Bağlantı hatası kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// Blog ID kontrolü
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $blog_id = $_GET['id'];

    // Blog yazısını silme sorgusu
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    
    // **Hata Kontrolü** (Eğer `$stmt` başarısız olursa hata yazdır)
    if (!$stmt) {
        die("SQL Hatası: " . $conn->error);
    }

    $stmt->bind_param("i", $blog_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Blog başarıyla silindi.";
    } else {
        $_SESSION['error'] = "Blog silinemedi. Hata: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Geçersiz blog ID!";
}

// Listeleme sayfasına geri yönlendir
header("Location: admin_blogs.php");
exit();
?>
