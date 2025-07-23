<?php
include '../includes/db.php';

$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($conn)) {
    $name = $_POST['employee_name'];
    $position = $_POST['employee_position'];
    $join_date = $_POST['join_date'];
    $email = $_POST['employee_email'];

    if (!empty($name) && !empty($position) && !empty($join_date)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, position, join_date, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $position, $join_date, $email);
        
        if ($stmt->execute()) {
            $message = "Registrasi berhasil! Data karyawan baru telah disimpan.";
        } else {
            $error_message = "Terjadi kesalahan saat menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Semua kolom yang ditandai bintang (*) wajib diisi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Karyawan - Awor Coffee</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .register-container { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        h2 { margin-top: 0; margin-bottom: 25px; color: #333; font-weight: 500; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #333; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.2s; }
        .btn:hover { background: #555; }
        .message { padding: 15px; margin-top: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-link { margin-top: 20px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registrasi Karyawan</h2>

        <?php if ($message): ?>
            <p class="message success"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" action="employee_register.php">
            <div class="form-group">
                <label for="employee_name">Nama Lengkap*</label>
                <input type="text" name="employee_name" id="employee_name" required>
            </div>
            <div class="form-group">
                <label for="employee_email">Email</label>
                <input type="email" name="employee_email" id="employee_email">
            </div>
             <div class="form-group">
                <label for="employee_position">Jabatan*</label>
                <select id="employee_position" name="employee_position" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Sales & Supply">Sales & Supply</option>
                    <option value="Warehouse Manager">Warehouse Manager</option>
                    <option value="HRD">HRD</option>
                    <option value="CEO">CEO</option>
                    <option value="Finance">Finance</option>
                </select>
            </div>
             <div class="form-group">
                <label for="join_date">Tanggal Bergabung*</label>
                <input type="date" name="join_date" id="join_date" required>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>
         <p class="login-link">Sudah punya akun admin? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>