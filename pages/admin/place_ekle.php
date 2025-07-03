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
    $name = $_POST['name'];
    $description = $_POST['description'];
    $text_message = $_POST['text_message'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $city_id = $_POST['city_id']; // Şehir ID'si alınıyor
    $photo = $_POST['photo'];
    $gallery_photos = $_POST['gallery_photos'] ?? []; // URL ile eklenen fotoğraflar

    // Mekanı veritabanına ekle
    $sql = "INSERT INTO places (name, description, text_message, address, latitude, longitude, city_id, photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssddis", $name, $description, $text_message, $address, $latitude, $longitude, $city_id, $photo);

    if ($stmt->execute()) {
        $place_id = $conn->insert_id; // Eklenen mekanın ID'sini al

        // 📌 Mekan adı ve ID ile klasör oluştur
        $formatted_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name); // Geçersiz karakterleri temizle
        $gallery_dir = "../../images/gallery/" . $place_id . "_" . $formatted_name;

        if (!is_dir($gallery_dir)) {
            mkdir($gallery_dir, 0777, true);
        }

        // URL ile eklenen fotoğrafları kaydet
        if (!empty($gallery_photos)) {
            foreach ($gallery_photos as $gallery_photo) {
                $gallery_photo = trim($gallery_photo);
                if (!empty($gallery_photo)) {
                    $gallery_sql = "INSERT INTO gallery (url, place_id) VALUES (?, ?)";
                    $gallery_stmt = $conn->prepare($gallery_sql);
                    $gallery_stmt->bind_param("si", $gallery_photo, $place_id);
                    $gallery_stmt->execute();
                }
            }
        }

        header("Location: admin_places.php?success=Mekan başarıyla eklendi!");
        exit();
    } else {
        echo "Mekan ekleme hatası: " . $conn->error;
    }
}

$country_query = "SELECT * FROM countries";
$countries = $conn->query($country_query);
?>



<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Mekan Ekle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/full/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

        .photo-section {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .photo-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .photo-input-group input {
            flex-grow: 1;
        }

        #uploaded-files-list,
        #url-files-list {
            list-style: none;
            padding: 0;
        }

        .file-list-item {
            padding: 5px;
            background: #e9ecef;
            margin-bottom: 5px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="text-center mb-4 fw-bold">Yeni Mekan Ekle</h2>
        <form action="place_ekle.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Mekan Adı</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Açıklama (HTML Destekli)</label>
                <textarea id="description" class="form-control" name="description"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Mekan Bilgisi (Text Message)</label>
                <input type="text" class="form-control" name="text_message">
            </div>
            <div class="mb-3">
                <label class="form-label">Adres</label>
                <input type="text" class="form-control" name="address">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Enlem (Latitude)</label>
                    <input type="text" class="form-control" name="latitude" placeholder="Örn: 41.0082">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Boylam (Longitude)</label>
                    <input type="text" class="form-control" name="longitude" placeholder="Örn: 28.9784">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ülke Seç</label>
                    <select class="form-control" id="country" name="country_id">
                        <option value="">Ülke Seç</option>
                        <?php while ($row = $countries->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Şehir Seç</label>
                    <select class="form-control" id="city" name="city_id">
                        <option value="">Önce Ülke Seçin</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Kapak Fotoğrafı (URL)</label>
                <input type="text" class="form-control" name="photo">
            </div>

            <!-- Fotoğraf Ekleme Alanı -->
            <div class="photo-section">
                <label class="form-label">Galeri İçin Fotoğraf</label>
                <button type="button" id="url-upload-btn" class="btn btn-success"><i class="fas fa-link"></i> URL
                    Ekle</button>
            </div>

            <!-- URL ile Yükleme -->
            <div id="photo-container" style="display: none;">
                <div class="photo-input-group">
                    <input type="text" name="gallery_photos[]" class="form-control" placeholder="Fotoğraf URL girin">
                    <button type="button" class="btn btn-danger remove-photo">X</button>
                </div>
            </div>
            <ul id="url-files-list"></ul>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-custom">Ekle</button>
                <a href="admin_places.php" class="btn btn-secondary">Geri Dön</a>
            </div>
        </form>
    </div>

    <script>
        CKEDITOR.replace('description', {
            extraAllowedContent: 'iframe[*]',
            height: 300
        });

        $(document).ready(function () {
            $('#country').change(function () {
                let country_id = $(this).val();
                if (country_id) {
                    $.ajax({
                        url: 'get_cities.php',
                        type: 'POST',
                        data: { country_id: country_id },
                        success: function (response) {
                            $('#city').html(response);
                        }
                    });
                } else {
                    $('#city').html('<option value="">Önce Ülke Seçin</option>');
                }
            });
        });

        $(document).ready(function () {

            // URL ile fotoğraf ekleme
            $('#url-upload-btn').click(function () {
                $("#photo-container").append(`
            <div class="photo-input-group">
                <input type="text" name="gallery_photos[]" class="form-control" placeholder="Fotoğraf URL girin">
                <button type="button" class="btn btn-danger remove-photo">X</button>
            </div>
        `).show();
            });

            // URL veya dosya silme
            $(document).on("click", ".remove-photo", function () {
                $(this).closest(".photo-input-group").remove();
            });
        });

    </script>

</body>

</html>