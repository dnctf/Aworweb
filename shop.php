<?php
session_start();
include_once 'includes/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Logika add to cart dipindahkan sepenuhnya ke product_detail.php dan cart_handler.php
// Bagian ini tidak lagi diperlukan di sini.

include_once 'includes/header.php'; 
?>
<main id="main-content">
    <div class="container shop-page-container">
        <h1>Our Products</h1>
        <?php
        if (isset($conn)) {
            $categories_result = $conn->query("SELECT * FROM product_categories ORDER BY id ASC");
            if ($categories_result && $categories_result->num_rows > 0) {
                while ($category = $categories_result->fetch_assoc()) {
        ?>
                    <section class="product-category-section" id="category-<?php echo $category['id']; ?>">
                        <h2 class="product-category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
                        <div class="product-grid">
                            <?php
                            $category_id = $category['id'];
                            $products_stmt = $conn->prepare("
                                SELECT 
                                    p.*, 
                                    (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id) as min_price,
                                    (SELECT COUNT(pv.id) FROM product_variants pv WHERE pv.product_id = p.id) as variant_count
                                FROM products p 
                                WHERE p.category_id = ?
                            ");
                            $products_stmt->bind_param("i", $category_id);
                            $products_stmt->execute();
                            $products_result = $products_stmt->get_result();

                            if ($products_result->num_rows > 0) {
                                while ($product = $products_result->fetch_assoc()) {
                            ?>
                                    <div class="product-card">
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" style="text-decoration:none; color:inherit;">
                                            <div class="product-image"><img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"></div>
                                            <div class="product-info">
                                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                                <p class="product-price">
                                                    <?php 
                                                    if($product['min_price']) {
                                                        echo 'Mulai Rp ' . number_format($product['min_price'], 0, ',', '.');
                                                    } else {
                                                        echo 'Lihat Opsi';
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </a>
                                        <div class="product-info" style="padding-top:0;">
                                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-add-to-cart">
                                                <?php echo ($product['variant_count'] > 1) ? 'Pilih Varian' : 'Lihat Produk'; ?>
                                            </a>
                                        </div>
                                    </div>
                            <?php
                                }
                            } else {
                                echo "<p>Produk untuk kategori ini akan segera hadir.</p>";
                            }
                            $products_stmt->close();
                            ?>
                        </div>
                    </section>
        <?php
                }
            }
        }
        ?>
    </div>
</main>
<?php 
include 'includes/footer.php'; 
?>