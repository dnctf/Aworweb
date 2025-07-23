<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/header.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'error';
$is_token_valid = false;

if (empty($token)) {
    $message = "Token tidak valid atau tidak ditemukan.";
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $is_token_valid = true;
    } else {
        $message = "Token reset password tidak valid atau sudah kedaluwarsa.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_token_valid) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password === $password_confirm) {
         if (strlen($password) >= 6) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt_update->execute()) {
                $message = "Password Anda telah berhasil direset. Silakan login dengan password baru Anda.";
                $message_type = 'success';
                $is_token_valid = false;
            } else {
                $message = "Terjadi kesalahan saat mereset password.";
            }
        } else {
             $message = "Password minimal harus 6 karakter.";
        }
    } else {
        $message = "Konfirmasi password tidak cocok.";
    }
}
?>
<style>
.auth-container { max-width: 450px; margin: 60px auto; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #eee; text-align: center; }
.message { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
</style>
<main>
    <div class="container">
         <div class="auth-container">
            <h2>Atur Ulang Password</h2>
            
            <?php if ($message): ?>
                <p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <?php if ($is_token_valid): ?>
                <form method="POST">
                    <div class="form-group">
                        <input type="password" name="password" class="form-control" placeholder="Password Baru" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password_confirm" class="form-control" placeholder="Konfirmasi Password Baru" required>
                    </div>
                    <button type="submit" class="btn-checkout">Reset Password</button>
                </form>
            <?php else: ?>
                <a href="user_profile.php">Kembali ke Halaman Login</a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include_once 'includes/footer.php'; ?>