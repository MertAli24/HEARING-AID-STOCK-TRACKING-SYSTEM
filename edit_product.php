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

$product_id = null;
$product = null;
$error_message = "";
$success_message = "";

// Ürün ID'si URL'den geldiyse
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $product_id = trim($_GET['id']);

    // Ürünü veritabanından çek
    $stmt = $conn->prepare("SELECT id, kategori, marka, urun_adi, stok FROM urunler WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        // Ürün bulunamadı
        $error_message = "Ürün bulunamadı.";
    }
    $stmt->close();

} elseif (isset($_POST['product_id'])) {
    // Form gönderildiyse (güncelleme işlemi)
    $product_id = $_POST['product_id'];
    $kategori = $_POST['kategori'];
    $marka = $_POST['marka'];
    $urun_adi = $_POST['urun_adi'];
    $stok = $_POST['stok'];

    // Alanların boş olup olmadığını kontrol et
     if (empty($kategori) || empty($marka) || empty($urun_adi) || !isset($stok) || $stok < 0) {
        $error_message = "Lütfen tüm alanları doldurun ve geçerli bir stok adedi girin.";
    } else {
        // Ürünü güncelle
        $stmt = $conn->prepare("UPDATE urunler SET kategori = ?, marka = ?, urun_adi = ?, stok = ? WHERE id = ?");
        $stmt->bind_param("sssis", $kategori, $marka, $urun_adi, $stok, $product_id);

        if ($stmt->execute()) {
            $success_message = "Ürün başarıyla güncellendi.";
            // Güncel bilgileri tekrar çek
            $stmt_select_again = $conn->prepare("SELECT id, kategori, marka, urun_adi, stok FROM urunler WHERE id = ?");
            $stmt_select_again->bind_param("i", $product_id);
            $stmt_select_again->execute();
            $result_select_again = $stmt_select_again->get_result();
            if ($result_select_again->num_rows == 1) {
                $product = $result_select_again->fetch_assoc();
            }
            $stmt_select_again->close();

        } else {
            $error_message = "Ürün güncellenirken bir hata oluştu: " . $stmt->error;
        }
        $stmt->close();
    }

} else {
    // Ürün ID'si gelmedi
    $error_message = "Düzenlenecek ürün belirtilmedi.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle</title>
    <link rel="stylesheet" href="add_product.css"> <link rel="stylesheet" href="index.css"> </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Ürün Düzenle</h2>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="menu">
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="add_product.php">Ürün Ekle</a></li>
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

            <?php if ($product): ?>
                <form action="edit_product.php" method="post">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                    <div class="form-group">
                        <label for="kategori">Kategori:</label>
                        <input type="text" id="kategori" name="kategori" value="<?php echo htmlspecialchars($product['kategori']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="marka">Marka:</label>
                        <input type="text" id="marka" name="marka" value="<?php echo htmlspecialchars($product['marka']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="urun_adi">Ürün Adı:</label>
                        <input type="text" id="urun_adi" name="urun_adi" value="<?php echo htmlspecialchars($product['urun_adi']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stok">Stok Adedi:</label>
                        <input type="number" id="stok" name="stok" value="<?php echo htmlspecialchars($product['stok']); ?>" required min="0">
                    </div>
                    <div class="form-group">
                        <button type="submit">Güncelle</button>
                         <a href="view_all_products.php" class="cancel-button">İptal</a> </div>
                </form>
            <?php elseif (empty($error_message)): ?>
                 <?php endif; ?>
        </div>
    </div>
</body>
</html>
