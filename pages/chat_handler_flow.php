<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Gelen mesajı al
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$message = strtolower(trim($data['message'] ?? ''));

// Veritabanı bağlantısı
$conn = new mysqli("localhost", "root", "12345678", "gezirotasi");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Veritabanı bağlantı hatası']);
    exit;
}

// Kategori butonlarını oluşturur
function kategoriButonlari() {
    $buttons = "<div style='margin-top:10px;'>";
    foreach (['Turlar', 'Blog Yazıları', 'Gezilecek Yerler'] as $opt) {
        $buttons .= "<button onclick=\"sendMessageText('$opt')\" style='margin:5px;padding:5px 10px;background:#007bff;color:#fff;border:none;border-radius:5px;'>$opt</button>";
    }
    $buttons .= "</div>";
    return $buttons;
}

function createCard($title, $photo, $desc, $link) {
    $descShort = mb_substr(strip_tags($desc), 0, 80) . '...';
    return "<div style='border:1px solid #ccc; border-radius:8px; overflow:hidden; margin-bottom:10px; display:flex;'>" .
           "<img src='$photo' style='width:80px; height:80px; object-fit:cover;'>" .
           "<div style='padding:5px;'>" .
           "<strong>$title</strong><br>$descShort<br>" .
           "<a href='$link' target='_blank'>Detay</a>" .
           "</div></div>";
}

// Girişte karşılama mesajı
if (!$message || in_array($message, ['merhaba', 'selam', 'başla'])) {
    $_SESSION['step'] = 'kategori';
    $reply = "Merhaba! Size nasıl yardımcı olabilirim? Lütfen bir kategori seçin:";
    $reply .= kategoriButonlari();
    echo json_encode(['reply' => $reply]);
    exit;
}

// Kategori seçimi
if ($_SESSION['step'] === 'kategori') {
    if (strpos($message, 'tur') !== false) {
        $_SESSION['step'] = 'tur';
        echo json_encode(['reply' => "Ne tür bir tur arıyorsunuz? Örneğin: '3 günlük antalya tatili' veya 'istanbul 1000 TL altı' gibi yazabilirsiniz."]);
        exit;
    } elseif (strpos($message, 'blog') !== false) {
        $_SESSION['step'] = 'blog';
        echo json_encode(['reply' => "Hangi konuda blog yazısı arıyorsunuz? Şehir adı, tema ya da doğrudan ilgi alanı belirtebilirsiniz."]);
        exit;
    } elseif (strpos($message, 'yer') !== false || strpos($message, 'gezilecek') !== false) {
        $_SESSION['step'] = 'yer';
        echo json_encode(['reply' => "Nasıl bir yer arıyorsunuz? Örneğin: 'deniz manzaralı', 'tarihi yer', 'doğada gezilecek' gibi."]);
        exit;
    } else {
        $reply = "Lütfen bir kategori seçin:";
        $reply .= kategoriButonlari();
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// Turlar
if ($_SESSION['step'] === 'tur') {
    $escaped = $conn->real_escape_string($message);
    $today = date('Y-m-d');
    $sql = "SELECT id, name, description, photo, start_date, end_date, price 
            FROM tours 
            WHERE (name LIKE '%$escaped%' OR description LIKE '%$escaped%') 
            AND start_date >= '$today' 
            LIMIT 5";
    $res = $conn->query($sql);
    $cards = [];
    while ($row = $res->fetch_assoc()) {
        $days = round((strtotime($row['end_date']) - strtotime($row['start_date'])) / 86400);
        $desc = strip_tags($row['description']);
        $desc .= "<br><strong>Fiyat:</strong> {$row['price']} ₺<br><strong>Süre:</strong> {$days} gün";
        $cards[] = createCard($row['name'], $row['photo'], $desc, "tur.php?id={$row['id']}");
    }
    $reply = $cards ? implode("\n", $cards) : 'Sonuç bulunamadı.';
    $reply .= "<br><br>Başka bir kategori seçmek ister misiniz?" . kategoriButonlari();
    $_SESSION['step'] = 'kategori';
    echo json_encode(['reply' => $reply]);
    exit;
}

// Blog Yazıları
if ($_SESSION['step'] === 'blog') {
    $escaped = $conn->real_escape_string($message);
    $sql = "SELECT id, title, content, photo 
            FROM blog_posts 
            WHERE title LIKE '%$escaped%' OR content LIKE '%$escaped%' 
            LIMIT 5";
    $res = $conn->query($sql);
    $cards = [];
    while ($row = $res->fetch_assoc()) {
        $cards[] = createCard($row['title'], $row['photo'], $row['content'], "blog.php?id={$row['id']}");
    }
    $reply = $cards ? implode("\n", $cards) : 'Sonuç bulunamadı.';
    $reply .= "<br><br>Başka bir kategori seçmek ister misiniz?" . kategoriButonlari();
    $_SESSION['step'] = 'kategori';
    echo json_encode(['reply' => $reply]);
    exit;
}

// Gezilecek Yerler
if ($_SESSION['step'] === 'yer') {
    $escaped = $conn->real_escape_string($message);
    $sql = "SELECT id, name, text_message, photo 
            FROM places 
            WHERE name LIKE '%$escaped%' OR description LIKE '%$escaped%' OR text_message LIKE '%$escaped%' 
            LIMIT 5";
    $res = $conn->query($sql);
    $cards = [];
    while ($row = $res->fetch_assoc()) {
        $cards[] = createCard($row['name'], $row['photo'], $row['text_message'], "place.php?id={$row['id']}");
    }
    $reply = $cards ? implode("\n", $cards) : 'Sonuç bulunamadı.';
    $reply .= "<br><br>Başka bir kategori seçmek ister misiniz?" . kategoriButonlari();
    $_SESSION['step'] = 'kategori';
    echo json_encode(['reply' => $reply]);
    exit;
}

// Hiçbir şeye uymadıysa:
$reply = "Mesajınız anlaşılamadı. Lütfen bir kategori seçin:";
$reply .= kategoriButonlari();
$_SESSION['step'] = 'kategori';
echo json_encode(['reply' => $reply]);
?>
