<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/header.php';

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// Ambil detail pesanan dan metode pembayaran
$sql = "
    SELECT 
        o.id, o.total_amount, o.order_date,
        pm.name as payment_name, pm.description as payment_instructions
    FROM orders o
    JOIN payment_methods pm ON o.payment_method_id = pm.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
     header('Location: index.php');
    exit();
}
?>

<style>
.confirmation-page { text-align: center; max-width: 600px; margin: 60px auto; padding: 40px; border: 1px solid #eee; border-radius: 10px; background-color: #f9f9f9; }
.confirmation-page h1 { color: #2ecc71; }
.payment-details { margin-top: 30px; text-align: left; background-color: #fff; padding: 20px; border-radius: 8px;}
</style>

<main>
    <div class="container confirmation-page">
        <h1>Terima Kasih!</h1>
        <p>Pesanan Anda dengan nomor <strong>#<?php echo $order['id']; ?></strong> telah berhasil kami terima.</p>
        
        <div class="payment-details">
            <h4>Instruksi Pembayaran</h4>
            <p>
                Total Tagihan: <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong><br>
                Metode: <strong><?php echo htmlspecialchars($order['payment_name']); ?></strong>
            </p>
            <hr>
            <p><?php echo nl2br(htmlspecialchars($order['payment_instructions'])); ?></p>
            <small>Mohon segera selesaikan pembayaran agar pesanan Anda dapat kami proses.</small>
        </div>
        
        <a href="shop.php" style="display:inline-block; margin-top:30px;">Kembali Belanja</a>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>