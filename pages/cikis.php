<?php
session_start();
session_destroy(); // Oturumu sonlandır
header("Location: anasayfa.php"); // Kullanıcıyı anasayfaya yönlendir
exit();
?>
