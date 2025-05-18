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

$success_message = "";
$error_message = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'];
    $marka = $_POST['marka'];
    $urun_adi = $_POST['urun_adi'];
    $stok = $_POST['stok'];

    // Alanların boş olup olmadığını kontrol et
    if (empty($kategori) || empty($marka) || empty($urun_adi) || !isset($stok) || $stok < 0) {
        $error_message = "Lütfen tüm alanları doldurun ve geçerli bir stok adedi girin.";
    } else {
        // SQL Injection'ı önlemek için prepare statement kullanın
        $stmt = $conn->prepare("INSERT INTO urunler (kategori, marka, urun_adi, stok) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $kategori, $marka, $urun_adi, $stok);

        if ($stmt->execute()) {
            $success_message = "Ürün başarıyla eklendi.";
            // Form alanlarını temizle
            $kategori = $marka = $urun_adi = $stok = "";
        } else {
            $error_message = "Ürün eklenirken bir hata oluştu: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Ekle</title>
    <link rel="stylesheet" href="add_product.css">
    <link rel="stylesheet" href="index.css"> </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Ürün Ekle</h2>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="menu">
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                 <li><a href="view_all_products.php">Tüm Ürünleri Listele</a></li>
                <li><a href="list_products.php">Stok Sorgula / Satış Yap</a></li>
            </ul>
        </div>

        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="kategori">Kategori:</label>
                    <input type="text" id="kategori" name="kategori" value="<?php echo isset($kategori) ? htmlspecialchars($kategori) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="marka">Marka:</label>
                    <input type="text" id="marka" name="marka" value="<?php echo isset($marka) ? htmlspecialchars($marka) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="urun_adi">Ürün Adı:</label>
                    <input type="text" id="urun_adi" name="urun_adi" value="<?php echo isset($urun_adi) ? htmlspecialchars($urun_adi) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="stok">Stok Adedi:</label>
                    <input type="number" id="stok" name="stok" value="<?php echo isset($stok) ? htmlspecialchars($stok) : ''; ?>" required min="0">
                </div>
                <div class="form-group">
                    <button type="submit">Ürün Ekle</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
