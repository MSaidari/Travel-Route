<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$regions = mysqli_query($conn, "SELECT * FROM regions");
$sql_departure_cities = "
  SELECT DISTINCT c.id, c.name
  FROM tours t
  JOIN cities c ON t.departure_city_id = c.id
  ORDER BY c.name ASC
";
$result_departure_cities = mysqli_query($conn, $sql_departure_cities);


$where = "t.start_date >= CURDATE()";


// Gidilecek bölgeye göre filtrele
if (!empty($_GET['region_id'])) {
    $region = intval($_GET['region_id']);
    $where .= " AND t.regions_id = $region";
}

// Tarih filtresi
if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    $where .= " AND '$date' BETWEEN t.start_date AND t.end_date";
}

// Gece sayısı filtresi
if (!empty($_GET['nights'])) {
    $nights_filter = [];
    foreach ($_GET['nights'] as $n) {
        if ($n == "1-3")
            $nights_filter[] = "(DATEDIFF(t.end_date, t.start_date)+1 BETWEEN 2 AND 4)";
        if ($n == "4-6")
            $nights_filter[] = "(DATEDIFF(t.end_date, t.start_date)+1 BETWEEN 5 AND 7)";
        if ($n == "7-15")
            $nights_filter[] = "(DATEDIFF(t.end_date, t.start_date)+1 BETWEEN 8 AND 16)";
    }
    if (!empty($nights_filter)) {
        $where .= " AND (" . implode(" OR ", $nights_filter) . ")";
    }
}

// Ulaşım türü filtresi
if (!empty($_GET['transport'])) {
    $transports = array_map(function ($t) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $t) . "'";
    }, $_GET['transport']);
    $where .= " AND t.transport IN (" . implode(',', $transports) . ")";
}

// Kalkış şehirleri filtresi
if (!empty($_GET['departure_city'])) {
    $ids = array_map('intval', $_GET['departure_city']);
    $where .= " AND t.departure_city_id IN (" . implode(",", $ids) . ")";
}

// Sıralama
$orderBy = "ORDER BY t.start_date ASC"; // varsayılan sıralama
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] == 'date') {
        $orderBy = "ORDER BY t.start_date ASC";
    } elseif ($_GET['sort'] == 'price') {
        $orderBy = "ORDER BY t.price ASC";
    }
}

// Seçilen aylara göre filtre
if (!empty($_GET['months']) && is_array($_GET['months'])) {
    $monthConditions = [];

    foreach ($_GET['months'] as $month) {
        $month = mysqli_real_escape_string($conn, $month);
        $monthConditions[] = "(MONTH(t.start_date) = '$month' OR MONTH(t.end_date) = '$month')";
    }

    if (!empty($monthConditions)) {
        $where .= " AND (" . implode(" OR ", $monthConditions) . ")";
    }
}

// Nihai sorgu
$query = "
  SELECT t.*, c.name AS city_name
  FROM tours t
  LEFT JOIN cities c ON t.departure_city_id = c.id
  WHERE $where
  $orderBy
";

$result = mysqli_query($conn, $query);
$tourCount = mysqli_num_rows($result);
?>


<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Tur Arama Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/arama_tur.css">

