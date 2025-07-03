<?php
$host = "localhost";
$dbname = "gezirotasi";
$username = "root";
$password = "12345678";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

if (isset($_POST['country_id'])) {
    $country_id = intval($_POST['country_id']);
    $sql = "SELECT id, name FROM cities WHERE country_id = $country_id";
    $result = $conn->query($sql);

    $cities = '<option value="">Şehir Seç</option>';
    while ($row = $result->fetch_assoc()) {
        $cities .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    echo $cities;
}
?>
