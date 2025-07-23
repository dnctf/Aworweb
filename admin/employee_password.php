<?php
include '../includes/db.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'error';
$is_token_valid = false;

if (empty($token)) {
    $message = "Token tidak valid atau tidak ditemukan.";
} else {
    // Cari karyawan berdasarkan token dan pastikan token belum kedaluwarsa
    $stmt = $conn->prepare("SELECT id FROM employees WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        $employee_id = $employee['id'];
        $is_token_valid = true;
    } else {
        $message = "Token reset password tidak valid atau sudah kedaluwarsa.";
    }
}

// Proses form jika token valid dan password baru disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_token_valid) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password === $password_confirm) {
        if (strlen($password) >= 6) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Update password dan hapus token dari database
            $stmt_update = $conn->prepare("UPDATE employees SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_password, $employee_id);
            
            if ($stmt_update->execute()) {
                $message = "Password Anda telah berhasil direset. Silakan login dengan password baru Anda.";
                $message_type = 'success';
                $is_token_valid = false; // Sembunyikan form setelah berhasil
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Karyawan - Awor Coffee</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--main-bg); }
        .reset-container { background: var(--card-bg); padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password Karyawan</h2>
        
        <?php if ($message): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($is_token_valid): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php else: ?>
            <a href="login.php">Kembali ke Halaman Login</a>
        <?php endif; ?>
    </div>
</body>
</html>