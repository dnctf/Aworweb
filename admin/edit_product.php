<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
include '../includes/db.php';

$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: dashboard.php#manajemen_data');
    exit();
}

// Proses form update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_product') {
    $conn->begin_transaction();
    try {
        // ... (Logika update nama, deskripsi, kategori, dan foto utama/galeri tetap sama) ...
        
        // LOGIKA BARU YANG LEBIH AMAN UNTUK VARIAN
        // 1. Tandai semua varian lama sebagai tidak aktif
        $conn->query("UPDATE product_variants SET is_active = 0 WHERE product_id = $product_id");

        // 2. Loop melalui data yang dikirim dan update/insert
        if (isset($_POST['variants']) && is_array($_POST['variants'])) {
            $stmt_update = $conn->prepare("UPDATE product_variants SET variant_group = ?, variant_option = ?, price = ?, stock = ?, is_active = 1 WHERE id = ?");
            $stmt_insert = $conn->prepare("INSERT INTO product_variants (product_id, variant_group, variant_option, price, stock, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            
            foreach ($_POST['variants'] as $groupData) {
                $groupName = $groupData['group_name'];
                if (isset($groupData['options']) && is_array($groupData['options'])) {
                    foreach ($groupData['options'] as $option) {
                        if (!empty($option['name']) && isset($option['price'])) {
                            $variant_id = (int)($option['id'] ?? 0);
                            $stock = !empty($option['stock']) ? (int)$option['stock'] : 0;
                            $price = (float)$option['price'];

                            if ($variant_id > 0) { // Jika ini varian yang sudah ada, UPDATE
                                $stmt_update->bind_param("ssdisi", $groupName, $option['name'], $price, $stock, $variant_id);
                                $stmt_update->execute();
                            } else { // Jika ini varian baru, INSERT
                                $stmt_insert->bind_param("issdi", $product_id, $groupName, $option['name'], $price, $stock);
                                $stmt_insert->execute();
                            }
                        }
                    }
                }
            }
        }
        
        $conn->commit();
        header('Location: dashboard.php?message=Produk%20berhasil%20diperbarui.#manajemen_data');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        // Handle error, tampilkan pesan
        $message = "Error: Gagal memperbarui data. " . $e->getMessage();
    }
}

// Ambil data produk dan varian yang AKTIF untuk ditampilkan di form
$product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
// ... (Kode pengambilan data lainnya tetap sama) ...
$variants_raw = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id AND is_active = 1 ORDER BY variant_group, id ASC")->fetch_all(MYSQLI_ASSOC);
$variant_groups = [];
foreach ($variants_raw as $variant) {
    $variant_groups[$variant['variant_group']][] = $variant;
}
?>
<!DOCTYPE html>
<div class="variant-option-row">
    <input type="hidden" name="variants[<?php echo $group_key; ?>][options][<?php echo $j; ?>][id]" value="<?php echo $option['id']; ?>">
    <input type="text" class="form-control" name="variants[<?php echo $group_key; ?>][options][<?php echo $j; ?>][name]" value="<?php echo htmlspecialchars($option['variant_option']); ?>">
    </div>