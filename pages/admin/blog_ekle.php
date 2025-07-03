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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $description = $_POST['description']; // HTML olarak kaydedilecek
    $author_id = $_POST['author_id'];
    $city_id = $_POST['city_id'];
    $photo = $_POST['photo'];

    $sql = "INSERT INTO blog_posts (title, content, description, author_id, city_id, photo, created_at) 
            VALUES ('$title', '$content', '$description', '$author_id', '$city_id', '$photo', NOW())";

    if ($conn->query($sql) === TRUE) {
        header("Location: admin_blogs.php?success=Blog başarıyla eklendi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
}

// Ülkeleri çek
$country_query = "SELECT * FROM countries";
$countries = $conn->query($country_query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Blog Ekle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/full/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            margin-top: 50px;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 600;
        }
        .btn-custom {
            background: #4CAF50;
            color: white;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background: #45a049;
        }
        .btn-secondary {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4 fw-bold">Yeni Blog Ekle</h2>
    <form action="blog_ekle.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Başlık</label>
            <input type="text" class="form-control" name="title" required>
        </div>
        <div class="mb-3">
            <label class="form-label">İçerik</label>
            <textarea class="form-control" name="content" rows="5" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Açıklama (HTML Otomatik Oluşturulacak)</label>
            <textarea id="description" class="form-control" name="description"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Yazar ID</label>
                <input type="number" class="form-control" name="author_id" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Ülke Seç</label>
                <select class="form-control" id="country" name="country_id">
                    <option value="">Ülke Seç</option>
                    <?php while ($row = $countries->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Şehir Seç</label>
            <select class="form-control" id="city" name="city_id">
                <option value="">Önce Ülke Seçin</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Fotoğraf URL</label>
            <input type="text" class="form-control" name="photo">
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-custom">Ekle</button>
            <a href="admin_blogs.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </form>
</div>

<script>
    CKEDITOR.replace('description', {
        extraAllowedContent: 'iframe[*]',
        height: 300
    });

    $(document).ready(function() {
        $('#country').change(function() {
            let country_id = $(this).val();
            if (country_id) {
                $.ajax({
                    url: 'get_cities.php',
                    type: 'POST',
                    data: { country_id: country_id },
                    success: function(response) {
                        $('#city').html(response);
                    }
                });
            } else {
                $('#city').html('<option value="">Önce Ülke Seçin</option>');
            }
        });
    });
</script>

</body>
</html>