</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <a href="anasayfa.php">
                <img src="../images/logo.jpg" alt="Gezi Rotası">
            </a>
        </div>
        <div class="nav-links">
            <a href="anasayfa.php">ANASAYFA</a>
            <a href="iletisim.php">İLETİŞİM</a>
        </div>

        <!-- Kullanıcı İkonu -->
        <div class="user-icon" id="userIcon">
            <?php if (isset($_SESSION['user_id'])): ?>
                <i class="fas fa-user-circle" style="color: #555; font-size: 30px; cursor:pointer;"></i>
                <div class="user-menu" id="userMenu">
                    <a href="profil.php">Profil</a>
                    <a href="anasayfa.php?logout=true">Çıkış Yap</a> <!-- Çıkış butonu -->
                </div>
            <?php else: ?>
                <a href="giris.php"><i class="fas fa-user-circle" style="color: #555; font-size: 30px;"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let userIcon = document.getElementById("userIcon");
            let userMenu = document.getElementById("userMenu");

            if (userIcon && userMenu) {
                userIcon.addEventListener("click", function (event) {
                    userMenu.style.display = (userMenu.style.display === "block") ? "none" : "block";
                    event.stopPropagation();
                });

                document.addEventListener("click", function (event) {
                    if (!userIcon.contains(event.target)) {
                        userMenu.style.display = "none";
                    }
                });
            }
        });
    </script>
    <div class="container py-4">
        <div class="search-bar mb-4 bg-white p-2 rounded shadow-sm">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <select name="region_id" class="form-select form-select-sm w-100">
                        <option value="">Bölge Seç</option>
                        <?php
                        mysqli_data_seek($regions, 0); // cursor reset
                        while ($reg = mysqli_fetch_assoc($regions)): ?>
                            <option value="<?= $reg['id'] ?>" <?= ($_GET['region_id'] ?? '') == $reg['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($reg['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <input type="date" name="start_date" class="form-control form-control-sm w-100"
                        value="<?= $_GET['start_date'] ?? '' ?>">
                </div>

                <input type="hidden" name="region_name" id="region_name_top">

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-success btn-sm">TUR ARA</button>
                </div>
            </form>
        </div>

        <div class="row">
            <!-- Filtre Alanı -->
            <div class="col-md-3">
                <form method="GET" action="arama_tur.php">
                    <div class="filter-box">
                        <h6><strong>Sonuçları Filtrele</strong></h6>
                        <hr>
                        <label>Dönemler</label>
                        <div class="d-flex flex-wrap">
                            <?php
                            $months = [
                                "01" => "Ocak",
                                "02" => "Şubat",
                                "03" => "Mart",
                                "04" => "Nisan",
                                "05" => "Mayıs",
                                "06" => "Haziran",
                                "07" => "Temmuz",
                                "08" => "Ağustos",
                                "09" => "Eylül",
                                "10" => "Ekim",
                                "11" => "Kasım",
                                "12" => "Aralık"
                            ];

                            foreach ($months as $num => $name): ?>
                                <div class="form-check me-2 mb-2">
                                    <input class="form-check-input" type="checkbox" name="months[]" value="<?= $num ?>"
                                        <?= (isset($_GET['months']) && in_array($num, $_GET['months'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= $name ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Ulaşım Seçenekleri -->
                        <label class="mt-3">Ulaşım Türü</label>
                        <?php
                        $sql_transports = "SELECT DISTINCT transport FROM tours WHERE transport IS NOT NULL AND transport != ''";
                        $result_transports = mysqli_query($conn, $sql_transports);
                        while ($row = mysqli_fetch_assoc($result_transports)):
                            $transport = $row['transport'];
                            ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="transport[]"
                                    value="<?= htmlspecialchars($transport) ?>" id="tr<?= htmlspecialchars($transport) ?>"
                                    <?= (isset($_GET['transport']) && in_array($transport, $_GET['transport'])) ? 'checked' : '' ?>>
                                <label class="form-check-label"
                                    for="tr<?= htmlspecialchars($transport) ?>"><?= ucfirst($transport) ?></label>
                            </div>
                        <?php endwhile; ?>

                        <!-- Gece Sayısı -->
                        <label class="mt-3">Gece Sayısı</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="gece1" name="nights[]" value="1-3"
                                <?= (isset($_GET['nights']) && in_array("1-3", $_GET['nights'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gece1">1-3 Gece</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="gece2" name="nights[]" value="4-6"
                                <?= (isset($_GET['nights']) && in_array("4-6", $_GET['nights'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gece2">4-6 Gece</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="gece3" name="nights[]" value="7-15"
                                <?= (isset($_GET['nights']) && in_array("7-15", $_GET['nights'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gece3">7-15 Gece</label>
                        </div>

                        <!-- Kalkış Noktaları -->
                        <label class="mt-3">Kalkış Noktaları</label>
                        <?php while ($city = mysqli_fetch_assoc($result_departure_cities)): ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="departure_city[]"
                                    value="<?= $city['id'] ?>" id="dc<?= $city['id'] ?>" <?= (isset($_GET['departure_city']) && in_array($city['id'], $_GET['departure_city'])) ? 'checked' : '' ?>>
                                <label class="form-check-label"
                                    for="dc<?= $city['id'] ?>"><?= htmlspecialchars($city['name']) ?></label>
                            </div>
                        <?php endwhile; ?>
                        <!-- Filtrele Butonu -->
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success w-100">Filtrele</button>
                        </div>
                        <!-- gizli parametreler -->
                        <?php if (isset($_GET['region_name'])): ?>
                            <input type="hidden" name="region_name" value="<?= htmlspecialchars($_GET['region_name']) ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['region_id'])): ?>
                            <input type="hidden" name="region_id" value="<?= htmlspecialchars($_GET['region_id']) ?>">
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tur Listesi -->
            <div class="col-md-9">
                <h5>
                    <?php
                    $regionTitle = isset($_GET['region_name']) ? htmlspecialchars($_GET['region_name']) : 'Turlar';
                    echo "$regionTitle için toplam <strong>$tourCount</strong> tur bulundu!";
                    ?>
                </h5>
                <form id="sort-form" method="GET">
                    <input type="hidden" name="sort" id="sort-input" value="<?= $_GET['sort'] ?? '' ?>">

                    <?php foreach ($_GET as $key => $val): ?>
                        <?php if ($key !== 'sort'): ?>
                            <?php if (is_array($val)): ?>
                                <?php foreach ($val as $v): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($v) ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>


                <div class="d-flex justify-content-between my-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="sortBy('date')">Tarihe Göre</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="sortBy('price')">Fiyata Göre</button>
                </div>

                <?php while ($row = mysqli_fetch_assoc($result)):
                    $start = date("d M Y", strtotime($row['start_date']));
                    $end = date("d M Y", strtotime($row['end_date']));
                    $days = (strtotime($row['end_date']) - strtotime($row['start_date'])) / 86400 + 1;
                    $nights = $days - 1;
                    ?>
                    <div class="tour-card">
                        <img src="<?= htmlspecialchars($row['photo']) ?>" class="tour-img" alt="tur görseli">
                        <div class="tour-details">
                            <h5><?= htmlspecialchars($row['name']) ?></h5>
                            <p>📍 Kalkış Şehri: <?= htmlspecialchars($row['city_name']) ?></p>
                            <p>🛌 <?= $nights ?> gece <?= $days ?> gün</p>
                            <p>📅 <?= $start ?> – <?= $end ?></p>
                            <div class="d-flex gap-2 mt-2">
                                <span class="badge bg-secondary"><?= date("d M", strtotime($row['start_date'])) ?></span>
                                <span class="badge bg-warning text-dark"> <?php echo "$regionTitle" ?> </span>
                            </div>
                        </div>
                        <div class="price-box">
                            <h4><?= number_format($row['price'], 2) ?> ₺</h4>
                            <p class="text-muted">'dan itibaren</p>
                            <a href="tur.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">➜</a>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($tourCount == 0): ?>
                    <div class="alert alert-warning mt-4">Hiç tur bulunamadı.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function sortBy(type) {
            const sortInput = document.getElementById('sort-input');
            sortInput.value = type;
            document.getElementById('sort-form').submit();
        }
        function setRegionName(select) {
            const selectedOption = select.options[select.selectedIndex];
            const name = selectedOption.getAttribute("data-name") || "";
            document.getElementById("region_name").value = name;
        }
        document.addEventListener("DOMContentLoaded", function () {
            const select = document.querySelector('select[name="region_id"]');
            const hiddenInput = document.getElementById("region_name_top");

            select.addEventListener("change", function () {
                const selected = select.options[select.selectedIndex];
                hiddenInput.value = selected.text;
            });

            // Sayfa yüklendiğinde de region_name'i doldur
            const selected = select.options[select.selectedIndex];
            hiddenInput.value = selected.text;
        });
    </script>


</body>

</html>