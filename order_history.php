<?php
session_start();
include_once 'includes/db.php';

// Pastikan pengguna sudah login, jika tidak, arahkan ke halaman profil/login
if (!isset($_SESSION['user_id'])) {
    header('Location: user_profile.php');
    exit();
}

// Ambil semua data pesanan milik pengguna yang sedang login
$user_id = $_SESSION['user_id'];
$orders = [];
$stmt = $conn->prepare("SELECT id, order_date, order_status, (total_amount + unique_code) as total_due FROM orders WHERE customer_email = (SELECT email FROM users WHERE id = ?) ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

include_once 'includes/header.php';
?>

<style>
    .history-page { max-width: 900px; margin: 40px auto; }
    .history-container { background: #fff; padding: 30px; border: 1px solid #eee; border-radius: 10px; }
    .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .history-table th, .history-table td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; }
    .history-table th { background-color: #f9f9f9; font-weight: 600; }
    .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; color: white; text-transform: capitalize; }
    .status-pending { background-color: #ffc107; } .status-paid { background-color: #28a745; }
    .status-shipped { background-color: #17a2b8; } .status-completed { background-color: #6c757d; }
    .status-cancelled { background-color: #dc3545; }
    .btn-view-details { background-color: #333; color: #fff; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9em; }
</style>

<main>
    <div class="container history-page">
        <div class="history-container">
            <h1>Riwayat Pesanan Saya</h1>
            <?php if (empty($orders)): ?>
                <p>Anda belum memiliki riwayat pesanan. <a href="shop.php">Mulai belanja sekarang!</a></p>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Total Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>Rp <?php echo number_format($order['total_due'], 0, ',', '.'); ?></td>
                                <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                                <td><a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-view-details">Lihat Detail</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>