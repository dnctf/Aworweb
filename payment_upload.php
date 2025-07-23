 <?php
 session_start();
 include_once 'includes/db.php';
 include_once 'includes/header.php';

 if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
     header('Location: index.php');
     exit();
 }
 $order_id = (int)$_GET['order_id'];
 $message = '';

 // Ambil detail pesanan
 $stmt_order = $conn->prepare("SELECT id, total_amount FROM orders WHERE id = ?");
 $stmt_order->bind_param("i", $order_id);
 $stmt_order->execute();
 $order_result = $stmt_order->get_result();
 $order = $order_result->fetch_assoc();

 if (!$order) {
     header('Location: index.php');
     exit();
 }

 // Proses upload bukti pembayaran
 if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_payment'])) {
     $upload_dir = 'uploads/payment_proofs/';
     if (!is_dir($upload_dir)) {
         mkdir($upload_dir, 0755, true);
     }
     $file_name = uniqid() . '-' . basename($_FILES['payment_proof']['name']);
     $target_path = $upload_dir . $file_name;
     $db_path = $target_path; // Simpan path lengkap untuk keperluan admin

     if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
         // Update status pesanan dan simpan path bukti pembayaran (Anda mungkin perlu kolom baru di tabel 'orders')
         $stmt_update = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_proof_path = ? WHERE id = ?");
         $stmt_update->bind_param("si", $db_path, $order_id);
         if ($stmt_update->execute()) {
             $message = 'Bukti pembayaran berhasil diupload. Kami akan segera memproses pesanan Anda.';
         } else {
             $message = 'Terjadi kesalahan saat menyimpan informasi bukti pembayaran.';
         }
     } else {
         $message = 'Gagal mengupload file bukti pembayaran.';
     }
 }
 ?>
 <style>
     .payment-upload-page { max-width: 600px; margin: 40px auto; padding: 30px; border: 1px solid #eee; border-radius: 8px; background-color: #f9f9f9; text-align: center; }
     .payment-upload-page h1 { margin-bottom: 20px; }
     .payment-info { margin-bottom: 20px; text-align: left; background-color: white; padding: 15px; border-radius: 5px; }
     .payment-form .form-group { margin-bottom: 15px; text-align: left; }
     .payment-form label { display: block; font-weight: 600; margin-bottom: 5px; }
     .payment-form input.form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
     .btn-upload { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
     .message { margin-top: 20px; padding: 15px; border-radius: 5px; }
     .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
     .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
 </style>

 <main>
     <div class="container payment-upload-page">
         <h1>Upload Bukti Pembayaran</h1>
         <?php if ($message): ?>
             <div class="message <?php echo (strpos($message, 'berhasil') !== false) ? 'success' : 'error'; ?>">
                 <?php echo htmlspecialchars($message); ?>
             </div>
         <?php endif; ?>
         <div class="payment-info">
             <p>Nomor Pesanan: <strong>#<?php echo $order['id']; ?></strong></p>
             <p>Total Pembayaran: <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></p>
             <p>Silakan upload bukti pembayaran Anda di bawah ini.</p>
         </div>
         <form method="post" enctype="multipart/form-data" class="payment-form">
             <div class="form-group">
                 <label for="payment_proof">File Bukti Pembayaran</label>
                 <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*, application/pdf" required>
                 <small class="form-text text-muted">Format yang didukung: JPG, PNG, PDF.</small>
             </div>
             <button type="submit" class="btn-upload" name="upload_payment">Upload Bukti Pembayaran</button>
         </form>
         <p style="margin-top: 20px;"><a href="index.php">Kembali ke Beranda</a></p>
     </div>
 </main>

 <?php include_once 'includes/footer.php'; ?>
