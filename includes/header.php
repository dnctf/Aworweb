<?php
// Memulai sesi di baris paling atas untuk memastikan sesi tersedia di semua halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Memuat koneksi database jika belum ada
if (!isset($conn)) {
    include_once 'db.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awor Coffee</title>
    <link rel="stylesheet" href="style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* PERBAIKAN: Membuat area hover lebih besar untuk mencegah pop-up hilang */
        .header-icon-container { 
            position: relative; 
            padding: 20px 10px; /* Menambah area padding atas/bawah untuk hover */
            margin: -20px -10px; /* Menetralkan padding agar layout tidak bergeser */
            cursor: pointer;
        }
        .cart-count { 
            position: absolute; 
            top: 12px; /* Disesuaikan karena padding baru */
            right: -2px; /* Disesuaikan karena padding baru */
            background: #e74c3c; 
            color: white; 
            border-radius: 50%; 
            padding: 0px 5px; 
            font-size: 0.7em; 
            border: 2px solid #fff;
            display: none; /* Default disembunyikan, ditampilkan oleh JS */
        }
        .cart-preview, .account-dropdown {
            display: none; 
            position: absolute; 
            top: 100%; /* Posisi pop-up tepat di bawah area hover */
            right: 0;
            background-color: #fff; 
            border-radius: 8px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            min-width: 320px; 
            z-index: 1001; 
            padding: 10px;
            opacity: 0; 
            visibility: hidden;
            transform: translateY(10px); 
            transition: all 0.3s ease;
        }
        .header-icon-container:hover .cart-preview, 
        .header-icon-container:hover .account-dropdown {
            display: block; 
            opacity: 1; 
            visibility: visible; 
            transform: translateY(0);
        }
        .cart-preview-items { 
            list-style: none; padding: 0; margin: 0; 
            max-height: 300px; overflow-y: auto; 
        }
        .cart-preview-item { 
            display: flex; align-items: center; gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-preview-item:last-child { border-bottom: none; }
        .cart-preview-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .cart-item-details strong { display: block; margin-bottom: 2px;}
        .cart-item-details span { font-size: 0.9em; color: #555; }
        .btn-view-cart { 
            display: block; text-align: center; background: #333; color: #fff;
            padding: 12px; border-radius: 5px; text-decoration: none; margin: 10px;
            font-weight: 600;
        }
        .account-dropdown ul { list-style: none; padding: 0; margin: 0; }
        .account-dropdown ul li a {
            display: block; padding: 10px; text-decoration: none; color: #333;
            border-bottom: 1px solid #f0f0f0; transition: background-color 0.2s;
        }
        .account-dropdown ul li:last-child a { border-bottom: none; }
        .account-dropdown ul li a:hover { background-color: #f5f5f5; }
        .account-dropdown ul hr { margin: 5px 0; border: 0; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo">
                 <?php
                if (isset($conn)) {
                    $logo_res = $conn->query("SELECT file_path FROM site_assets WHERE asset_name = 'main_logo' LIMIT 1");
                    if ($logo_res && $logo_res->num_rows > 0) {
                        $main_logo = $logo_res->fetch_assoc();
                        echo '<img src="' . htmlspecialchars($main_logo['file_path']) . '" alt="Awor Coffee">';
                    } else { echo 'AWOR COFFEE'; }
                }
                ?>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="index.php#outlets">Outlets</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                </ul>
            </nav>
            <div class="header-right">
                <div class="search-bar">
                    <input type="text" placeholder="Cari produk...">
                    <button><i class="fa fa-search"></i></button>
                </div>
                
                <div class="header-icon-container cart-container">
                    <a href="cart.php" style="color: inherit; text-decoration: none;">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <div class="cart-preview"><div id="cart-preview-content"><p style="padding: 10px;">Keranjang Anda kosong.</p></div></div>
                </div>

                <div class="header-icon-container account-container">
                    <i class="fa fa-user"></i>
                    <div class="account-dropdown">
                        <ul>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><a href="user_profile.php"><strong>Profil: <?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></a></li>
                                <li><a href="order_history.php">Riwayat Pesanan</a></li>
                                <li><a href="logout_user.php">Logout</a></li>
                            <?php else: ?>
                                <li><a href="user_profile.php">Login / Daftar</a></li>
                            <?php endif; ?>
                            <hr>
                            <li><a href="admin/login.php" style="font-size: 0.9em; color: #777;">Login Admin</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartContainer = document.querySelector('.cart-container');
        const cartPreviewContent = document.getElementById('cart-preview-content');
        const cartCount = document.querySelector('.cart-count');

        const updateCartPreview = () => {
            fetch('get_cart_preview.php')
                .then(response => response.json())
                .then(data => {
                    cartCount.textContent = data.item_count;
                    cartCount.style.display = data.item_count > 0 ? 'block' : 'none';

                    if (data.items.length > 0) {
                        let content = '<ul class="cart-preview-items">';
                        data.items.forEach(item => {
                            content += `
                                <li class="cart-preview-item">
                                    <img src="${item.image_path}" alt="${item.product_name}">
                                    <div class="cart-item-details">
                                        <strong>${item.product_name}</strong>
                                        <span>${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</span>
                                    </div>
                                </li>
                            `;
                        });
                        content += '</ul><a href="cart.php" class="btn-view-cart">Lihat & Bayar</a>';
                        cartPreviewContent.innerHTML = content;
                    } else {
                        cartPreviewContent.innerHTML = '<p style="padding: 10px;">Keranjang Anda kosong.</p>';
                    }
                })
                .catch(error => console.error('Error fetching cart preview:', error));
        };
        
        if(cartContainer) {
             updateCartPreview();
        }
    });
</script>
</body>
</html>