<section id="categories" class="content-section bg-light-grey fade-in-section">
    <div class="container">
        <div class="category-showcase">
            <h2 class="category-showcase-title">Jelajahi Kategori Produk Kami</h2>
            <div class="category-grid">
                <?php
                if (isset($conn)) {
                    $showcase_result = $conn->query("SELECT id, name FROM product_categories ORDER BY id ASC");
                    if ($showcase_result && $showcase_result->num_rows > 0) {
                        while ($category_item = $showcase_result->fetch_assoc()) {
                            // Membuat nama file gambar secara dinamis dari nama kategori
                            $image_name = 'cat_' . strtolower(str_replace(' ', '_', $category_item['name'])) . '.png';
                ?>
                    <a href="shop.php#category-<?php echo $category_item['id']; ?>" class="category-item">
                        <img src="uploads/categories/<?php echo $image_name; ?>" alt="<?php echo htmlspecialchars($category_item['name']); ?>">
                        <h3><?php echo htmlspecialchars($category_item['name']); ?></h3>
                    </a>
                <?php
                        }
                    } else {
                        echo "<p>Kategori produk belum tersedia.</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</section>