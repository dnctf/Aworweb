<section id="hero" class="hero-section">
    <div id="hero-slider">
        <?php
            $slides = [];
            if (isset($conn)) {
                $slides_result = $conn->query("SELECT file_path FROM images WHERE category='hero_slider'");
                if ($slides_result && $slides_result->num_rows > 0) { while($row = $slides_result->fetch_assoc()) { $slides[] = $row; } }
            }
        ?>
        <?php if (!empty($slides)): ?>
            <?php foreach ($slides as $index => $slide): ?>
                <div class="slide <?php echo ($index === 0) ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($slide['file_path']); ?>');"></div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="slide active" style="background-color: #ddd;"></div>
        <?php endif; ?>
    </div>
    <div class="hero-content-overlay">
        <?php
            $hero_logo = (isset($conn)) ? $conn->query("SELECT * FROM site_assets WHERE asset_name = 'hero_logo' LIMIT 1")->fetch_assoc() : null;
            if ($hero_logo) {
                echo '<img src="'.htmlspecialchars($hero_logo['file_path']).'" alt="'.htmlspecialchars($hero_logo['alt_text']).'" class="hero-logo" style="width: '.htmlspecialchars($hero_logo['width_px']).'px;">';
            }
            $hero_text = (isset($conn)) ? $conn->query("SELECT * FROM content WHERE section='hero'")->fetch_assoc() : null;
        ?>
        <h1><?php echo htmlspecialchars($hero_text['title'] ?? 'Selamat Datang'); ?></h1>
        <p><?php echo htmlspecialchars($hero_text['description'] ?? ''); ?></p>
    </div>
</section>