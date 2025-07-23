<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !isset($_GET['order_id'])) {
    header('Location: login.php');
    exit;
}
include '../includes/db.php';

$order_id = (int)$_GET['order_id'];

// Ambil data order
$stmt = $conn->prepare("SELECT o.*, pm.name as payment_method_name FROM orders o LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Error: Pesanan #{$order_id} tidak ditemukan.");
}

// Ambil item-item order
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil data pengirim/toko dari tabel 'content'
$sender_info = [];
$contact_res = $conn->query("SELECT section, description FROM content WHERE section IN ('contact_location_title', 'contact_address', 'contact_phone', 'contact_email')");
if ($contact_res) {
    while($row = $contact_res->fetch_assoc()){
        $key = str_replace('contact_', '', $row['section']);
        $sender_info[$key] = $row['description'];
    }
}

// Ambil logo toko
$logo_res = $conn->query("SELECT file_path FROM site_assets WHERE asset_name = 'main_logo' LIMIT 1");
$logo = $logo_res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order['id']; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 10pt;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .invoice-box {
            width: 297mm;
            height: 209mm; /* A4 Landscape height - a little less for padding */
            padding: 12mm;
            box-sizing: border-box;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .header .logo { max-width: 150px; max-height: 50px; }
        .invoice-details { text-align: right; }
        .invoice-details h1 { margin: 0; font-size: 2.2em; color: #333; }
        .invoice-details p { margin: 2px 0; color: #555; }
        .addresses {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
        }
        .address-block { width: 48%; }
        .address-block h3 { margin-top: 0; margin-bottom: 5px; font-size: 0.9em; color: #777; text-transform: uppercase; }
        .address-block p { margin: 0; line-height: 1.6; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 10px; flex-grow: 1; }
        .invoice-table th, .invoice-table td { padding: 12px; text-align: left; }
        .invoice-table thead { background-color: #f5f5f5; border-bottom: 2px solid #ddd; }
        .invoice-table th { font-weight: 500; color: #555; }
        .invoice-table tbody tr { border-bottom: 1px solid #eee; }
        .invoice-table tbody tr:last-child { border-bottom: none; }
        .align-right { text-align: right; }
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .totals table { width: 300px; }
        .totals td { padding: 8px; }
        .totals .label { color: #555; }
        .totals .grand-total td { font-size: 1.2em; font-weight: bold; border-top: 2px solid #333; }
        .footer {
            text-align: center;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice-box">
        <header class="header">
            <div>
                <img src="../<?php echo htmlspecialchars($logo['file_path'] ?? ''); ?>" alt="Logo" class="logo">
            </div>
            <div class="invoice-details">
                <h1>INVOICE</h1>
                <p><strong>No:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
                <p><strong>Tanggal:</strong> <?php echo date("d M Y", strtotime($order['order_date'])); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
            </div>
        </header>

        <section class="addresses">
            <div class="address-block">
                <h3>DARI:</h3>
                <p>
                    <strong><?php echo htmlspecialchars($sender_info['location_title'] ?? 'Awor Coffee'); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($sender_info['address'] ?? 'Alamat Pengirim')); ?><br>
                    <?php echo htmlspecialchars($sender_info['phone'] ?? 'Telepon Pengirim'); ?>
                </p>
            </div>
            <div class="address-block">
                <h3>DITAGIHKAN KEPADA:</h3>
                <p>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?><br>
                    <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                    <?php echo htmlspecialchars($order['customer_email']); ?>
                </p>
            </div>
        </section>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Deskripsi Item</th>
                    <th class="align-right">Qty</th>
                    <th class="align-right">Harga Satuan</th>
                    <th class="align-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach ($items as $item):
                    $item_subtotal = $item['price'] * $item['quantity'];
                    $subtotal += $item_subtotal;
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <br><small><?php echo htmlspecialchars($item['variant_name']); ?></small>
                    </td>
                    <td class="align-right"><?php echo $item['quantity']; ?></td>
                    <td class="align-right">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td class="align-right">Rp <?php echo number_format($item_subtotal, 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <section class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="align-right">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td class="label">Ongkos Kirim (<?php echo strtoupper(htmlspecialchars($order['shipping_courier'])); ?>)</td>
                    <td class="align-right">Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td class="label">Kode Unik</td>
                    <td class="align-right">Rp <?php echo number_format($order['unique_code'], 0, ',', '.'); ?></td>
                </tr>
                <tr class="grand-total">
                    <td>Total Tagihan</td>
                    <td class="align-right">Rp <?php echo number_format($order['total_amount'] + $order['unique_code'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        </section>

        <footer class="footer">
            Terima kasih atas pesanan Anda!
        </footer>
    </div>
</body>
</html>