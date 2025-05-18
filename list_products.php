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

// AJAX isteği gelirse (kategoriye göre markaları getir)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && isset($_POST['action']) && $_POST['action'] === 'get_brands') {
    $selected_kategori = $_POST['kategori'];
    $stmt = $conn->prepare("SELECT DISTINCT marka FROM urunler WHERE kategori = ? ORDER BY marka");
    $stmt->bind_param("s", $selected_kategori);
    $stmt->execute();
    $result = $stmt->get_result();
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row['marka'];
    }
    $stmt->close();
    echo json_encode($brands);
    $conn->close();
    exit; // AJAX isteği bitti
}

// AJAX isteği gelirse (markaya göre ürün adlarını getir)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && isset($_POST['action']) && $_POST['action'] === 'get_products') {
    $selected_kategori = $_POST['kategori'];
    $selected_marka = $_POST['marka'];
    $stmt = $conn->prepare("SELECT id, urun_adi FROM urunler WHERE kategori = ? AND marka = ? ORDER BY urun_adi");
    $stmt->bind_param("ss", $selected_kategori, $selected_marka);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = ['id' => $row['id'], 'urun_adi' => $row['urun_adi']];
    }
    $stmt->close();
    echo json_encode($products);
    $conn->close();
    exit; // AJAX isteği bitti
}

// AJAX isteği gelirse (ürün id'sine göre stok bilgisini getir)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && isset($_POST['action']) && $_POST['action'] === 'get_stock') {
    $selected_product_id = $_POST['product_id'];
    $stmt = $conn->prepare("SELECT stok FROM urunler WHERE id = ?");
    $stmt->bind_param("i", $selected_product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stock = $row['stok'];
    }
    $stmt->close();
    echo json_encode(['stok' => $stock]);
    $conn->close();
    exit; // AJAX isteği bitti
}

// AJAX isteği gelirse (ürün satışı yap ve stoğu düşür)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && isset($_POST['action']) && $_POST['action'] === 'sell_product') {
    $product_id_to_sell = $_POST['product_id'];
    $quantity_to_sell = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; // Satılacak adet sayısını al, varsayılan 1

    // Girilen adet geçerli mi kontrol et
    if ($quantity_to_sell <= 0) {
         echo json_encode(['success' => false, 'message' => 'Satış adedi pozitif bir sayı olmalıdır.']);
         $conn->close();
         exit;
    }

    // Ürünün mevcut stokunu al
    $stmt_select = $conn->prepare("SELECT stok FROM urunler WHERE id = ?");
    $stmt_select->bind_param("i", $product_id_to_sell);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows > 0) {
        $row_select = $result_select->fetch_assoc();
        $current_stock = $row_select['stok'];

        // Satılacak adet mevcut stoktan fazla mı kontrol et
        if ($quantity_to_sell > $current_stock) {
             echo json_encode(['success' => false, 'message' => 'Satmak istediğiniz adet mevcut stoktan fazla. (Mevcut: ' . $current_stock . ')']);
             $stmt_select->close();
             $conn->close();
             exit;
        }


        if ($current_stock > 0) {
            // Stoğu belirtilen adet kadar azalt
            $new_stock = $current_stock - $quantity_to_sell;
            $stmt_update = $conn->prepare("UPDATE urunler SET stok = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $new_stock, $product_id_to_sell);

            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'new_stock' => $new_stock]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Stok güncellenirken hata oluştu: ' . $stmt_update->error]);
            }
            $stmt_update->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Stok 0 olduğu için satış yapılamaz.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
    }

    $stmt_select->close();
    $conn->close();
    exit; // AJAX isteği bitti
}


