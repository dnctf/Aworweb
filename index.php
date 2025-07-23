<?php
// Memulai session di halaman utama sangat penting untuk fitur keranjang
session_start(); 
include_once 'includes/db.php';
include_once 'includes/header.php';

// Logika untuk menghitung pengunjung (opsional, bisa disimpan)
if (isset($conn)) {
    $today = date("Y-m-d");
    $conn->query("INSERT INTO visitors (visit_date, visit_count) VALUES ('$today', 1) ON DUPLICATE KEY UPDATE visit_count = visit_count + 1");
}
?>
<main id="main-content">
    <?php
        // Memuat semua bagian halaman utama
        include 'sections/hero.php';
        include 'sections/categories.php';
        include 'sections/outlets.php';
        include 'sections/contact.php';
    ?>
</main>
<?php
include_once 'includes/footer.php';
?>