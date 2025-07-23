<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
include '../includes/db.php';

$message = '';
$category_id = $_GET['id'] ?? 0;

if ($category_id == 0) {
    header('Location: dashboard.php#manajemen_data');
    exit();
}

// Fungsi helper untuk upload file
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

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = $_POST['category_name'];
    $new_image_path = handle_file_upload('category_image', 'categories');

    if ($new_image_path) {
        // Jika ada gambar baru, update nama dan gambar
        $stmt = $conn->prepare("UPDATE product_categories SET name = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("ssi", $category_name, $new_image_path, $category_id);
    } else {
        // Jika tidak ada gambar baru, hanya update nama
        $stmt = $conn->prepare("UPDATE product_categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $category_name, $category_id);
    }

    if ($stmt->execute()) {
        header('Location: dashboard.php?message=Kategori%20berhasil%20diperbarui.#manajemen_data');
        exit();
    } else {
        $message = "Error: Gagal memperbarui kategori.";
    }
}

// Ambil data kategori yang akan diedit
$stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    header('Location: dashboard.php#manajemen_data');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
    <link rel="stylesheet" href="dashboard-style.css?v=1.0">
</head>
<body style="background-color: #f7fafc;">
    <div class="main-content" style="margin-left: 0; padding: 40px;">
        <div class="main-header"><h1>Edit Kategori: <?php echo htmlspecialchars($category['name']); ?></h1></div>
        
        <?php if ($message): ?>
            <div class="alert-info" style="background-color: #f8d7da;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px; margin: auto;">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category_name">Nama Kategori</label>
                    <input type="text" id="category_name" name="category_name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Gambar Saat Ini</label><br>
                    <?php if (!empty($category['image_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($category['image_path']); ?>" alt="Gambar Kategori" style="max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
                    <?php else: ?>
                        <p>Belum ada gambar untuk kategori ini.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category_image">Upload Gambar Baru (Opsional)</label>
                    <input type="file" id="category_image" name="category_image" class="form-control" accept="image/*">
                    <small>Kosongkan jika tidak ingin mengubah gambar.</small>
                </div>

                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="dashboard.php#manajemen_data" style="margin-left: 15px;">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>