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

// Blog ID'yi al
$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($blog_id == 0) {
    die("Hatalı veya eksik ID değeri!");
}

// Blog detaylarını çek
$sql = "SELECT * FROM blog_posts WHERE id = $blog_id";
$result = $conn->query($sql);
$blog = $result->fetch_assoc();

if (!$blog) {
    die("Böyle bir blog bulunamadı.");
}

// Blog güncelleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $description = $_POST['description'];
    $author_id = $_POST['author_id'];
    $city_id = $_POST['city_id'];
    $photo = $_POST['photo'];

    $sql_update = "UPDATE blog_posts SET 
                    title = '$title', 
                    content = '$content', 
                    description = '$description', 
                    author_id = '$author_id', 
                    city_id = '$city_id', 
                    photo = '$photo', 
                    updated_at = NOW() 
                   WHERE id = $blog_id";

    if ($conn->query($sql_update) === TRUE) {
        header("Location: admin_blogs.php?success=Blog başarıyla güncellendi!");
        exit();
    } else {
        echo "Hata: " . $conn->error;
    }
}

// Ülkeleri çek
$country_query = "SELECT * FROM countries";
$countries = $conn->query($country_query);

// Seçili şehrin ülkesini bul
$selected_country_id = 0;
$city_query = "SELECT country_id FROM cities WHERE id = " . $blog['city_id'];
$city_result = $conn->query($city_query);
if ($city_result->num_rows > 0) {
    $selected_country_id = $city_result->fetch_assoc()['country_id'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Düzenle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- CKEditor Kütüphanesi -->
    <script src="https://cdn.ckeditor.com/4.16.2/full/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>Blog Düzenle</h2>
    <form action="blog_duzenle.php?id=<?= $blog['id'] ?>" method="POST">
        <div class="mb-3">
            <label class="form-label">Başlık</label>
            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($blog['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">İçerik</label>
            <textarea class="form-control" name="content" rows="5" required><?= htmlspecialchars($blog['content']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Açıklama (HTML Otomatik Oluşturulacak)</label>
            <textarea id="description" class="form-control" name="description"><?= htmlspecialchars($blog['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Yazar ID</label>
            <input type="number" class="form-control" name="author_id" value="<?= $blog['author_id'] ?>" required>
        </div>
        
        <!-- Ülke ve Şehir Seçimi -->
        <div class="mb-3">
            <label class="form-label">Ülke Seç</label>
            <select class="form-control" id="country" name="country_id">
                <option value="">Ülke Seç</option>
                <?php while ($row = $countries->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>" <?= ($row['id'] == $selected_country_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Şehir Seç</label>
            <select class="form-control" id="city" name="city_id">
                <option value="">Şehir Seç</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Fotoğraf URL</label>
            <input type="text" class="form-control" name="photo" value="<?= $blog['photo'] ?>">
        </div>
        <button type="submit" class="btn btn-primary">Güncelle</button>
        <a href="admin_blogs.php" class="btn btn-secondary">Geri Dön</a>
    </form>
</div>

<!-- CKEditor'u Tanımlama -->
<script>
    CKEDITOR.replace('description', {
        extraAllowedContent: 'iframe[*]',
        height: 300
    });

    // Ülke seçildiğinde şehirleri getir
    $(document).ready(function() {
        function loadCities(country_id, selected_city = '') {
            $.ajax({
                url: 'get_cities.php',
                type: 'POST',
                data: {country_id: country_id},
                success: function(response) {
                    $('#city').html(response);
                    if (selected_city) {
                        $('#city').val(selected_city);
                    }
                }
            });
        }

        $('#country').change(function() {
            let country_id = $(this).val();
            if (country_id) {
                loadCities(country_id);
            } else {
                $('#city').html('<option value="">Şehir Seç</option>');
            }
        });

        // Sayfa yüklenince var olan şehir bilgisini getir
        let initial_country = $('#country').val();
        let initial_city = <?= $blog['city_id'] ?>;
        if (initial_country) {
            loadCities(initial_country, initial_city);
        }
    });
</script>

</body>
</html>
