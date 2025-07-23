<?php
session_start();
include '../includes/db.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // Bisa username atau email
    $password = $_POST['password'];

    if (isset($conn)) {
        // Cek sebagai Super Admin terlebih dahulu
        $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_role'] = 'superadmin'; // Superadmin role
                header('Location: dashboard.php');
                exit();
            }
        }
        
        // Jika bukan superadmin, cek sebagai Karyawan
        $stmt = $conn->prepare("SELECT id, name, password, role FROM employees WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();
            if ($employee['password'] && password_verify($password, $employee['password'])) {
                 $_SESSION['admin_logged_in'] = true;
                 $_SESSION['admin_username'] = $employee['name'];
                 $_SESSION['admin_role'] = $employee['role']; // Ambil role dari tabel employees
                 header('Location: dashboard.php');
                 exit();
            }
        }
        
        $error_message = 'Username atau password salah!';
        $stmt->close();
    } else {
        $error_message = 'Gagal terhubung ke database.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Awor Coffee</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 320px; text-align: center; }
        h2 { margin-top: 0; margin-bottom: 25px; color: #333; font-weight: 500; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #333; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.2s; }
        .btn:hover { background: #555; }
        .error { color: #e74c3c; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Awor Staff Login</h2>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Email atau Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <?php if ($error_message): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>