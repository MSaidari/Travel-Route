<?php
session_start();
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

// Veritabanına bağlan
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// Gezilecek yerleri çek (Rastgele 4 tane)
$sql_places = "SELECT id, name, text_message, address, photo FROM places ORDER BY RAND() LIMIT 4";
$result_places = $conn->query($sql_places);

// Son 3 blog yazısını çek
$sql_blogs = "SELECT b.id, b.title, b.content, b.photo, c.name AS city_name, c.id AS city_id
        FROM blog_posts b
        JOIN cities c ON b.city_id = c.id
        ORDER BY b.created_at DESC 
        LIMIT 3";
$result_new_blogs = $conn->query($sql_blogs);

// Popüler şehirleri çek (Rastgele 3 tane)
$sql_cities = "SELECT id, name, photo FROM cities ORDER BY RAND() LIMIT 3";
$result_cities = $conn->query($sql_cities);

// Türkiye'deki şehirleri çek (country_id = 1)
$sql_turkey_cities = "SELECT id, name FROM cities WHERE country_id = 1 ORDER BY name ASC";
$result_turkey_cities = $conn->query($sql_turkey_cities);

// Diğer ülkeleri çek
$sql_countries = "SELECT id, name FROM countries ORDER BY name ASC";
$result_countries = $conn->query($sql_countries);

// Regions verisini çek (turlar dropdown için)
$sql_regions_menu = "SELECT id, name FROM regions ORDER BY name ASC";
$result_regions_menu = $conn->query($sql_regions_menu);


$sql_blogs = "SELECT id, title, content, photo FROM blog_posts LIMIT 6";
$result_blogs = $conn->query($sql_blogs);

// Ülkelere ait şehirleri çek
$cities_by_country = [];
while ($country = $result_countries->fetch_assoc()) {
    $country_id = $country['id'];
    $cities_by_country[$country_id] = [
        'name' => $country['name'],
        'cities' => [],
    ];
    $sql_country_cities = "SELECT id, name FROM cities WHERE country_id = $country_id ORDER BY name ASC";
    $result_country_cities = $conn->query($sql_country_cities);
    while ($city = $result_country_cities->fetch_assoc()) {
        $cities_by_country[$country_id]['cities'][] = $city;
    }
}

// Eğer çıkış butonuna basıldıysa oturumu kapat ve giriş sayfasına yönlendir
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: anasayfa.php");
    exit();
}

