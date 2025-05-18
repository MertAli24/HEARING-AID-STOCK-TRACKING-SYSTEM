<?php
session_start(); // Oturumu başlat

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Veritabanı bağlantı bilgileri
$servername = "localhost"; // Genellikle XAMPP için localhost
$username = "root"; // XAMPP varsayılan kullanıcı adı
$password = ""; // XAMPP varsayılan şifre (genellikle boş)
$dbname = "isitme_cihazlari_stok"; // Oluşturduğumuz veritabanı adı

// Veritabanı bağlantısını oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Kullanıcı adını oturumdan al
$current_username = $_SESSION['username'];

// --- Ana Sayfa İçin Özet Bilgileri Çekme (Örnek) ---
// Gerçek verileri çekmek için bu kısımları geliştirebilirsiniz
$total_products = 0;
$low_stock_count = 0; // Stok adedi 5 veya altı olanlar
$out_of_stock_count = 0; // Stok adedi 0 olanlar

$sql_summary = "SELECT COUNT(*) AS total_products,
                       SUM(CASE WHEN stok <= 5 AND stok > 0 THEN 1 ELSE 0 END) AS low_stock_count,
                       SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS out_of_stock_count
                FROM urunler";
$result_summary = $conn->query($sql_summary);

if ($result_summary && $result_summary->num_rows > 0) {
    $summary_row = $result_summary->fetch_assoc();
    $total_products = $summary_row['total_products'];
    $low_stock_count = $summary_row['low_stock_count'];
    $out_of_stock_count = $summary_row['out_of_stock_count'];
}

// Son eklenen ürünleri çekme (Örnek - Son 5 ürün)
$latest_products = [];
$sql_latest = "SELECT urun_adi, stok FROM urunler ORDER BY id DESC LIMIT 5";
$result_latest = $conn->query($sql_latest);
if ($result_latest && $result_latest->num_rows > 0) {
    while($row = $result_latest->fetch_assoc()) {
        $latest_products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Hoş Geldiniz, <?php echo htmlspecialchars($current_username); ?>!</h2>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="menu">
            <ul>
                <li><a href="add_product.php">Ürün Ekle</a></li>
                <li><a href="view_all_products.php">Tüm Ürünleri Listele</a></li>
                <li><a href="list_products.php">Stok Sorgula / Satış Yap</a></li>
                </ul>
        </div>

        <div class="content">
            <h3>Panel Özeti</h3>

            <div class="summary-cards">
                <div class="card">
                    <h4>Toplam Ürün Sayısı</h4>
                    <p class="count"><?php echo $total_products; ?></p>
                </div>
                <div class="card low-stock">
                    <h4>Düşük Stoklu Ürünler</h4>
                    <p class="count"><?php echo $low_stock_count; ?></p>
                </div>
                <div class="card out-of-stock">
                    <h4>Stokta Olmayan Ürünler</h4>
                    <p class="count"><?php echo $out_of_stock_count; ?></p>
                </div>
            </div>

            <div class="latest-products">
                <h4>Son Eklenen Ürünler</h4>
                <?php if (empty($latest_products)): ?>
                    <p>Son eklenen ürün bulunmamaktadır.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($latest_products as $product): ?>
                            <li><?php echo htmlspecialchars($product['urun_adi']); ?> (Stok: <?php echo htmlspecialchars($product['stok']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

             <div class="quick-links">
                <h4>Hızlı Erişim</h4>
                <ul>
                    <li><a href="add_product.php">Yeni Ürün Ekle</a></li>
                    <li><a href="view_all_products.php">Ürünleri Listele ve Yönet</a></li>
                    <li><a href="list_products.php">Stok Sorgulama ve Satış</a></li>
                </ul>
            </div>

        </div>
    </div>
</body>
</html>
