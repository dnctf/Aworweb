<section id="outlets" class="content-section">
    <div class="container">
        <div class="outlet-section-box">
            <h2>Visit Our Outlet</h2>
            <div class="filter-buttons">
                <button class="btn-filter active" data-filter="all">Semua</button>
                <?php
                if (isset($conn)) {
                    $cities_res = $conn->query("SELECT DISTINCT city FROM outlets WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
                    if ($cities_res && $cities_res->num_rows > 0) {
                        while ($city = $cities_res->fetch_assoc()) {
                            echo '<button class="btn-filter" data-filter="'.htmlspecialchars($city['city']).'">'.htmlspecialchars($city['city']).'</button>';
                        }
                    }
                }
                ?>
            </div>
            <div class="outlet-slider-container">
                <button class="slider-arrow prev" id="prev-outlet">&#10094;</button>
                <div class="outlet-slider-wrapper">
                    <div class="outlet-grid-slider">
                        <?php
                        if (isset($conn)) {
                            $outlets_res = $conn->query("SELECT * FROM outlets ORDER BY name ASC");
                            if ($outlets_res && $outlets_res->num_rows > 0):
                                while($outlet = $outlets_res->fetch_assoc()):
                        ?>
                        <div class="outlet-card" data-city="<?php echo htmlspecialchars($outlet['city']); ?>">
                            <div class="outlet-image"><img src="<?php echo htmlspecialchars($outlet['image_path']); ?>" alt="<?php echo htmlspecialchars($outlet['name']); ?>"></div>
                            <div class="outlet-content">
                                <h3><?php echo htmlspecialchars($outlet['name']); ?></h3>
                                <p><?php echo htmlspecialchars($outlet['address']); ?></p>
                                <a href="<?php echo htmlspecialchars($outlet['maps_url'] ?? '#'); ?>" class="btn-details" target="_blank">Lihat Peta</a>
                            </div>
                        </div>
                        <?php
                                endwhile;
                            endif;
                        }
                        ?>
                    </div>
                </div>
                <button class="slider-arrow next" id="next-outlet">&#10095;</button>
            </div>
        </div>
    </div>
</section>
