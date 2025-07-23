<?php
session_start();
include_once 'includes/db.php';

// 1. DAPATKAN ID PRODUK & PASTIKAN VALID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: shop.php');
    exit();
}
$product_id = (int)$_GET['id'];

// 2. LOGIKA TAMBAH KE KERANJANG
$notification = '';
$notification_type = 'success'; // Tipe notifikasi: 'success' atau 'error'

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
    $variant_id = $_POST['variant_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($variant_id && $quantity > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        // Menambah kuantitas jika produk sudah ada di keranjang
        $_SESSION['cart'][$variant_id] = ($_SESSION['cart'][$variant_id] ?? 0) + $quantity;
        
        $notification = 'Produk berhasil ditambahkan ke keranjang!';
        $notification_type = 'success';
    } else {
        $notification = 'Gagal menambahkan ke keranjang. Silakan pilih varian terlebih dahulu.';
        $notification_type = 'error';
    }
}


// 3. AMBIL DATA PRODUK UTAMA
$stmt_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt_product->bind_param("i", $product_id);
$stmt_product->execute();
$product_result = $stmt_product->get_result();

if ($product_result->num_rows === 0) {
    header('Location: shop.php');
    exit();
}
$product = $product_result->fetch_assoc();

// 4. AMBIL SEMUA GAMBAR PRODUK (UTAMA + GALERI)
$product_images = [];
if (!empty($product['image_path'])) {
    $product_images[] = $product['image_path'];
}
$stmt_gallery = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
$stmt_gallery->bind_param("i", $product_id);
$stmt_gallery->execute();
$gallery_result = $stmt_gallery->get_result();
while ($row = $gallery_result->fetch_assoc()) {
    $product_images[] = $row['image_path'];
}
// Jika tidak ada gambar sama sekali, gunakan placeholder
if (empty($product_images)) {
    $product_images[] = 'uploads/products/placeholder.jpg';
}


// 5. AMBIL DAN KELOMPOKKAN VARIAN YANG AKTIF
$variants_raw = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id AND is_active = 1 ORDER BY variant_group, id ASC")->fetch_all(MYSQLI_ASSOC);
$variant_groups = [];
foreach ($variants_raw as $variant) {
    $variant_groups[$variant['variant_group']][] = $variant;
}

include_once 'includes/header.php';
?>

