<?php
session_start();
include_once 'includes/db.php';

// Pastikan pengguna login dan ada order_id
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: user_profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

// Ambil data order, pastikan order ini milik user yang sedang login
$stmt = $conn->prepare("SELECT o.*, pm.name as payment_method_name FROM orders o LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id WHERE o.id = ? AND o.customer_email = (SELECT email FROM users WHERE id = ?)");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    // Jika order tidak ditemukan atau bukan milik user ini, kembalikan ke riwayat
    header('Location: order_history.php');
    exit();
}

// Ambil item-item dari order tersebut
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once 'includes/header.php';
?>

<style>
    .order-detail-page { max-width: 900px; margin: 40px auto; }
    .detail-box { background: #fff; padding: 30px; border: 1px solid #eee; border-radius: 10px; }
    .detail-header { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
    .detail-header h1 { margin: 0; } .detail-header p { margin: 5px 0 0 0; color: #666; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;}
    .detail-block h3 { font-size: 1.1em; color: #555; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 10px;}
    .detail-block p { margin: 0; line-height: 1.7; }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th, .items-table td { padding: 12px 0; border-bottom: 1px solid #eee; text-align: left; }
    .items-table th { color: #888; font-weight: 500; }
    .item-info { display: flex; align-items: center; gap: 15px; }
    .item-info img { width: 50px; height: 50px; border-radius: 5px; object-fit: cover; }
    .totals-summary { float: right; width: 40%; margin-top: 20px; }
    .summary-line { display: flex; justify-content: space-between; padding: 8px 0; }
    .grand-total { border-top: 2px solid #333; font-weight: bold; font-size: 1.2em; }
    .clearfix::after { content: ""; clear: both; display: table; }
</style>

<main>
    <div class="container order-detail-page">
        <div class="detail-box">
            <div class="detail-header">
                <h1>Detail Pesanan #<?php echo $order['id']; ?></h1>
                <p>Tanggal: <?php echo date('d F Y', strtotime($order['order_date'])); ?> | Status: <span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></p>
            </div>

            <div class="detail-grid">
                <div class="detail-block">
                    <h3>Alamat Pengiriman</h3>
                    <p>
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                        <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                        <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                    </p>
                </div>
                <div class="detail-block">
                    <h3>Info Pembayaran & Pengiriman</h3>
                    <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method_name']); ?></p>
                    <p><strong>Kurir Pengiriman:</strong> <?php echo htmlspecialchars($order['shipping_courier']); ?></p>
                </div>
            </div>

            <div class="detail-block">
                <h3>Rincian Item</h3>
                <table class="items-table">
                    <thead>
                        <tr><th>Produk</th><th>Kuantitas</th><th style="text-align:right;">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php $subtotal = 0; foreach ($items as $item): $item_total = $item['price'] * $item['quantity']; $subtotal += $item_total; ?>
                        <tr>
                            <td>
                                <div class="item-info">
                                    <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'path/to/placeholder.jpg'); ?>" alt="">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($item['variant_name']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="text-align:right;">Rp <?php echo number_format($item_total, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="totals-summary">
                <div class="summary-line"><span>Subtotal:</span><span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></div>
                <div class="summary-line"><span>Ongkos Kirim:</span><span>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span></div>
                <div class="summary-line"><span>Kode Unik:</span><span>Rp <?php echo number_format($order['unique_code'], 0, ',', '.'); ?></span></div>
                <div class="summary-line grand-total"><span>TOTAL:</span><span>Rp <?php echo number_format($order['total_amount'] + $order['unique_code'], 0, ',', '.'); ?></span></div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>