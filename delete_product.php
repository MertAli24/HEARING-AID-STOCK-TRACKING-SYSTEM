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

$product_id_to_delete = null;

// Ürün ID'si URL'den geldiyse
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $product_id_to_delete = trim($_GET['id']);

    // SQL Injection'ı önlemek için prepare statement kullanın
    $stmt = $conn->prepare("DELETE FROM urunler WHERE id = ?");
    $stmt->bind_param("i", $product_id_to_delete);

    if ($stmt->execute()) {
        // Silme başarılı
        // Tüm ürünleri listeleme sayfasına yönlendir
        header("location: view_all_products.php?status=deleted"); // Başarılı silme mesajı için parametre eklenebilir
        exit;
    } else {
        // Silme başarısız
        echo "Hata: Ürün silinirken bir sorun oluştu. " . $stmt->error;
    }

    $stmt->close();

} else {
    // Ürün ID'si gelmedi
    echo "Silinecek ürün belirtilmedi.";
}

$conn->close();
?>
