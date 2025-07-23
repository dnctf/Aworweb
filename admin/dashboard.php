<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
include '../includes/db.php';

// Pastikan koneksi DB berhasil
if (!isset($conn)) {
    die("Koneksi ke database gagal. Mohon periksa file 'includes/db.php'.");
}

$admin_role = $_SESSION['admin_role'] ?? 'guest';

// =================================================================
// BAGIAN 1: LOGIKA PROSES FORM (POST REQUEST) - STRUKTUR DIPERBAIKI
// =================================================================
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $tab_name = $_POST['tab_name'] ?? 'dashboard';
    $id = $_POST['id'] ?? 0;

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

    switch ($action) {
        // ... (SEMUA BLOK 'case' UNTUK SETIAP AKSI SEPERTI add_product, update_logo, DLL, LENGKAP DI SINI) ...
        case 'update_site_appearance':
            if (isset($_POST['content'])) {
                foreach ($_POST['content'] as $section => $fields) {
                    $stmt = $conn->prepare("UPDATE content SET title = ?, description = ? WHERE section = ?");
                    $stmt->bind_param("sss", $fields['title'], $fields['description'], $section);
                    $stmt->execute();
                }
            }
            $message = "Pengaturan Tampilan Situs berhasil diperbarui.";
            break;
        case 'update_main_logo':
        case 'update_hero_logo':
            $logo_type = ($action == 'update_main_logo') ? 'main_logo' : 'hero_logo';
            $file_input = $logo_type . '_file';
            $image_path = handle_file_upload($file_input, 'assets');
            if ($image_path) {
                $stmt = $conn->prepare("UPDATE site_assets SET file_path = ? WHERE asset_name = ?");
                $stmt->bind_param("ss", $image_path, $logo_type);
                $message = $stmt->execute() ? "Logo berhasil diperbarui." : "Gagal update database.";
            } else { $message = "Gagal mengupload file logo."; }
            break;
        case 'add_slider_image':
            $image_path = handle_file_upload('slider_image', 'slider');
            if($image_path){
                $stmt = $conn->prepare("INSERT INTO images (category, file_path) VALUES ('hero_slider', ?)");
                $stmt->bind_param("s", $image_path);
                $message = $stmt->execute() ? "Gambar slider baru ditambahkan." : "Error: " . $stmt->error;
            } else { $message = "Gagal mengupload gambar."; }
            break;
        case 'delete_slider_image':
            $stmt = $conn->prepare("DELETE FROM images WHERE id = ?");
            $stmt->bind_param("i", $id);
            $message = $stmt->execute() ? "Gambar slider dihapus." : "Error: " . $stmt->error;
            break;
        case 'add_category':
            $stmt = $conn->prepare("INSERT INTO product_categories (name) VALUES (?)");
            $stmt->bind_param("s", $_POST['category_name']);
            $message = $stmt->execute() ? "Kategori baru ditambahkan." : "Error: " . $stmt->error;
            break;
        case 'delete_category':
            $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $message = $stmt->execute() ? "Kategori dihapus." : "Error: " . $stmt->error;
            break;
        case 'add_product':
            $conn->begin_transaction();
            try {
                $image_path = handle_file_upload('product_photo', 'products') ?? 'uploads/products/placeholder.jpg';
                $stmt_prod = $conn->prepare("INSERT INTO products (name, description, category_id, image_path) VALUES (?, ?, ?, ?)");
                $stmt_prod->bind_param("ssis", $_POST['product_name'], $_POST['product_description'], $_POST['product_category'], $image_path);
                $stmt_prod->execute();
                $product_id = $conn->insert_id;
                if (isset($_POST['variants']) && is_array($_POST['variants'])) {
                    $stmt_var = $conn->prepare("INSERT INTO product_variants (product_id, variant_group, variant_option, price, stock) VALUES (?, ?, ?, ?, ?)");
                    foreach ($_POST['variants'] as $groupData) {
                        $groupName = $groupData['group_name'];
                        if (isset($groupData['options']) && is_array($groupData['options'])) {
                            foreach ($groupData['options'] as $option) {
                                if (!empty($option['name']) && isset($option['price'])) {
                                    $stock = !empty($option['stock']) ? (int)$option['stock'] : 0;
                                    $price = (float)$option['price'];
                                    $stmt_var->bind_param("issdi", $product_id, $groupName, $option['name'], $price, $stock);
                                    $stmt_var->execute();
                                }
                            }
                        }
                    }
                }
                $conn->commit();
                $message = "Produk baru dengan varian berhasil ditambahkan.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error: Gagal menambahkan produk. " . $e->getMessage();
            }
            break;
        case 'delete_product':
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $message = $stmt->execute() ? "Produk dihapus." : "Error: " . $stmt->error;
            break;
        case 'add_outlet':
            $image_path = handle_file_upload('outlet_image', 'outlets') ?? 'uploads/outlets/placeholder.jpg';
            $stmt = $conn->prepare("INSERT INTO outlets (name, address, city, province, image_path, maps_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $_POST['outlet_name'], $_POST['outlet_address'], $_POST['outlet_city'], $_POST['outlet_province'], $image_path, $_POST['outlet_maps_url']);
            $message = $stmt->execute() ? "Outlet baru ditambahkan." : "Error: " . $stmt->error;
            break;
        case 'delete_outlet':
            $stmt = $conn->prepare("DELETE FROM outlets WHERE id = ?");
            $stmt->bind_param("i", $id);
            $message = $stmt->execute() ? "Outlet dihapus." : "Error: " . $stmt->error;
            break;
            case 'delete_employee':
            $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->bind_param("i", $id);
            $message = $stmt->execute() ? "Data karyawan berhasil dihapus." : "Error: " . $stmt->error;
            break;
            case 'request_employee_password_reset':
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 3600); // Token valid selama 1 jam
            
            $stmt = $conn->prepare("UPDATE employees SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires, $id);
            if ($stmt->execute()) {
                $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                $reset_link = $base_url . '/reset_employee_password.php?token=' . $token;
                $message = "Link reset password telah dibuat. Berikan link berikut kepada karyawan: <br><strong style='font-size:12px;'>$reset_link</strong>";
            } else {
                $message = "Gagal membuat link reset password.";
            }
            break;
        case 'update_order_status':
            $new_status = $_POST['order_status'];
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $message = "Status pesanan #{$id} berhasil diperbarui.";
            } else {
                $message = "Gagal memperbarui status pesanan.";
            }
            break;
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "#" . $tab_name);
    exit();
}

