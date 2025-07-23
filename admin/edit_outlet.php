<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
include '../includes/db.php';

$message = '';
$outlet_id = $_GET['id'] ?? 0;

// Jika ID tidak valid, kembalikan ke dashboard
if ($outlet_id == 0) {
    header('Location: dashboard.php#manajemen_outlet');
    exit();
}

// Fungsi helper untuk menangani upload file (diambil dari dashboard.php)
function handle_file_upload($file_input_name, $upload_subdir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $upload_dir = '../uploads/' . $upload_subdir . '/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file_name = uniqid() . '-' . basename($_FILES[$file_input_name]['name']);
        $target_path = $upload_dir . $file_name;
        $db_path = 'uploads/' . $upload_subdir . '/' . $file_name;
        if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $target_path)) return $db_path;
    }
    return null;
}

// Logika untuk UPDATE data saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['outlet_name'];
    $address = $_POST['outlet_address'];
    $city = $_POST['outlet_city'];
    $province = $_POST['outlet_province'];
    $maps_url = $_POST['outlet_maps_url'];
    
    // Cek apakah ada file foto baru yang di-upload
    $new_image_path = handle_file_upload('outlet_image', 'outlets');
    
    if ($new_image_path) {
        // Jika ada foto baru, update semua termasuk image_path
        $stmt = $conn->prepare("UPDATE outlets SET name = ?, address = ?, city = ?, province = ?, maps_url = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $address, $city, $province, $maps_url, $new_image_path, $outlet_id);
    } else {
        // Jika tidak ada foto baru, update data lain kecuali image_path
        $stmt = $conn->prepare("UPDATE outlets SET name = ?, address = ?, city = ?, province = ?, maps_url = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $address, $city, $province, $maps_url, $outlet_id);
    }
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?message=Outlet%20berhasil%20diperbarui.#manajemen_outlet');
        exit();
    } else {
        $message = "Error: Gagal memperbarui data. " . $stmt->error;
    }
}

// Mengambil data outlet yang akan diedit dari database
$stmt = $conn->prepare("SELECT * FROM outlets WHERE id = ?");
$stmt->bind_param("i", $outlet_id);
$stmt->execute();
$result = $stmt->get_result();
$outlet = $result->fetch_assoc();

if (!$outlet) {
    header('Location: dashboard.php#manajemen_outlet');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Outlet - Awor Coffee</title>
    <link rel="stylesheet" href="dashboard-style.css?v=12.1">
</head>
<body style="background-color: #f7fafc;">
    <div class="main-content" style="margin-left: 0; padding: 40px;">
        <div class="main-header"><h1>Edit Outlet: <?php echo htmlspecialchars($outlet['name']); ?></h1></div>
        
        <?php if ($message): ?>
            <div class="alert-info" style="background-color: #f8d7da; color: #721c24;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="outlet_name">Nama Outlet</label>
                    <input type="text" id="outlet_name" name="outlet_name" value="<?php echo htmlspecialchars($outlet['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="outlet_address">Alamat</label>
                    <input type="text" id="outlet_address" name="outlet_address" value="<?php echo htmlspecialchars($outlet['address']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="outlet_city">Kota</label>
                    <input type="text" id="outlet_city" name="outlet_city" value="<?php echo htmlspecialchars($outlet['city']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="outlet_province">Provinsi</label>
                    <input type="text" id="outlet_province" name="outlet_province" value="<?php echo htmlspecialchars($outlet['province']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="outlet_maps_url">URL Google Maps</label>
                    <input type="url" id="outlet_maps_url" name="outlet_maps_url" value="<?php echo htmlspecialchars($outlet['maps_url']); ?>">
                </div>
                
                <hr style="margin: 25px 0;">

                <div class="form-group">
                    <label>Foto Outlet Saat Ini</label>
                    <img src="../<?php echo htmlspecialchars($outlet['image_path']); ?>" alt="Foto Outlet" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">
                </div>
                 <div class="form-group">
                    <label for="outlet_image">Ganti Foto Outlet (Opsional)</label>
                    <input type="file" id="outlet_image" name="outlet_image" accept="image/*">
                    <small>Kosongkan jika tidak ingin mengganti foto.</small>
                </div>
                
                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="dashboard.php#manajemen_outlet" style="margin-left: 15px; text-decoration: none;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>