// Sayfa ilk yüklendiğinde tüm kategorileri al
$categories = [];
$sql_categories = "SELECT DISTINCT kategori FROM urunler ORDER BY kategori";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row['kategori'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürünleri Listele / Stok Sorgula</title>
    <link rel="stylesheet" href="list_products.css">
    <link rel="stylesheet" href="index.css"> </head>
<body>
    <div class="container">
        <div class="header">
            <h2>Ürünleri Listele / Stok Sorgula</h2>
            <a href="logout.php" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="menu">
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="add_product.php">Ürün Ekle</a></li>
                <li><a href="view_all_products.php">Tüm Ürünleri Listele</a></li>
            </ul>
        </div>

        <div class="content">
            <div class="stock-query-form">
                <div class="form-group">
                    <label for="kategori">Kategori:</label>
                    <select id="kategori" name="kategori">
                        <option value="">Kategori Seçin</option>
                        <?php foreach ($categories as $kategori): ?>
                            <option value="<?php echo htmlspecialchars($kategori); ?>"><?php htmlspecialchars($kategori); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marka">Marka:</label>
                    <select id="marka" name="marka" disabled>
                        <option value="">Marka Seçin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="urun_adi">Ürün Adı:</label>
                    <select id="urun_adi" name="urun_adi" disabled>
                        <option value="">Ürün Seçin</option>
                    </select>
                </div>

                <button id="query-stock-button" disabled>Stok Sorgula</button>
            </div>

            <div id="stock-info" class="stock-info">
                </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriSelect = document.getElementById('kategori');
            const markaSelect = document.getElementById('marka');
            const urunAdiSelect = document.getElementById('urun_adi');
            const queryStockButton = document.getElementById('query-stock-button');
            const stockInfoDiv = document.getElementById('stock-info');

            // Kategori seçimi değiştiğinde markaları getir
            kategoriSelect.addEventListener('change', function() {
                const selectedKategori = this.value;
                markaSelect.innerHTML = '<option value="">Marka Seçin</option>'; // Marka listesini temizle
                urunAdiSelect.innerHTML = '<option value="">Ürün Seçin</option>'; // Ürün listesini temizle
                markaSelect.disabled = true;
                urunAdiSelect.disabled = true;
                queryStockButton.disabled = true;
                stockInfoDiv.innerHTML = ''; // Stok bilgisini temizle

                if (selectedKategori) {
                    fetch('list_products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirt
                        },
                        body: 'action=get_brands&kategori=' + encodeURIComponent(selectedKategori)
                    })
                    .then(response => response.json())
                    .then(brands => {
                        brands.forEach(brand => {
                            const option = document.createElement('option');
                            option.value = brand;
                            option.textContent = brand;
                            markaSelect.appendChild(option);
                        });
                        markaSelect.disabled = false;
                    })
                    .catch(error => console.error('Markalar getirilirken hata:', error));
                }
            });

            // Marka seçimi değiştiğinde ürün adlarını getir
            markaSelect.addEventListener('change', function() {
                const selectedKategori = kategoriSelect.value;
                const selectedMarka = this.value;
                urunAdiSelect.innerHTML = '<option value="">Ürün Seçin</option>'; // Ürün listesini temizle
                urunAdiSelect.disabled = true;
                queryStockButton.disabled = true;
                stockInfoDiv.innerHTML = ''; // Stok bilgisini temizle


                if (selectedKategori && selectedMarka) {
                     fetch('list_products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirt
                        },
                        body: 'action=get_products&kategori=' + encodeURIComponent(selectedKategori) + '&marka=' + encodeURIComponent(selectedMarka)
                    })
                    .then(response => response.json())
                    .then(products => {
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id; // Ürün ID'sini value olarak kullan
                            option.textContent = product.urun_adi;
                            urunAdiSelect.appendChild(option);
                        });
                        urunAdiSelect.disabled = false;
                    })
                    .catch(error => console.error('Ürünler getirilirken hata:', error));
                }
            });

            // Ürün adı seçimi değiştiğinde Stok Sorgula butonunu aktif et
            urunAdiSelect.addEventListener('change', function() {
                const selectedProductId = this.value;
                queryStockButton.disabled = !selectedProductId; // Eğer ürün seçildiyse butonu aktif et
                stockInfoDiv.innerHTML = ''; // Stok bilgisini temizle
            });


            // Stok Sorgula butonuna tıklandığında stok bilgisini getir
            queryStockButton.addEventListener('click', function() {
                const selectedProductId = urunAdiSelect.value;

                if (selectedProductId) {
                    fetch('list_products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirt
                        },
                        body: 'action=get_stock&product_id=' + encodeURIComponent(selectedProductId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        displayStockInfo(data.stok, selectedProductId);
                    })
                    .catch(error => console.error('Stok bilgisi getirilirken hata:', error));
                }
            });

            // Stok bilgisini gösteren ve SAT butonu ekleyen fonksiyon
            function displayStockInfo(stock, productId) {
                 stockInfoDiv.innerHTML = `
                    <p><strong>Stok Adedi:</strong> <span id="current-stock">${stock}</span></p>
                    <button class="sell-button" data-product-id="${productId}" data-current-stock="${stock}" ${stock <= 0 ? 'disabled' : ''}>SAT</button>
                `;

                // SAT butonuna click event listener ekle
                const sellButton = stockInfoDiv.querySelector('.sell-button');
                if (sellButton) {
                    sellButton.addEventListener('click', handleSellClick);
                }
            }

            // SAT butonuna tıklandığında çalışacak fonksiyon
            function handleSellClick() {
                const productId = this.dataset.productId;
                 const currentStock = parseInt(this.dataset.currentStock); // Güncel stok bilgisini al
                const stockSpan = document.getElementById('current-stock');
                const sellButton = this;

                 // Satılacak adet sayısını kullanıcıdan al
                const quantityToSell = prompt('Kaç adet satmak istiyorsunuz? (Mevcut stok: ' + currentStock + ')');

                // Kullanıcı iptal ederse veya boş/geçersiz giriş yaparsa
                if (quantityToSell === null || quantityToSell.trim() === '' || isNaN(quantityToSell) || parseInt(quantityToSell) <= 0) {
                    alert('Geçerli bir adet girmediniz veya işlemi iptal ettiniz.');
                    return; // İşlemi durdur
                }

                const sellQuantity = parseInt(quantityToSell);

                // Girilen adet mevcut stoktan fazla mı kontrol et
                if (sellQuantity > currentStock) {
                    alert('Satmak istediğiniz adet mevcut stoktan fazla! (Mevcut stok: ' + currentStock + ')');
                    return; // İşlemi durdur
                }

                fetch('list_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                         'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirt
                    },
                    body: 'action=sell_product&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(sellQuantity) // Adet bilgisini ekle
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentStockSpan.textContent = data.new_stock; // Yeni stoğu güncelle
                         sellButton.dataset.currentStock = data.new_stock; // Butonun data-current-stock özniteliğini güncelle

                        if (data.new_stock <= 0) {
                            sellButton.disabled = true; // Stok 0 veya altındaysa butonu devre dışı bırak
                        }
                        // İsteğe bağlı: Başarı mesajı göster
                        console.log("Satış başarılı. Yeni stok: " + data.new_stock);
                    } else {
                        // Hata mesajı göster
                         console.error("Satış yapılırken hata: " + data.message);
                         alert("Satış yapılırken hata: " + data.message); // Kullanıcıya hata mesajı göster
                    }
                })
                .catch(error => console.error('Satış işlemi sırasında hata:', error));
            }
        });
    </script>
</body>
</html>
