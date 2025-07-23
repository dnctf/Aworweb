<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/header.php';

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}
$order_id = (int)$_GET['order_id'];

// PERBAIKAN: Mengambil kolom 'unique_code' dari database
$sql = "
    SELECT 
        o.id, o.total_amount, o.unique_code, o.order_date,
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

// PERBAIKAN: Menjumlahkan total_amount dengan unique_code
$total_due = $order['total_amount'] + $order['unique_code'];
?>
<style>
.confirmation-page { text-align: center; max-width: 600px; margin: 60px auto; padding: 40px; border: 1px solid #eee; border-radius: 10px; background-color: #f9f9f9; }
.payment-details { margin-top: 30px; text-align: left; background-color: #fff; padding: 20px; border-radius: 8px;}
.btn-confirm-payment {
    display: inline-block; background-color: #28a745; color: white; padding: 12px 25px;
    border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 30px; transition: background-color 0.3s;
}
.btn-confirm-payment:hover { background-color: #218838; }
</style>
<main>
    <div class="container confirmation-page">
        <h1>Terima Kasih!</h1>
        <p>Pesanan Anda dengan nomor <strong>#<?php echo $order['id']; ?></strong> telah berhasil dibuat.</p>
        <div class="payment-details">
            <h4>Instruksi Pembayaran</h4>
            <p>Subtotal Pesanan: <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></p>
            <p>Kode Unik: <strong><?php echo $order['unique_code']; ?></strong></p>
            <hr>
            <h3 style="color:red;">Total Transfer: <strong>Rp <?php echo number_format($total_due, 0, ',', '.'); ?></strong></h3>
            <hr>
            <p><strong><?php echo htmlspecialchars($order['payment_name']); ?></strong></p>
            <p><?php echo nl2br(htmlspecialchars($order['payment_instructions'])); ?></p>
        </div>
        <a href="payment_upload.php?order_id=<?php echo $order['id']; ?>" class="btn-confirm-payment">Konfirmasi Pembayaran</a>
        <br>
        <a href="shop.php" style="display:inline-block; margin-top:15px;">Kembali Belanja</a>
    </div>
</main>
<?php include_once 'includes/footer.php'; ?>