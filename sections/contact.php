<section id="contact" class="content-section">
    <div class="container">
        <h2>Hubungi Kami</h2>
        <div class="contact-grid">
            <div class="contact-details">
                <?php
                $contact_data = [];
                if (isset($conn)) {
                    $q = $conn->query("SELECT section, description FROM content WHERE section LIKE 'contact_%'");
                    while($r = $q->fetch_assoc()) { $contact_data[$r['section']] = $r['description']; }
                }
                ?>
                <h4><strong><?php echo htmlspecialchars($contact_data['contact_location_title'] ?? 'Awor HQ'); ?></strong></h4>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($contact_data['contact_address'] ?? ''); ?></span></li>
                    <li><i class="fas fa-phone"></i><span><?php echo htmlspecialchars($contact_data['contact_phone'] ?? ''); ?></span></li>
                    <li><i class="fas fa-envelope"></i><span><?php echo htmlspecialchars($contact_data['contact_email'] ?? ''); ?></span></li>
                </ul>
            </div>
            <div class="contact-map">
                <iframe src="<?php echo htmlspecialchars($contact_data['contact_maps_url'] ?? ''); ?>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>
