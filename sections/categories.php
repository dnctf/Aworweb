<section id="featured-products" class="content-section bg-light-grey">
    <div class="container">
        <div class="category-showcase">
            <h2 class="category-showcase-title">Produk Unggulan Kami</h2>
            <p style="margin-top:-30px; margin-bottom:50px;">Satu rasa dari setiap cerita yang kami sajikan.</p>
            <div class="product-grid">
                <?php
                if (isset($conn)) {
                    $categories_res = $conn->query("SELECT id FROM product_categories");
                    if ($categories_res && $categories_res->num_rows > 0) {
                        while ($cat = $categories_res->fetch_assoc()) {
                            $category_id = $cat['id'];
                            
                            // PERBAIKAN: Menggunakan MIN(pv.price) untuk mengatasi error ONLY_FULL_GROUP_BY
                            $product_res = $conn->query("
                                SELECT p.*, MIN(pv.price) as price 
                                FROM products p 
                                JOIN product_variants pv ON p.id = pv.product_id 
                                WHERE p.category_id = $category_id AND pv.is_active = 1 
                                GROUP BY p.id 
                                ORDER BY RAND() 
                                LIMIT 1
                            ");
                            
                            if ($product_res && $product_res->num_rows > 0) {
                                $product = $product_res->fetch_assoc();
                ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </a>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="product-price">Mulai Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-add-to-cart">Lihat Detail</a>
                                    </div>
                                </div>
                <?php
                            }
                        }
                    } else {
                        echo "<p>Produk unggulan akan segera hadir.</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</section>