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

// Düzenlenecek mekanın ID'sini al
$place_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($place_id == 0) {
    die("Geçersiz mekan ID'si!");
}

// Mevcut mekan bilgilerini al
$query = $conn->prepare("SELECT * FROM places WHERE id = ?");
$query->bind_param("i", $place_id);
$query->execute();
$place = $query->get_result()->fetch_assoc();
$query->close();

// Galeriye eklenmiş fotoğrafları al
$gallery_query = $conn->prepare("SELECT id, url FROM gallery WHERE place_id = ?");
$gallery_query->bind_param("i", $place_id);
$gallery_query->execute();
$gallery_result = $gallery_query->get_result();
$gallery_photos = [];
while ($photo = $gallery_result->fetch_assoc()) {
    $gallery_photos[] = $photo;
}
$gallery_query->close();

// Güncelleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = $_POST['name']; // Kullanıcının güncellediği yeni mekan adı
    $description = $_POST['description'];
    $text_message = $_POST['text_message'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $city_id = $_POST['city_id'];
    $photo = $_POST['photo'];
    $gallery_photos_urls = $_POST['gallery_photos'] ?? [];
    $delete_photos = $_POST['delete_photos'] ?? [];

    // 📌 Eski mekan adını al (dosya adı değişikliği için)
    $query_old = $conn->prepare("SELECT name FROM places WHERE id = ?");
    $query_old->bind_param("i", $place_id);
    $query_old->execute();
    $result_old = $query_old->get_result();
    $old_place = $result_old->fetch_assoc();
    $old_name = $old_place['name']; // Eski mekan adı
    $query_old->close();

    // 📂 Eğer mekan adı değişmişse dosyanın ismini de değiştir
    if ($old_name !== $new_name) {
        $old_folder = "../../images/gallery/" . $place_id . "_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $old_name);
        $new_folder = "../../images/gallery/" . $place_id . "_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $new_name);

        if (is_dir($old_folder)) {
            rename($old_folder, $new_folder); // 📂 Eski klasörü yeni adla değiştir
        }
    }

    // 📌 Mekanı güncelle
    $update_sql = "UPDATE places SET name=?, description=?, text_message=?, address=?, latitude=?, longitude=?, city_id=?, photo=? WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssddisi", $new_name, $description, $text_message, $address, $latitude, $longitude, $city_id, $photo, $place_id);

    if ($stmt->execute()) {
        // ✅ Seçili fotoğrafları veritabanından sil
        if (!empty($delete_photos)) {
            $placeholders = implode(',', array_fill(0, count($delete_photos), '?'));
            $delete_query = $conn->prepare("DELETE FROM gallery WHERE id IN ($placeholders)");
            $delete_query->bind_param(str_repeat("i", count($delete_photos)), ...$delete_photos);
            $delete_query->execute();
        }

        // ✅ Yeni galeri fotoğraflarını ekle
        if (!empty($gallery_photos_urls)) {
            foreach ($gallery_photos_urls as $gallery_photo) {
                $gallery_photo = trim($gallery_photo);
                if (!empty($gallery_photo)) {
                    $gallery_sql = "INSERT INTO gallery (url, place_id) VALUES (?, ?)";
                    $gallery_stmt = $conn->prepare($gallery_sql);
                    $gallery_stmt->bind_param("si", $gallery_photo, $place_id);
                    $gallery_stmt->execute();
                }
            }
        }

        header("Location: admin_places.php?success=Mekan başarıyla güncellendi!");
        exit();
    } else {
        echo "Mekan güncelleme hatası: " . $conn->error;
    }
}

