<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/header.php';

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // Token valid 1 jam

        $stmt_update = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $token, $expires, $user_id);
        $stmt_update->execute();
        
        // SIMULASI PENGIRIMAN EMAIL
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $reset_link = $base_url . '/reset_password.php?token=' . $token;

        $message = "<b>(SIMULASI)</b> Link reset password telah dibuat. Dalam aplikasi nyata, link ini akan dikirim ke email Anda.<br><br>Silakan klik link berikut untuk melanjutkan: <a href='$reset_link'>$reset_link</a>";
        $message_type = 'success';
    } else {
        // Pesan umum untuk keamanan, agar tidak bisa menebak email yang terdaftar
        $message = "Jika email yang Anda masukkan terdaftar, kami telah mengirimkan instruksi reset password.";
    }
}
?>
<style>
.auth-container { max-width: 450px; margin: 60px auto; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #eee; text-align: center; }
.message { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; text-align: left; }
.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
.info { background-color: #e2e3e5; color: #383d41; border-color: #d6d8db; }
</style>

<main>
    <div class="container">
        <div class="auth-container">
            <h2>Lupa Password Anda?</h2>
            <p>Masukkan alamat email Anda di bawah ini untuk menerima instruksi reset password.</p>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required>
                </div>
                <button type="submit" class="btn-checkout">Kirim Instruksi</button>
            </form>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>