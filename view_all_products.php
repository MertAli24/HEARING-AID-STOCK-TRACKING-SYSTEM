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

// Arama ve filtreleme parametrelerini al
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_marka = isset($_GET['marka']) ? $_GET['marka'] : '';

// SQL sorgusunu oluştur
$sql = "SELECT id, kategori, marka, urun_adi, stok FROM urunler WHERE 1=1"; // 1=1 her zaman doğru, WHERE koşullarını eklemek için başlangıç noktası

$params = [];
$types = "";

// Arama sorgusu varsa
if (!empty($search_query)) {
    $sql .= " AND (urun_adi LIKE ? OR marka LIKE ? OR kategori LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= "sss";
}

// Kategori filtresi varsa
if (!empty($filter_kategori)) {
    $sql .= " AND kategori = ?";
    $params[] = $filter_kategori;
    $types .= "s";
}

// Marka filtresi varsa
if (!empty($filter_marka)) {
    $sql .= " AND marka = ?";
    $params[] = $filter_marka;
    $types .= "s";
}


$sql .= " ORDER BY kategori, marka, urun_adi";

// SQL Injection'ı önlemek için prepare statement kullanın
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$stmt->close();
$conn->close();

// Tüm kategorileri filtre dropdown'ı için al
$conn_filter_cat = new mysqli($servername, $username, $password, $dbname);
$sql_filter_categories = "SELECT DISTINCT kategori FROM urunler ORDER BY kategori";
$result_filter_categories = $conn_filter_cat->query($sql_filter_categories);
$filter_categories = [];
if ($result_filter_categories->num_rows > 0) {
    while ($row = $result_filter_categories->fetch_assoc()) {
        $filter_categories[] = $row['kategori'];
    }
}
$conn_filter_cat->close();

// Tüm markaları filtre dropdown'ı için al
$conn_filter_brand = new mysqli($servername, $username, $password, $dbname);
$sql_filter_brands = "SELECT DISTINCT marka FROM urunler ORDER BY marka";
$result_filter_brands = $conn_filter_brand->query($sql_filter_brands);
$filter_brands = [];
if ($result_filter_brands->num_rows > 0) {
    while ($row = $result_filter_brands->fetch_assoc()) {
        $filter_brands[] = $row['marka'];
    }
}
$conn_filter_brand->close();

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tüm Ürünler</title>
    <link rel="stylesheet" href="view_all_products.css">
    <link rel="stylesheet" href="index.css"> </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Tüm Ürünler</h2>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="menu">
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="add_product.php">Ürün Ekle</a></li>
                <li><a href="list_products.php">Stok Sorgula / Satış Yap</a></li>
            </ul>
        </div>

        <div class="content">
            <div class="filter-form">
                <form action="view_all_products.php" method="get">
                    <div class="form-group">
                        <label for="search">Arama:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Ürün adı, marka veya kategori ara...">
                    </div>
                    <div class="form-group">
                        <label for="kategori">Kategori Filtrele:</label>
                        <select id="kategori" name="kategori">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($filter_categories as $kategori): ?>
                                <option value="<?php echo htmlspecialchars($kategori); ?>" <?php echo ($filter_kategori === $kategori) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kategori); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="form-group">
                        <label for="marka">Marka Filtrele:</label>
                        <select id="marka" name="marka">
                            <option value="">Tüm Markalar</option>
                            <?php foreach ($filter_brands as $marka): ?>
                                <option value="<?php echo htmlspecialchars($marka); ?>" <?php echo ($filter_marka === $marka) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($marka); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Filtrele / Ara</button>
                    </div>
                </form>
            </div>

            <?php if (empty($products)): ?>
                <p>Aradığınız kriterlere uygun ürün bulunamadı.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kategori</th>
                            <th>Marka</th>
                            <th>Ürün Adı</th>
                            <th>Stok Adedi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr data-product-id="<?php echo htmlspecialchars($product['id']); ?>"> <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['kategori']); ?></td>
                                <td><?php echo htmlspecialchars($product['marka']); ?></td>
                                <td><?php echo htmlspecialchars($product['urun_adi']); ?></td>
                                <td class="stock-cell <?php echo ($product['stok'] <= 5 && $product['stok'] > 0) ? 'low-stock' : ($product['stok'] == 0 ? 'out-of-stock' : ''); ?>">
                                    <span class="current-stock"><?php echo htmlspecialchars($product['stok']); ?></span> </td>
                                <td class="actions">
                                    <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="edit-link">Düzenle</a>
                                    <button class="sell-button" data-product-id="<?php echo htmlspecialchars($product['id']); ?>" <?php echo ($product['stok'] <= 0) ? 'disabled' : ''; ?>>SAT</button>
                                    <a href="delete_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="delete-link" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sellButtons = document.querySelectorAll('.sell-button');

            sellButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const row = this.closest('tr'); // Butonun bulunduğu satırı bul
                    const stockSpan = row.querySelector('.current-stock'); // Stok adedini gösteren span'i bul
                    const sellButton = this; // Tıklanan butonu referans al

                    if (confirm('Bu üründen bir adet satmak istediğinizden emin misiniz?')) {
                        // AJAX isteği gönder
                        // list_products.php'deki satış işlemini kullanmak yerine,
                        // doğrudan bu sayfaya (view_all_products.php) bir AJAX isteği gönderebiliriz
                        // ve burada satış işlemini yapabiliriz.
                        // Ancak list_products.php'deki mevcut AJAX endpoint'ini kullanmak daha modüler.
                        // Bu nedenle list_products.php'ye istek göndermeye devam ediyoruz.
                        fetch('list_products.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirt
                            },
                            body: 'action=sell_product&product_id=' + encodeURIComponent(productId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Stok adedini güncelle
                                stockSpan.textContent = data.new_stock;

                                // Stok adedi 0 veya altına düşerse butonu devre dışı bırak
                                if (data.new_stock <= 0) {
                                    sellButton.disabled = true;
                                    // Stok hücresinin rengini güncelle
                                    const stockCell = row.querySelector('.stock-cell');
                                    stockCell.classList.remove('low-stock');
                                    stockCell.classList.add('out-of-stock');
                                } else if (data.new_stock <= 5) { // Düşük stok seviyesi
                                     const stockCell = row.querySelector('.stock-cell');
                                     stockCell.classList.add('low-stock');
                                     stockCell.classList.remove('out-of-stock');
                                } else { // Yeterli stok
                                     const stockCell = row.querySelector('.stock-cell');
                                     stockCell.classList.remove('low-stock');
                                     stockCell.classList.remove('out-of-stock');
                                }

                                console.log("Satış başarılı. Yeni stok: " + data.new_stock);
                            } else {
                                // Hata mesajı göster
                                console.error("Satış yapılırken hata: " + data.message);
                                alert("Satış yapılırken hata: " + data.message);
                            }
                        })
                        .catch(error => console.error('Satış işlemi sırasında hata:', error));
                    }
                });
            });
        });
    </script>
</body>
</html>
