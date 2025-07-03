<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: giris.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Ödeme Başarılı</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .success-box {
      max-width: 500px;
      margin: 100px auto;
      padding: 40px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
      text-align: center;
    }
    .success-box .icon {
      font-size: 64px;
      color: #28a745;
      margin-bottom: 20px;
    }
    .success-box h2 {
      color: #28a745;
      margin-bottom: 10px;
    }
    .success-box p {
      color: #555;
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="success-box">
    <div class="icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2>Ödemeniz Başarıyla Gerçekleşti!</h2>
    <p>Rezervasyon işleminiz alınmıştır. Detayları profilinizden görüntüleyebilirsiniz.</p>
    <a href="anasayfa.php" class="btn btn-success mt-4">Ana Sayfaya Dön</a>
  </div>
</body>
</html>