// =================================================================
// BAGIAN 2: PENGAMBILAN SEMUA DATA (SETELAH PROSES FORM SELESAI)
// =================================================================
$db_data = [];
$total_revenue_res = $conn->query("SELECT SUM(total_amount + unique_code) as total FROM orders WHERE order_status IN ('paid', 'shipped', 'completed')");
$db_data['total_revenue'] = $total_revenue_res->fetch_assoc()['total'] ?? 0;
$total_orders_res = $conn->query("SELECT COUNT(id) as total FROM orders");
$db_data['total_orders'] = $total_orders_res->fetch_assoc()['total'] ?? 0;
$products_sold_res = $conn->query("SELECT SUM(quantity) as total FROM order_items JOIN orders ON order_items.order_id = orders.id WHERE orders.order_status NOT IN ('cancelled')");
$db_data['products_sold'] = $products_sold_res->fetch_assoc()['total'] ?? 0;
$today_visitors_res = $conn->query("SELECT visit_count FROM visitors WHERE visit_date = CURDATE()");
$db_data['today_visitors'] = $today_visitors_res->fetch_assoc()['visit_count'] ?? 0;
$visitor_chart_data = ['labels' => [], 'data' => []];
$visitor_res = $conn->query("SELECT visit_date, visit_count FROM visitors WHERE visit_date >= CURDATE() - INTERVAL 6 DAY ORDER BY visit_date ASC");
if($visitor_res) while($row = $visitor_res->fetch_assoc()) { $visitor_chart_data['labels'][] = date('d M', strtotime($row['visit_date'])); $visitor_chart_data['data'][] = $row['visit_count']; }
$sales_orders = $conn->query("SELECT * FROM orders WHERE order_status = 'pending' ORDER BY order_date DESC")->fetch_all(MYSQLI_ASSOC);
$sales_invoices = $conn->query("SELECT * FROM orders WHERE order_status IN ('paid', 'shipped', 'completed') ORDER BY order_date DESC")->fetch_all(MYSQLI_ASSOC);
$sales_orders_count = count($sales_orders);
$all_employees = $conn->query("SELECT id, name, email, position, role FROM employees ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$job_applications = $conn->query("SELECT * FROM job_applications ORDER BY application_date DESC")->fetch_all(MYSQLI_ASSOC);
$db_data['content'] = []; $results = $conn->query("SELECT * FROM content"); if($results) while($row = $results->fetch_assoc()) $db_data['content'][$row['section']] = $row;
$db_data['assets'] = []; $results = $conn->query("SELECT * FROM site_assets"); if($results) while($row = $results->fetch_assoc()) $db_data['assets'][$row['asset_name']] = $row;
$db_data['categories'] = []; $results = $conn->query("SELECT * FROM product_categories ORDER BY name ASC"); if($results) while($row = $results->fetch_assoc()) $db_data['categories'][] = $row;
$db_data['products'] = []; $sql_products = "SELECT p.*, c.name as category_name, (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as min_price FROM products p LEFT JOIN product_categories c ON p.category_id = c.id ORDER BY p.id DESC"; $results = $conn->query($sql_products); if($results) while($row = $results->fetch_assoc()) $db_data['products'][] = $row;
$db_data['outlets'] = []; $results = $conn->query("SELECT * FROM outlets ORDER BY id DESC"); if($results) while($row = $results->fetch_assoc()) $db_data['outlets'][] = $row;
$db_data['slider_images'] = []; $results = $conn->query("SELECT * FROM images WHERE category = 'hero_slider' ORDER BY id DESC"); if($results) while($row = $results->fetch_assoc()) $db_data['slider_images'][] = $row;
$db_data['orders'] = []; $orders_res = $conn->query("SELECT o.*, pm.name as payment_method FROM orders o LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id ORDER BY o.order_date DESC"); if($orders_res) while($row = $orders_res->fetch_assoc()) $db_data['orders'][] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Awor Coffee</title>
    <link rel="stylesheet" href="dashboard-style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <?php if ($admin_role === 'superadmin'): ?>
                    <li><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="menu-heading">WEBSITE</li>
                    <li><a href="#tampilan_situs"><i class="fas fa-palette"></i> Tampilan Situs</a></li>
                    <li><a href="#pengaturan_logo"><i class="fas fa-gem"></i> Logo</a></li>
                    <li><a href="#pengaturan_gambar"><i class="fas fa-images"></i> Gambar Slider</a></li>
                    <li class="menu-heading">OPERASIONAL</li>
                    <li><a href="#manajemen_keuangan"><i class="fas fa-file-invoice-dollar"></i> Keuangan</a></li>
                    <li><a href="#manajemen_pesanan"><i class="fas fa-box-open"></i> Semua Pesanan</a></li>
                    <li><a href="#manajemen_data"><i class="fas fa-database"></i> Produk & Outlet</a></li>
                    <li class="menu-heading">ADMINISTRASI</li>
                    <li><a href="#manajemen_admin"><i class="fas fa-user-shield"></i> Karyawan & Akses</a></li>
                    <li><a href="#manajemen_hiring"><i class="fas fa-users"></i> Aplikasi Hiring</a></li>
                <?php elseif ($admin_role === 'marketing'): ?>
                    <li><a href="#tampilan_situs"><i class="fas fa-palette"></i> Tampilan Situs</a></li>
                    <li><a href="#pengaturan_logo"><i class="fas fa-gem"></i> Logo</a></li>
                <?php elseif ($admin_role === 'sales_finance'): ?>
                    <li><a href="#manajemen_keuangan"><i class="fas fa-file-invoice-dollar"></i> Manajemen Keuangan</a></li>
                    <li><a href="#manajemen_data"><i class="fas fa-database"></i> Produk & Kategori</a></li>
                <?php elseif ($admin_role === 'hrd'): ?>
                     <li><a href="#manajemen_hiring"><i class="fas fa-users"></i> Form Hiring</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <?php if(isset($_GET['message'])): ?>
                <div class="alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>

            <?php if ($admin_role === 'superadmin'): ?>
            <div id="dashboard" class="page">
                <div class="main-header"><h1>Dashboard</h1></div>
                <div class="stat-grid">
                    <div class="stat-card"><div class="icon"><i class="fas fa-dollar-sign"></i></div><div class="info"><h3>Total Pendapatan</h3><p>Rp <?php echo number_format($db_data['total_revenue'], 0, ',', '.'); ?></p></div></div>
                    <div class="stat-card"><div class="icon"><i class="fas fa-shopping-cart"></i></div><div class="info"><h3>Total Pesanan</h3><p><?php echo $db_data['total_orders']; ?></p></div></div>
                    <div class="stat-card"><div class="icon"><i class="fas fa-box"></i></div><div class="info"><h3>Produk Terjual</h3><p><?php echo $db_data['products_sold']; ?></p></div></div>
                    <div class="stat-card"><div class="icon"><i class="fas fa-users"></i></div><div class="info"><h3>Pengunjung Hari Ini</h3><p><?php echo $db_data['today_visitors']; ?></p></div></div>
                </div>
                 <div class="card full-width-card">
                    <div class="card-header"><h2>Statistik Pengunjung (7 Hari Terakhir)</h2></div>
                    <div class="chart-container"><canvas id="visitorChart"></canvas></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($admin_role, ['superadmin', 'marketing'])): ?>
            <div id="tampilan_situs" class="page">
                <div class="main-header"><h1>Pengaturan Tampilan Situs</h1></div>
                <form method="POST"><input type="hidden" name="action" value="update_site_appearance"><input type="hidden" name="tab_name" value="tampilan_situs">
                    <div class="card"><div class="card-header"><h2>Teks Halaman Depan</h2></div>
                        <div class="form-group"><label>Judul Hero</label><input type="text" name="content[hero][title]" class="form-control" value="<?php echo htmlspecialchars($db_data['content']['hero']['title'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Deskripsi Hero</label><input type="text" name="content[hero][description]" class="form-control" value="<?php echo htmlspecialchars($db_data['content']['hero']['description'] ?? ''); ?>"></div>
                    </div>
                    <button type="submit" class="btn">Simpan Perubahan</button>
                </form>
            </div>
            <div id="pengaturan_logo" class="page">
                <div class="main-header"><h1>Pengaturan Logo</h1></div>
                <div class="content-grid">
                    <div class="card"><div class="card-header"><h2>Logo Utama (Header)</h2></div><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="update_main_logo"><input type="hidden" name="tab_name" value="pengaturan_logo"><div class="form-group"><label>Logo Saat Ini:</label><img src="../<?php echo htmlspecialchars($db_data['assets']['main_logo']['file_path']); ?>" style="max-height: 40px; background: #eee; padding: 5px;"></div><div class="form-group"><label>Upload Baru</label><input type="file" name="main_logo_file" class="form-control" required></div><button type="submit" class="btn">Update Logo</button></form></div>
                    <div class="card"><div class="card-header"><h2>Logo Hero</h2></div><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="update_hero_logo"><input type="hidden" name="tab_name" value="pengaturan_logo"><div class="form-group"><label>Logo Saat Ini:</label><img src="../<?php echo htmlspecialchars($db_data['assets']['hero_logo']['file_path']); ?>" style="max-height: 80px; background: #333; padding: 10px;"></div><div class="form-group"><label>Upload Baru</label><input type="file" name="hero_logo_file" class="form-control" required></div><button type="submit" class="btn">Update Logo</button></form></div>
                </div>
            </div>
            <?php endif; ?>
            
             <?php if ($admin_role === 'superadmin'): ?>
            <div id="pengaturan_gambar" class="page">
                <div class="main-header"><h1>Manajemen Gambar Slider</h1></div>
                <div class="card"><div class="card-header"><h2>Tambah Gambar Baru</h2></div><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add_slider_image"><input type="hidden" name="tab_name" value="pengaturan_gambar"><div class="form-group"><label>Pilih File Gambar</label><input type="file" name="slider_image" accept="image/*" class="form-control" required></div><button type="submit" class="btn">Upload</button></form></div>
                <div class="card full-width-card" style="margin-top:30px;"><div class="card-header"><h2>Daftar Gambar Slider</h2></div><div class="image-list"><?php foreach($db_data['slider_images'] as $img): ?><div class="image-item"><img src="../<?php echo htmlspecialchars($img['file_path']); ?>"><form method="POST"><input type="hidden" name="action" value="delete_slider_image"><input type="hidden" name="tab_name" value="pengaturan_gambar"><input type="hidden" name="id" value="<?php echo $img['id']; ?>"><button type="submit" onclick="return confirm('Yakin?')">&times;</button></form></div><?php endforeach; ?></div></div>
            </div>
            <?php endif; ?>

            <?php if (in_array($admin_role, ['superadmin', 'sales_finance'])): ?>
            <div id="manajemen_keuangan" class="page">
                <div class="main-header"><h1>Manajemen Keuangan</h1></div>
                <div class="card full-width-card"><div class="card-header"><h2>Sales Order (Belum Dibayar) - <?php echo $sales_orders_count; ?> Pesanan</h2></div><table class="data-table"><thead><tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Tanggal Pesan</th><th>Aksi</th></tr></thead><tbody><?php if(empty($sales_orders)): ?><tr><td colspan="5" style="text-align:center">Tidak ada sales order.</td></tr><?php else: foreach($sales_orders as $order): ?><tr><td>#<?php echo $order['id']; ?></td><td><?php echo htmlspecialchars($order['customer_name']); ?></td><td>Rp <?php echo number_format($order['total_amount'] + $order['unique_code']); ?></td><td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td><td><a href="invoice.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="btn-sm-edit">Lihat Order</a></td></tr><?php endforeach; endif; ?></tbody></table></div>
                <div class="card full-width-card" style="margin-top:30px;"><div class="card-header"><h2>Sales Invoices (Sudah Dibayar/Diproses)</h2></div><table class="data-table"><thead><tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if(empty($sales_invoices)): ?><tr><td colspan="5" style="text-align:center">Tidak ada invoice.</td></tr><?php else: foreach($sales_invoices as $order): ?><tr><td>#<?php echo $order['id']; ?></td><td><?php echo htmlspecialchars($order['customer_name']); ?></td><td>Rp <?php echo number_format($order['total_amount'] + $order['unique_code']); ?></td><td><span class="badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td><td><a href="invoice.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="btn-sm-edit">Lihat Invoice</a></td></tr><?php endforeach; endif; ?></tbody></table></div>
            </div>
            <?php endif; ?>
            
            <?php if ($admin_role === 'superadmin'): ?>
            <div id="manajemen_pesanan" class="page">
                 <div class="main-header"><h1>Manajemen Semua Pesanan</h1></div>
                <div class="card full-width-card">
                    <div class="card-header"><h2>Daftar Pesanan Masuk</h2></div>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th><th style="text-align:right;">Aksi</th></tr></thead>
                            <tbody>
                                <?php if (empty($db_data['orders'])): ?>
                                    <tr><td colspan="6" style="text-align:center;">Belum ada pesanan.</td></tr>
                                <?php else: foreach($db_data['orders'] as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?><br><small><?php echo htmlspecialchars($order['customer_email']); ?></small></td>
                                        <td>Rp <?php echo number_format($order['total_amount'] + $order['unique_code']); ?></td>
                                        <td><span class="badge status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></td>
                                        <td style="text-align:right;">
                                            <?php if (!empty($order['payment_proof_path'])): ?><a href="#" class="btn-sm-view-proof" data-proof-url="../<?php echo htmlspecialchars($order['payment_proof_path']); ?>">Lihat Bukti</a><?php endif; ?>
                                            <a href="shipping_label.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="btn-sm-edit">Label</a>
                                            <a href="invoice.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="btn-sm-edit" style="margin-left:5px;">Invoice</a>
                                            <form method="POST" style="display:inline-block; margin-left: 10px;"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="tab_name" value="manajemen_pesanan"><input type="hidden" name="id" value="<?php echo $order['id']; ?>"><select name="order_status" onchange="this.form.submit()" style="padding: 5px;"><option value="pending" <?php if($order['order_status'] == 'pending') echo 'selected'; ?>>Pending</option><option value="paid" <?php if($order['order_status'] == 'paid') echo 'selected'; ?>>Dibayar</option><option value="shipped" <?php if($order['order_status'] == 'shipped') echo 'selected'; ?>>Dikirim</option><option value="completed" <?php if($order['order_status'] == 'completed') echo 'selected'; ?>>Selesai</option><option value="cancelled" <?php if($order['order_status'] == 'cancelled') echo 'selected'; ?>>Batal</option></select></form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($admin_role, ['superadmin', 'sales_finance'])): ?>
            <div id="manajemen_data" class="page">
                <div class="main-header"><h1>Manajemen Produk & Kategori</h1></div>
                <div class="content-grid">
                    <div class="card"><div class="card-header"><h2>Manajemen Kategori</h2></div><form method="POST"><input type="hidden" name="action" value="add_category"><input type="hidden" name="tab_name" value="manajemen_data"><div class="form-group"><label>Nama Kategori Baru</label><input type="text" name="category_name" class="form-control" required></div><button type="submit" class="btn">Tambah</button></form><hr style="margin:20px 0;"><div class="card-header" style="border:none;padding:0;margin-bottom:15px;"><h3>Daftar Kategori</h3></div><table class="data-table"><tbody><?php foreach($db_data['categories'] as $cat): ?><tr><td><?php echo htmlspecialchars($cat['name']); ?></td><td style="text-align:right;"><a href="edit_category.php?id=<?php echo $cat['id']; ?>" class="btn-sm-edit">Edit</a><form method="POST" style="display:inline-block;"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="tab_name" value="manajemen_data"><input type="hidden" name="id" value="<?php echo $cat['id']; ?>"><button type="submit" class="btn-sm-delete" onclick="return confirm('Yakin?')">Hapus</button></form></td></tr><?php endforeach; ?></tbody></table></div>
                    <div class="card"><div class="card-header"><h2>Tambah Produk Baru</h2></div>
                        <form method="POST" enctype="multipart/form-data" id="addProductForm">
                            <input type="hidden" name="action" value="add_product"><input type="hidden" name="tab_name" value="manajemen_data">
                            <div class="form-group"><label>Nama Produk</label><input type="text" name="product_name" class="form-control" required></div>
                            <div class="form-group"><label>Deskripsi Produk</label><textarea name="product_description" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label>Foto Utama</label><input type="file" name="product_photo" class="form-control" accept="image/*" required></div>
                            <div class="form-group"><label>Kategori</label><select name="product_category" class="form-control" required><option value="">-- Pilih --</option><?php foreach($db_data['categories'] as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; ?></select></div>
                            <hr style="margin: 25px 0;">
                            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;"><h3 style="margin:0;">Varian Produk</h3><button type="button" id="add-group-btn-new" class="btn-sm btn-add"><i class="fas fa-plus"></i> Tambah Grup Varian</button></div>
                            <div id="variant-groups-container-new"><p>Belum ada varian. Klik "Tambah Grup Varian" untuk memulai.</p></div>
                            <hr style="margin: 25px 0;"><button type="submit" class="btn">Tambah Produk</button>
                        </form>
                    </div>
                </div>
                <div class="card full-width-card" style="margin-top:30px;"><div class="card-header"><h2>Daftar Produk</h2></div><table class="data-table"><thead><tr><th>Foto</th><th>Nama</th><th>Kategori</th><th>Harga Mulai</th><th style="text-align:right;">Aksi</th></tr></thead><tbody><?php foreach($db_data['products'] as $prod): ?><tr><td><img src="../<?php echo htmlspecialchars($prod['image_path']); ?>" style="width:50px;height:50px;object-fit:cover;border-radius:5px;"></td><td><?php echo htmlspecialchars($prod['name']); ?></td><td><?php echo htmlspecialchars($prod['category_name'] ?? 'N/A'); ?></td><td>Rp <?php echo number_format($prod['min_price'], 0, ',', '.'); ?></td><td style="text-align:right;"><a href="edit_product.php?id=<?php echo $prod['id']; ?>" class="btn-sm-edit">Edit</a><form method="POST" style="display:inline-block;"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="tab_name" value="manajemen_data"><input type="hidden" name="id" value="<?php echo $prod['id']; ?>"><button type="submit" class="btn-sm-delete" onclick="return confirm('Yakin?')">Hapus</button></form></td></tr><?php endforeach; ?></tbody></table></div>
            </div>
            <?php endif; ?>

             <?php if ($admin_role === 'superadmin'): ?>
            <div id="manajemen_outlet" class="page">
                <div class="main-header"><h1>Manajemen Outlet</h1></div><div class="card"><div class="card-header"><h2>Tambah Outlet Baru</h2></div><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add_outlet"><input type="hidden" name="tab_name" value="manajemen_outlet"><div class="form-group"><label>Nama Outlet</label><input type="text" name="outlet_name" class="form-control" required></div><div class="form-group"><label>Alamat</label><input type="text" name="outlet_address" class="form-control" required></div><div class="form-group"><label>Kota</label><input type="text" name="outlet_city" class="form-control" required></div><div class="form-group"><label>Provinsi</label><input type="text" name="outlet_province" class="form-control" required></div><div class="form-group"><label>URL Google Maps</label><input type="url" name="outlet_maps_url" class="form-control"></div><div class="form-group"><label>Foto Outlet</label><input type="file" name="outlet_image" accept="image/*" class="form-control"></div><button type="submit" class="btn">Tambah Outlet</button></form></div><div class="card full-width-card" style="margin-top:30px;"><div class="card-header"><h2>Daftar Outlet</h2></div><table class="data-table"><thead><tr><th>Nama</th><th>Kota</th><th>Alamat</th><th style="text-align:right;">Aksi</th></tr></thead><tbody><?php foreach($db_data['outlets'] as $outlet): ?><tr><td><?php echo htmlspecialchars($outlet['name']); ?></td><td><?php echo htmlspecialchars($outlet['city']); ?></td><td><?php echo htmlspecialchars($outlet['address']); ?></td><td style="text-align:right;"><a href="edit_outlet.php?id=<?php echo $outlet['id']; ?>" class="btn-sm-edit">Edit</a><form method="POST" style="display:inline-block;"><input type="hidden" name="action" value="delete_outlet"><input type="hidden" name="tab_name" value="manajemen_outlet"><input type="hidden" name="id" value="<?php echo $outlet['id']; ?>"><button type="submit" class="btn-sm-delete" onclick="return confirm('Yakin?')">Hapus</button></form></td></tr><?php endforeach; ?></tbody></table></div>
            </div>
            <?php endif; ?>
            
            <?php if ($admin_role === 'superadmin'): ?>
            <div id="manajemen_admin" class="page">
                <div class="main-header"><h1>Manajemen Karyawan & Akses</h1></div>
                <div class="card full-width-card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <h2>Daftar Karyawan</h2>
                        <a href="employee_register.php" class="btn">Tambah Karyawan Baru</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Karyawan</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Hak Akses</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($all_employees)): ?>
                                <tr><td colspan="5" style="text-align:center">Belum ada data karyawan.</td></tr>
                            <?php else: foreach($all_employees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['employee_number'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($emp['name']); ?></strong><br><small><?php echo htmlspecialchars($emp['email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td>
                                        <?php 
                                        $roles = !empty($emp['role']) ? explode(',', $emp['role']) : ['Tidak ada'];
                                        foreach($roles as $r) {
                                            echo '<span class="badge" style="background-color:#6c757d; margin-right:5px;">' . htmlspecialchars($r) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn-sm-edit">Edit</a>
                                        
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Buat link reset password untuk karyawan ini?');">
                                            <input type="hidden" name="action" value="request_employee_password_reset">
                                            <input type="hidden" name="tab_name" value="manajemen_admin">
                                            <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                            <button type="submit" class="btn-sm-edit" style="background-color: #ffc107; color: #212529;">Reset Pass</button>
                                        </form>
                                    
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?');">
                                            <input type="hidden" name="action" value="delete_employee">
                                            <input type="hidden" name="tab_name" value="manajemen_admin">
                                            <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                            <button type="submit" class="btn-sm-delete">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($admin_role, ['superadmin', 'hrd'])): ?>
            <div id="manajemen_hiring" class="page">
                <div class="main-header"><h1>Aplikasi Masuk (Hiring)</h1></div>
                <div class="card full-width-card"><div class="card-header"><h2>Daftar Pelamar Kerja</h2></div><table class="data-table"><thead><tr><th>Nama Pelamar</th><th>Posisi Dilamar</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if(empty($job_applications)): ?><tr><td colspan="5" style="text-align:center">Belum ada aplikasi yang masuk.</td></tr><?php else: foreach($job_applications as $app): ?><tr><td><?php echo htmlspecialchars($app['full_name']); ?></td><td><?php echo htmlspecialchars($app['position']); ?></td><td><?php echo date('d M Y', strtotime($app['application_date'])); ?></td><td><span class="badge status-<?php echo $app['status']; ?>"><?php echo $app['status']; ?></span></td><td><a href="../<?php echo $app['cv_path']; ?>" target="_blank" class="btn-sm-edit">Lihat CV</a></td></tr><?php endforeach; endif; ?></tbody></table></div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <div id="proofModal" class="modal"><span class="close-modal">&times;</span><img class="modal-content" id="imgProof"></div>
    <script src="dashboard-script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data untuk chart pengunjung
        const visitorData = {
            labels: <?php echo json_encode($visitor_chart_data['labels'] ?? []); ?>,
            data: <?php echo json_encode($visitor_chart_data['data'] ?? []); ?>
        };
        const visitorChartCtx = document.getElementById('visitorChart');
        if (visitorChartCtx && visitorData.labels.length > 0) {
            new Chart(visitorChartCtx, { type: 'line', data: { labels: visitorData.labels, datasets: [{ label: 'Pengunjung', data: visitorData.data, backgroundColor: 'rgba(107, 70, 193, 0.1)', borderColor: 'rgba(107, 70, 193, 1)', borderWidth: 2, pointBackgroundColor: 'rgba(107, 70, 193, 1)', tension: 0.3, fill: true }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
        }
        
        // Logika untuk modal lihat bukti pembayaran
        var modal = document.getElementById("proofModal");
        var modalImg = document.getElementById("imgProof");
        var span = document.getElementsByClassName("close-modal")[0];
        document.querySelectorAll('.btn-sm-view-proof').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                modal.style.display = "flex";
                modalImg.src = item.getAttribute('data-proof-url');
            });
        });
        if(span) { span.onclick = function() { modal.style.display = "none"; } }
        window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }

        // Logika untuk form tambah produk varian
        const groupsContainerNew = document.getElementById('variant-groups-container-new');
        if(groupsContainerNew){
            let groupIndexNew = 0;
            const createOptionRowNew = (groupKey, optionIndex) => { return `<div class="variant-option-row"><input type="text" class="form-control" name="variants[${groupKey}][options][${optionIndex}][name]" placeholder="Nama Opsi (cth: 250g)" required><input type="number" class="form-control" name="variants[${groupKey}][options][${optionIndex}][price]" placeholder="Harga" required><input type="number" class="form-control" name="variants[${groupKey}][options][${optionIndex}][stock]" placeholder="Stok" value="0" required><button type="button" class="btn-sm btn-delete btn-remove-option">&times;</button></div>`; };
            const createGroupCardNew = () => {
                const noVariantMsg = groupsContainerNew.querySelector('p');
                if (noVariantMsg) noVariantMsg.remove();
                groupIndexNew++;
                const groupKey = 'newgroup_' + groupIndexNew;
                let optionIndex = 0;
                const card = document.createElement('div');
                card.className = 'variant-group-card';
                card.innerHTML = `<div class="variant-group-header"><input type="text" name="variants[${groupKey}][group_name]" placeholder="Nama Grup Varian Baru (cth: Warna)" required><button type="button" class="btn-sm btn-delete btn-remove-group">&times; Hapus Grup</button></div><div class="variant-group-body"><div class="variant-option-row variant-option-header"><span>Nama Opsi</span><span>Harga</span><span>Stok</span><span></span></div>${createOptionRowNew(groupKey, optionIndex)}<button type="button" class="btn-sm btn-add btn-add-option" style="margin-top: 10px;">+ Tambah Opsi</button></div>`;
                groupsContainerNew.appendChild(card);
            };
            const addGroupBtn = document.getElementById('add-group-btn-new');
            if(addGroupBtn) addGroupBtn.addEventListener('click', createGroupCardNew);
            
            groupsContainerNew.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-option')) {
                    const body = e.target.closest('.variant-group-body');
                    const groupKey = e.target.closest('.variant-group-card').querySelector('input[name*="[group_name]"]').name.match(/\[(.*?)\]/)[1];
                    let optionIndex = body.querySelectorAll('.variant-option-row').length;
                    e.target.insertAdjacentHTML('beforebegin', createOptionRowNew(groupKey, optionIndex));
                }
                if (e.target.classList.contains('btn-remove-option')) {
                     if(e.target.closest('.variant-group-body').querySelectorAll('.variant-option-row').length > 2){
                         e.target.closest('.variant-option-row').remove();
                    } else { alert('Setiap grup harus memiliki minimal satu opsi varian.'); }
                }
                if (e.target.classList.contains('btn-remove-group')) {
                    if(confirm('Apakah Anda yakin ingin menghapus grup varian ini?')) { e.target.closest('.variant-group-card').remove(); }
                }
            });
        }
    });
    </script>
</body>
</html>