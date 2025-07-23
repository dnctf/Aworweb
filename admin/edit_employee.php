<?php
session_start();
// Pastikan hanya superadmin yang bisa mengakses halaman ini
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}
include '../includes/db.php';

$message = '';
$employee_id = $_GET['id'] ?? 0;

// Jika ID tidak valid, kembalikan ke dashboard
if ($employee_id == 0) {
    header('Location: dashboard.php#manajemen_admin');
    exit();
}

// Proses form saat tombol "Simpan Perubahan" diklik
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil semua data dari form
    $employee_name = $_POST['employee_name'];
    $employee_position = $_POST['employee_position'];
    $employee_email = $_POST['employee_email'];
    $employee_phone = $_POST['employee_phone'];
    $employee_number = $_POST['employee_number'];
    
    // 2. Ambil hak akses dari checkbox, pastikan itu adalah array
    $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];
    // Gabungkan array menjadi string yang dipisahkan koma untuk disimpan di DB
    $roles_str = implode(',', $roles);

    // 3. Siapkan dan jalankan query UPDATE ke database
    $stmt = $conn->prepare("UPDATE employees SET name = ?, position = ?, email = ?, phone = ?, employee_number = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $employee_name, $employee_position, $employee_email, $employee_phone, $employee_number, $roles_str, $employee_id);

    if ($stmt->execute()) {
        // Jika berhasil, kembali ke dashboard dengan pesan sukses
        header('Location: dashboard.php?message=Data%20karyawan%20berhasil%20diperbarui.#manajemen_admin');
        exit();
    } else {
        // Jika gagal, tampilkan pesan error
        $message = "Error: Gagal memperbarui data karyawan. " . $stmt->error;
    }
}

// Ambil data karyawan yang akan diedit dari database untuk ditampilkan di form
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Jika data karyawan tidak ditemukan, kembali ke dashboard
if (!$employee) {
    header('Location: dashboard.php#manajemen_admin');
    exit();
}

// Ubah string 'role' dari database menjadi array agar bisa dicocokkan dengan checkbox
$employee_roles = !empty($employee['role']) ? explode(',', $employee['role']) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Karyawan - <?php echo htmlspecialchars($employee['name']); ?></title>
    <link rel="stylesheet" href="dashboard-style.css?v=1.0">
</head>
<body style="background-color: #f7fafc;">
    <div class="main-content" style="margin-left: 0; padding: 40px;">
        <div class="main-header"><h1>Edit Karyawan: <?php echo htmlspecialchars($employee['name']); ?></h1></div>
        
        <?php if ($message): ?>
            <div class="alert-info" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px; margin: auto;">
            <form method="POST" action="edit_employee.php?id=<?php echo $employee_id; ?>">
                <div class="form-group">
                    <label for="employee_name">Nama Lengkap</label>
                    <input type="text" id="employee_name" name="employee_name" class="form-control" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                </div>
                 <div class="form-group">
                    <label for="employee_number">Nomor Karyawan</label>
                    <input type="text" id="employee_number" name="employee_number" class="form-control" value="<?php echo htmlspecialchars($employee['employee_number']); ?>">
                </div>
                <div class="form-group">
                    <label for="employee_position">Jabatan</label>
                    <input type="text" id="employee_position" name="employee_position" class="form-control" value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="employee_email">Email (untuk login)</label>
                    <input type="email" id="employee_email" name="employee_email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>">
                </div>
                 <div class="form-group">
                    <label for="employee_phone">Nomor HP</label>
                    <input type="tel" id="employee_phone" name="employee_phone" class="form-control" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                </div>
                
                <hr style="margin: 25px 0;">

                <div class="form-group">
                    <label style="margin-bottom: 15px;">Hak Akses Dashboard</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <input type="checkbox" id="role_superadmin" name="roles[]" value="superadmin" <?php echo in_array('superadmin', $employee_roles) ? 'checked' : ''; ?>>
                            <label for="role_superadmin" style="display: inline; font-weight: normal;">Super Admin (Akses Penuh)</label>
                        </div>
                        <div>
                            <input type="checkbox" id="role_marketing" name="roles[]" value="marketing" <?php echo in_array('marketing', $employee_roles) ? 'checked' : ''; ?>>
                            <label for="role_marketing" style="display: inline; font-weight: normal;">Marketing (Tampilan & Logo)</label>
                        </div>
                        <div>
                            <input type="checkbox" id="role_sales" name="roles[]" value="sales_finance" <?php echo in_array('sales_finance', $employee_roles) ? 'checked' : ''; ?>>
                            <label for="role_sales" style="display: inline; font-weight: normal;">Sales & Finance (Keuangan & Produk)</label>
                        </div>
                        <div>
                            <input type="checkbox" id="role_hrd" name="roles[]" value="hrd" <?php echo in_array('hrd', $employee_roles) ? 'checked' : ''; ?>>
                            <label for="role_hrd" style="display: inline; font-weight: normal;">HRD (Aplikasi Hiring)</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="dashboard.php#manajemen_admin" style="margin-left: 15px;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>