// Fotoğraf silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_photo_id'])) {
    $delete_photo_id = intval($_POST['delete_photo_id']);
    $delete_query = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $delete_query->bind_param("i", $delete_photo_id);

    if ($delete_query->execute()) {
        // ✅ Fotoğraf başarıyla silindiğinde sayfayı yenile
        echo "<script>window.location.href='place_duzenle.php?id=" . $place_id . "&success=Fotoğraf silindi';</script>";
        exit();
    } else {
        echo "Fotoğraf silme hatası: " . $conn->error;
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
    <title>Mekan Düzenle</title>
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

        .gallery-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .gallery-item img {
            width: 100px;
            border-radius: 5px;
        }

        .marked-for-delete {
            border: 2px solid red;
            opacity: 0.5;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="text-center mb-4 fw-bold">Mekan Düzenle</h2>
        <form action="place_duzenle.php?id=<?= $place_id ?>" method="POST">
            <div class="mb-3">
                <label class="form-label">Mekan Adı</label>
                <input type="text" class="form-control" name="name" value="<?= $place['name'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Açıklama (HTML Destekli)</label>
                <textarea id="description" class="form-control"
                    name="description"><?= $place['description'] ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Mekan Bilgisi (Text Message)</label>
                <input type="text" class="form-control" name="text_message" value="<?= $place['text_message'] ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Adres</label>
                <input type="text" class="form-control" name="address" value="<?= $place['address'] ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Enlem (Latitude)</label>
                    <input type="text" class="form-control" name="latitude" value="<?= $place['latitude'] ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Boylam (Longitude)</label>
                    <input type="text" class="form-control" name="longitude" value="<?= $place['longitude'] ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ülke Seç</label>
                    <select class="form-control" id="country" name="country_id" required>
                        <option value="">Ülke Seç</option>
                        <?php
                        $country_query = "SELECT * FROM countries";
                        $countries = $conn->query($country_query);
                        while ($row = $countries->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $place['country_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Şehir Seç</label>
                    <select class="form-control" id="city" name="city_id" required>
                        <option value="">Önce Ülke Seçin</option>
                        <?php
                        $city_query = "SELECT * FROM cities WHERE country_id = ?";
                        $stmt = $conn->prepare($city_query);
                        $stmt->bind_param("i", $place['country_id']);
                        $stmt->execute();
                        $cities = $stmt->get_result();
                        while ($row = $cities->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $place['city_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Kapak Fotoğrafı (URL)</label>
                <input type="text" class="form-control" name="photo" value="<?= $place['photo'] ?>">
            </div>

            <div id="photo-container">
            <label class="form-label">Mevcut Galeri Fotoğrafları</label>

                <?php foreach ($gallery_photos as $photo): ?>
                    <div class="gallery-item" data-photo-id="<?= $photo['id'] ?>">
                        <img src="<?= htmlspecialchars($photo['url']) ?>" width="100" class="gallery-photo">
                        <button type="button" class="btn btn-danger btn-sm mark-delete">Sil</button>
                        <input type="hidden" name="delete_photos[]" value="" class="delete-photo-input">
                    </div>
                <?php endforeach; ?>
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

            <button type="submit" class="btn btn-custom">Güncelle</button>
        </form>
    </div>

    <script>
        CKEDITOR.replace('description', { extraAllowedContent: 'iframe[*]', height: 300 });
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
        $(document).ready(function () {
            $(".mark-delete").click(function () {
                let galleryItem = $(this).closest(".gallery-item");
                let deleteInput = galleryItem.find(".delete-photo-input");

                // Eğer zaten işaretlendiyse geri al
                if (galleryItem.hasClass("marked-for-delete")) {
                    galleryItem.removeClass("marked-for-delete");
                    galleryItem.find("img").css("opacity", "1"); // Fotoğrafı normal göster
                    deleteInput.val(""); // Gizli inputu boşalt
                } else {
                    galleryItem.addClass("marked-for-delete");
                    galleryItem.find("img").css("opacity", "0.5"); // Fotoğrafı saydam yap
                    deleteInput.val(galleryItem.data("photo-id")); // Silinecek fotoğrafın ID'sini gizli inputa ekle
                }
            });
        });

    </script>

</body>

</html>