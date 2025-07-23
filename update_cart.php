<?php
// htdocs/update_cart.php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['cart'])) {
    
    // Logika untuk menghapus item
    if (isset($_POST['remove_item'])) {
        $variant_id_to_remove = $_POST['remove_item'];
        if (isset($_SESSION['cart'][$variant_id_to_remove])) {
            unset($_SESSION['cart'][$variant_id_to_remove]);
        }
    }

    // Logika untuk update kuantitas
    if (isset($_POST['update_cart'])) {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $variant_id => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $_SESSION['cart'][$variant_id] = $quantity;
                } else {
                    // Hapus item jika kuantitas 0 atau kurang
                    unset($_SESSION['cart'][$variant_id]);
                }
            }
        }
    }
}

// Kembali ke halaman keranjang
header('Location: cart.php');
exit();
?>