<style>
    .product-detail-container {
        display: grid; grid-template-columns: 1fr; gap: 30px; margin: 40px auto; max-width: 1100px;
    }
    @media (min-width: 768px) {
        .product-detail-container { grid-template-columns: 1fr 1fr; gap: 50px; }
    }
    .product-gallery { display: flex; flex-direction: column; gap: 15px; }
    .main-image-container { border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); aspect-ratio: 1 / 1; }
    .main-image-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .thumbnail-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; }
    .thumbnail { border: 2px solid #eee; border-radius: 8px; overflow: hidden; cursor: pointer; aspect-ratio: 1 / 1; transition: border-color 0.3s ease; }
    .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
    .thumbnail:hover, .thumbnail.active { border-color: #333; }
    .product-detail-info h1 { font-size: 2.5em; margin-top: 0; margin-bottom: 15px; }
    .product-price-display { font-size: 2em; font-weight: 600; color: #e74c3c; margin-bottom: 25px; }
    .product-description { color: #555; line-height: 1.7; margin-bottom: 30px; }
    .variants-form .form-group { margin-bottom: 20px; }
    .variants-form label { font-weight: 600; display: block; margin-bottom: 10px; }
    .variant-options { display: flex; flex-wrap: wrap; gap: 10px; }
    .variant-options .variant-label { display: block; padding: 10px 20px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
    .variant-options input[type="radio"] { display: none; }
    .variant-options input[type="radio"]:checked + .variant-label { border-color: #333; background-color: #f0f0f0; font-weight: bold; }
    .quantity-selector { display: flex; align-items: center; }
    .quantity-btn { width: 40px; height: 40px; border: 1px solid #ddd; background-color: #f9f9f9; cursor: pointer; font-size: 1.5em; line-height: 38px; text-align: center; }
    .quantity-input { width: 60px; height: 40px; text-align: center; border: 1px solid #ddd; border-left: none; border-right: none; box-sizing: border-box; -moz-appearance: textfield; }
    .quantity-input::-webkit-outer-spin-button, .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .notification { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
    .notification.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .notification.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<main id="main-content">
    <div class="container">
        <?php if ($notification): ?>
            <div class="notification <?php echo $notification_type; ?>">
                <?php echo htmlspecialchars($notification); ?>
                <?php if ($notification_type === 'success'): ?>
                    <a href="cart.php" style="margin-left: 15px; font-weight: bold;">Lihat Keranjang</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="product-detail-container">
            <div class="product-gallery">
                <div class="main-image-container">
                    <img src="<?php echo htmlspecialchars($product_images[0]); ?>" alt="Gambar utama <?php echo htmlspecialchars($product['name']); ?>" id="mainProductImage">
                </div>
                <?php if (count($product_images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach($product_images as $index => $img_path): ?>
                    <div class="thumbnail <?php echo ($index == 0) ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img_path); ?>', this)">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-price-display" id="price-display">Pilih Opsi untuk Melihat Harga</div>
                <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="variants-form" id="add-to-cart-form">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="variant_id" id="selected_variant_id" value="">
                    
                    <?php foreach ($variant_groups as $group_name => $options): ?>
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($group_name); ?>:</label>
                        <div class="variant-options">
                            <?php foreach($options as $option): ?>
                            <div>
                                <input type="radio" name="variant_option_<?php echo md5($group_name); ?>" id="variant_<?php echo $option['id']; ?>" value="<?php echo $option['id']; ?>" data-price="<?php echo $option['price']; ?>">
                                <label for="variant_<?php echo $option['id']; ?>" class="variant-label">
                                    <?php echo htmlspecialchars($option['variant_option']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="form-group">
                        <label for="quantity">Jumlah:</label>
                        <div class="quantity-selector">
                             <button type="button" class="quantity-btn" id="btn-minus">-</button>
                             <input type="number" class="quantity-input" id="quantity" name="quantity" value="1" min="1">
                             <button type="button" class="quantity-btn" id="btn-plus">+</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-add-to-cart" style="width: 100%; padding: 15px; font-size: 1.1em;">+ Tambah ke Keranjang</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    function changeImage(newImageSrc, clickedThumbnail) {
        document.getElementById('mainProductImage').src = newImageSrc;
        document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
        clickedThumbnail.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const priceDisplay = document.getElementById('price-display');
        const selectedVariantInput = document.getElementById('selected_variant_id');
        const variantRadios = document.querySelectorAll('input[type="radio"]');
        const form = document.getElementById('add-to-cart-form');
        const btnMinus = document.getElementById('btn-minus');
        const btnPlus = document.getElementById('btn-plus');
        const quantityInput = document.getElementById('quantity');

        if(btnMinus) {
            btnMinus.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) { quantityInput.value = currentValue - 1; }
            });
        }
        
        if(btnPlus) {
            btnPlus.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                quantityInput.value = currentValue + 1;
            });
        }

        function updatePriceAndSelection() {
            const selectedRadio = document.querySelector('input[type="radio"]:checked');
            if (selectedRadio) {
                const price = parseFloat(selectedRadio.dataset.price);
                priceDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
                selectedVariantInput.value = selectedRadio.value;
            } else {
                priceDisplay.textContent = 'Pilih Opsi untuk Melihat Harga';
                selectedVariantInput.value = '';
            }
        }
        variantRadios.forEach(radio => radio.addEventListener('change', updatePriceAndSelection));

        if(form) {
            form.addEventListener('submit', function(event) {
                const totalGroups = <?php echo count($variant_groups); ?>;
                const selectedCount = document.querySelectorAll('input[type="radio"]:checked').length;
                
                // Cek apakah semua grup varian sudah dipilih
                if (totalGroups > 0 && selectedCount < totalGroups) {
                    event.preventDefault();
                    alert('Silakan pilih satu opsi dari setiap grup varian.');
                }
            });
        }

        updatePriceAndSelection(); // Inisialisasi tampilan harga
    });
</script>

<?php 
include 'includes/footer.php'; 
?>