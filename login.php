<?php
session_start(); // Oturumu başlat

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

$error_message = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // SQL Injection'ı önlemek için prepare statement kullanın
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Kullanıcı bulundu, şifreyi doğrula
        $row = $result->fetch_assoc();
        // Gerçek uygulamada hashlenmiş şifre doğrulaması yapın
        // if (password_verify($input_password, $row['password'])) {
        if ($input_password === $row['password']) { // Basitlik için düz metin karşılaştırması
            // Giriş başarılı, oturum değişkenlerini ayarla
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['userid'] = $row['id'];

            // Yönlendirme
            header("location: index.php"); // Başarılı giriş sonrası yönlendirilecek sayfa
            exit;
        } else {
            $error_message = "Hatalı şifre.";
        }
    } else {
        $error_message = "Kullanıcı bulunamadı.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h2>Admin Giriş</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Giriş Yap</button>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
