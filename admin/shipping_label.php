<?php
session_start();
// Pastikan hanya admin yang login dan ada order_id yang bisa akses
if (!isset($_SESSION['admin_logged_in']) || !isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    // Arahkan ke halaman login jika akses tidak sah
    header('Location: login.php');
    exit();
}

include '../includes/db.php';

// 1. AMBIL DATA PESANAN DENGAN AMAN
// ===================================
$order_id = (int)$_GET['order_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

// Jika pesanan tidak ditemukan, hentikan eksekusi dengan pesan error
if (!$order) {
    die("Error: Pesanan dengan ID #{$order_id} tidak ditemukan.");
}


// 2. PERBAIKAN: AMBIL DATA PENGIRIM (DARI TABEL 'content')
// =======================================================
$sender_info = [];
$contact_res = $conn->query("SELECT section, description FROM content WHERE section IN ('contact_location_title', 'contact_address', 'contact_phone')");
if ($contact_res) {
    while($row = $contact_res->fetch_assoc()){
        // Mengubah kunci agar lebih mudah digunakan
        $key = str_replace('contact_', '', $row['section']);
        $sender_info[$key] = $row['description'];
    }
}


// 3. AMBIL LOGO TOKO
// =====================
$main_logo = null;
$main_logo_res = $conn->query("SELECT file_path FROM site_assets WHERE asset_name = 'main_logo' LIMIT 1");
if ($main_logo_res) {
    $main_logo = $main_logo_res->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Shipping Label - Order #<?php echo $order['id']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
    <style>
        @media print {
            body, .label { margin: 0; box-shadow: none; border: none; }
        }
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .label { 
            width: 4in; 
            min-height: 6in; 
            padding: 0.25in; 
            border: 1px solid #ccc; 
            background-color: white;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .section { padding-bottom: 10px; margin-bottom: 10px; border-bottom: 2px solid #000; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .header img { max-width: 120px; max-height: 40px; }
        .courier-info { text-align: right; }
        .courier-info h3 { margin: 0; font-size: 1.2em; }
        .recipient, .sender { font-size: 14px; line-height: 1.4; }
        .recipient strong, .sender strong { font-size: 12px; text-transform: uppercase; }
        .footer { text-align: center; margin-top: auto; }
        .barcode { font-family: 'Libre Barcode 39 Text', cursive; font-size: 42px; line-height: 1; }
    </style>
</head>
<body onload="window.print()">
    <div class="label">
        <div class="header section">
            <?php if ($main_logo && !empty($main_logo['file_path'])): ?>
                <img src="../<?php echo htmlspecialchars($main_logo['file_path']); ?>" alt="Logo Toko">
            <?php endif; ?>
            <div class="courier-info">
                <h3><?php echo strtoupper(htmlspecialchars($order['shipping_courier'] ?? 'KURIR')); ?></h3>
            </div>
        </div>
        <div class="recipient section">
            <strong>Kepada:</strong><br>
            <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Nama Pelanggan'); ?></strong><br>
            <?php echo htmlspecialchars($order['customer_phone'] ?? 'No. Telepon'); ?><br>
            <?php echo nl2br(htmlspecialchars($order['customer_address'] ?? 'Alamat Pelanggan')); ?>
        </div>
        <div class="sender section">
            <strong>Dari:</strong><br>
            <strong><?php echo htmlspecialchars($sender_info['location_title'] ?? 'Awor Coffee'); ?></strong><br>
            <?php echo htmlspecialchars($sender_info['phone'] ?? 'No. Telepon Pengirim'); ?><br>
            <?php echo nl2br(htmlspecialchars($sender_info['address'] ?? 'Alamat Pengirim')); ?>
        </div>
        <div class="footer">
            <p>Order ID: #<?php echo $order['id']; ?> | <?php echo date("d/m/Y"); ?></p>
            <div class="barcode">*<?php echo 'AWOR' . str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>*</div>
        </div>
    </div>
</body>
</html>