include("chatbot.php");
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gezi Rotası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap tasarım kutuphanesi -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- bootstrap js kutuphanesi -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <!-- ikon kutuphanesi -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <!-- ozel yazı tipi -->
    <link href="../css/anasayfa.css" rel="stylesheet">
    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
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
    <!-- Hero Bölümü -->
    <div class="hero">
        <h1>Rotanız Neresi?</h1>

        <div class="category-tabs text-center my-3">
            <button type="button" class="category-btn active" data-target="search-city">Şehir / Blog</button>
            <button type="button" class="category-btn" data-target="search-tour">Turlar</button>
        </div>

        <!-- Şehir / Blog Arama -->
        <form id="search-city" class="search-box" style="display: block;" method="GET" action="arama.php">
            <div class="search-container">
                <input type="text" name="q" placeholder="Şehir veya blog ara..." class="search-input"
                    style="width: 70%;">
                <button type="submit" class="search-button">Ara</button>
            </div>
        </form>

        <!-- Turlar Arama -->
        <form id="search-tour" class="search-box" style="display: none;" method="GET" action="arama_tur.php">
            <div class="search-container">
                <!-- Gidilecek yer -->
                <select name="region_id" class="search-select" onchange="setRegionName(this)">
                    <option value="">Bölge</option>
                    <?php
                    $sql_regions = "SELECT id, name FROM regions ORDER BY name ASC";
                    $result_regions = $conn->query($sql_regions);
                    while ($region = $result_regions->fetch_assoc()) {
                        echo '<option value="' . $region['id'] . '" data-name="' . htmlspecialchars($region['name']) . '">' . htmlspecialchars($region['name']) . '</option>';
                    }
                    ?>
                </select>

                <!-- Gidiş tarihi -->
                <input type="hidden" name="region_name" id="region_name">
                <input type="date" name="start_date" class="search-select">

                <!-- Buton -->
                <button type="submit" class="search-button">Ara</button>
            </div>
        </form>

    </div>
    <!-- Menü Alanı-->
    <div class="menu-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container justify-content-center">
                <ul class="navbar-nav">
                    <!-- TÜRKİYE -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="turkiyeMenu" data-bs-toggle="dropdown">
                            TÜRKİYE
                        </a>
                        <ul class="dropdown-menu">
                            <?php while ($city = $result_turkey_cities->fetch_assoc()): ?>
                                <li><a class="dropdown-item"
                                        href="sehir_detay.php?id=<?php echo $city['id']; ?>"><?php echo $city['name']; ?></a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </li>

                    <!-- DÜNYA -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dunyaMenu" data-bs-toggle="dropdown">
                            DÜNYA
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($cities_by_country as $country_id => $country): ?>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#"><?php echo $country['name']; ?></a>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($country['cities'] as $city): ?>
                                            <li><a class="dropdown-item"
                                                    href="sehir_detay.php?id=<?php echo $city['id']; ?>"><?php echo $city['name']; ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>

                    <!-- TURLAR -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="turlarMenu" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            TURLAR
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="turlarMenu">
                            <?php while ($region = $result_regions_menu->fetch_assoc()): ?>
                                <?php
                                $region_id = $region['id'];
                                $region_name = urlencode($region['name']);
                                ?>
                                <li>
                                    <a class="dropdown-item"
                                        href="arama_tur.php?region_id=<?= $region_id ?>&region_name=<?= $region_name ?>">
                                        <?= htmlspecialchars($region['name']) ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div id="explore" class="container mt-5">
        <div class="blog-slider">
            <div class="blog-wrapper">
                <?php while ($blog = $result_blogs->fetch_assoc()): ?>
                    <div class="blog-slide">
                        <div class="blog-content">
                            <div class="blog-image">
                                <img src="<?= htmlspecialchars($blog['photo']) ?>"
                                    alt="<?= htmlspecialchars($blog['title']) ?>">
                            </div>
                            <div class="blog-text">
                                <h3><?= htmlspecialchars($blog['title']) ?></h3>
                                <p><?= htmlspecialchars($blog['content']) ?></p>
                                <a href="blog.php?id=<?= $blog['id'] ?>" class="btn btn-dark">Devamı</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Kaydırma Noktaları -->
            <div class="blog-dots"></div>
        </div>
    </div>

    <div class="container">

        <!-- Gezilecek Yerler -->
        <div class="container">
            <div class="section-title">
                <h2>Gezilecek Yerler</h2>
            </div>
            <div class="row">
                <?php while ($row = $result_places->fetch_assoc()): ?>
                    <div class="col-md-3">
                        <div class="place-card">
                            <a href="place.php?id=<?php echo $row['id']; ?>">
                                <img src="<?php echo $row['photo']; ?>" alt="<?php echo $row['name']; ?>">
                                <div class="place-info">
                                    <h3 class="place-title"><?php echo $row['name']; ?></h3>
                                    <p><?php echo $row['text_message']; ?></p>
                                    <span class="place-location"><?php echo $row['address']; ?></span>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Son Yazılar -->
        <div class="section-title">
            <h2>Son Yazılar</h2>
        </div>
        <div class="latest-blogs-container">
            <?php while ($row = $result_new_blogs->fetch_assoc()): ?>
                <div class="latest-blog-item">
                    <img src="<?= htmlspecialchars($row['photo']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                    <div class="latest-blog-text">
                        <h3>
                            <a href="blog.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: black;">
                                <?= htmlspecialchars($row['title']) ?>
                            </a>
                        </h3>
                        <span class="latest-blog-tag">
                            <a href="sehir_detay.php?id=<?= $row['city_id'] ?>"
                                style="text-decoration: none; color: white;">
                                <?= htmlspecialchars($row['city_name']) ?>
                            </a>
                        </span>

                        <p><?= substr(strip_tags($row['content']), 0, 150) ?>...</p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Popüler Şehirler -->
        <div class="container">
            <div class="section-title">
                <h2>Popüler Şehirler</h2>
            </div>
            <div class="city-grid">
                <?php while ($row = $result_cities->fetch_assoc()): ?>
                    <div class="city-item">
                        <a href="sehir_detay.php?id=<?php echo $row['id']; ?>">
                            <img src="<?php echo $row['photo']; ?>" alt="<?php echo $row['name']; ?>">
                            <div class="city-text"><?php echo $row['name']; ?></div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>

        </div>
        <!-- Turlar Bölümü -->
        <div class="container mt-5">
            <div class="section-title">
                <h2>Popüler Turlar</h2>

            </div>
            <div class="row">
                <?php
                $sql_tours = "SELECT id, name, price, start_date, photo FROM tours ORDER BY RAND() LIMIT 3";
                $result_tours = $conn->query($sql_tours);
                while ($tour = $result_tours->fetch_assoc()):
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="<?= $tour['photo'] ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($tour['name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($tour['name']) ?></h5>
                                <p class="card-text">
                                    <?= date("d M Y", strtotime($tour['start_date'])) ?> -
                                    <?= number_format($tour['price'], 2) ?> ₺
                                </p>
                                <a href="tur.php?id=<?= $tour['id'] ?>" class="btn btn-black mt-auto">Detay</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const categoryButtons = document.querySelectorAll(".category-btn");
            const cityForm = document.getElementById("search-city");
            const tourForm = document.getElementById("search-tour");

            // Butonlara tıklama olayı
            categoryButtons.forEach(button => {
                button.addEventListener("click", function () {
                    // Aktif butonu belirle
                    categoryButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");

                    // Hedef formu belirle
                    const targetForm = this.getAttribute("data-target");

                    // Formları gizle veya göster
                    if (targetForm === "search-city") {
                        cityForm.style.display = "block";
                        tourForm.style.display = "none";
                    } else if (targetForm === "search-tour") {
                        cityForm.style.display = "none";
                        tourForm.style.display = "block";
                    }
                });
            });

            // Blog slider 
            const wrapper = document.querySelector(".blog-wrapper");
            const slides = document.querySelectorAll(".blog-slide");
            const dotsContainer = document.querySelector(".blog-dots");

            let index = 0;
            let totalSlides = slides.length;

            function updateSliderPosition() {
                wrapper.style.transform = `translateX(-${index * 100}%)`;
                updateDots();
            }

            function nextSlide() {
                if (index < totalSlides - 1) {
                    index++;
                } else {
                    index = 0;
                }
                updateSliderPosition();
            }

            slides.forEach((_, i) => {
                const dot = document.createElement("div");
                dot.classList.add("blog-dot");
                if (i === 0) dot.classList.add("active");
                dot.addEventListener("click", () => {
                    index = i;
                    updateSliderPosition();
                });
                dotsContainer.appendChild(dot);
            });

            function updateDots() {
                document.querySelectorAll(".blog-dot").forEach((dot, i) => {
                    dot.classList.toggle("active", i === index);
                });
            }

            setInterval(nextSlide, 10000);

            //optiondan seçileni alma

            function setRegionName(select) {
                const selectedOption = select.options[select.selectedIndex];
                const regionName = selectedOption.getAttribute("data-name") || "";
                document.getElementById("region_name").value = regionName;

            }
            document.querySelector("select[name='region_id']").addEventListener("change", function () {
                const selectedOption = this.options[this.selectedIndex];
                const name = selectedOption.getAttribute("data-name");

                // region_name input'una değeri yaz
                document.getElementById("region_name").value = name;
            });

        });
    </script>

</body>

</html>