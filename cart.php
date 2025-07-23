<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/header.php';

$cart_items = [];
$subtotal = 0;
$user_data = null; // Variabel untuk menyimpan data pengguna

// Cek jika pengguna login dan ambil datanya
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}

if (!empty($_SESSION['cart'])) {
    $variant_ids = array_keys($_SESSION['cart']);
    if (!empty($variant_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
        
        // PERBAIKAN KRITIS: Mengganti v.name menjadi v.variant_option as variant_name
        $sql = "SELECT v.id as variant_id, v.variant_option as variant_name, v.price, p.name as product_name, p.image_path FROM product_variants v JOIN products p ON v.product_id = p.id WHERE v.id IN ($id_placeholders)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($variant_ids)), ...$variant_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $items_from_db = [];
        while ($row = $result->fetch_assoc()) { $items_from_db[$row['variant_id']] = $row; }
        foreach ($_SESSION['cart'] as $variant_id => $quantity) {
            if (isset($items_from_db[$variant_id])) {
                $item = $items_from_db[$variant_id];
                $item['quantity'] = $quantity;
                $subtotal += $item['price'] * $quantity;
                $cart_items[] = $item;
            }
        }
    }
}
?>
<style>
    .cart-page { max-width: 1200px; margin: 40px auto; }
    .cart-grid { display: grid; grid-template-columns: 1fr; gap: 40px; }
    @media (min-width: 992px) { .cart-grid { grid-template-columns: 2fr 1fr; align-items: flex-start; } }
    .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .cart-table th, .cart-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    .cart-item-info { display: flex; align-items: center; gap: 15px; }
    .cart-item-info img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
    .shipping-details, .cart-summary { border-radius: 8px; padding: 25px; }
    .shipping-details { border: 1px solid #eee; }
    .cart-summary { background-color: #f9f9f9; position: sticky; top: 90px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; }
    .summary-line { display: flex; justify-content: space-between; margin-bottom: 10px; }
    .btn-update-cart { background-color: #6c757d; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
    .btn-checkout { background-color: #2d3748; color: white; border: none; padding: 15px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 1.1em; }
    .quantity-selector { display: flex; align-items: center; }
    .quantity-btn {
        width: 30px; height: 30px; border: 1px solid #ddd;
        background-color: #f9f9f9; cursor: pointer; font-size: 1.2em;
        line-height: 28px; text-align: center;
    }
    .quantity-input {
        width: 50px; height: 30px; text-align: center;
        border: 1px solid #ddd; border-left: none; border-right: none;
        box-sizing: border-box; -moz-appearance: textfield;
    }
    .quantity-input::-webkit-outer-spin-button, .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none; margin: 0;
    }
</style>

<main>
    <div class="container cart-page">
        <h1>Keranjang Belanja</h1>
        <?php if (empty($cart_items)): ?>
            <p>Keranjang Anda kosong. <a href="shop.php">Mulai belanja</a>.</p>
        <?php else: ?>
            <form action="checkout_process.php" method="POST">
                <div class="cart-grid">
                    <div class="cart-main">
                        <table class="cart-table">
                            <thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Total</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr class="cart-item-row" data-price="<?php echo (float)$item['price']; ?>">
                                        <td><div class="cart-item-info"><img src="<?php echo htmlspecialchars($item['image_path']); ?>"><div><strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br><small><?php echo htmlspecialchars($item['variant_name']); ?></small></div></div></td>
                                        <td>Rp <?php echo number_format((float)$item['price']); ?></td>
                                        <td>
                                            <div class="quantity-selector">
                                                <button type="button" class="quantity-btn btn-minus">-</button>
                                                <input type="number" class="quantity-input" name="quantities[<?php echo $item['variant_id']; ?>]" value="<?php echo (int)$item['quantity']; ?>" min="1">
                                                <button type="button" class="quantity-btn btn-plus">+</button>
                                            </div>
                                        </td>
                                        <td><strong>Rp <span class="item-subtotal"><?php echo number_format((float)$item['price'] * (int)$item['quantity']); ?></span></strong></td>
                                        <td><button type="submit" name="remove_item" value="<?php echo $item['variant_id']; ?>" formaction="update_cart.php" style="background:none; border:none; color:red;" onclick="return confirm('Hapus item?');">&times;</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="update_cart" value="1" formaction="update_cart.php" class="btn-update-cart">Update Keranjang</button>
                        <div class="shipping-details" style="margin-top: 40px;">
                            <h2>Alamat Pengiriman</h2>
                            <div class="form-group"><label>Nama Penerima</label><input type="text" class="form-control" name="customer_name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required></div>
                            <div class="form-group"><label>Email</label><input type="email" class="form-control" name="customer_email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required></div>
                            <div class="form-group"><label>No. Telepon</label><input type="tel" class="form-control" name="customer_phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required></div>
                            <div class="form-group"><label>Alamat Lengkap</label><textarea class="form-control" name="customer_address" rows="4" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea></div>
                        </div>
                    </div>
                    <div class="cart-summary">
                        <h2>Ringkasan Pesanan</h2>
                        <div class="form-group"><label>Kurir</label><select class="form-control" name="shipping_courier" id="courier_select" required><option value="JNE Reguler" data-cost="10000">JNE Reguler (Rp 10.000)</option><option value="J&T Express" data-cost="12000">J&T Express (Rp 12.000)</option></select></div>
                        <div class="form-group"><label>Pembayaran</label><select class="form-control" name="payment_method_id" required><?php $payment_methods = $conn->query("SELECT * FROM payment_methods WHERE is_active = 1"); while($method = $payment_methods->fetch_assoc()){ echo "<option value='{$method['id']}'>{$method['name']}</option>"; } ?></select></div><hr>
                        <div class="summary-line"><span>Subtotal:</span><strong>Rp <span id="summary-subtotal"><?php echo number_format((float)$subtotal); ?></span></strong></div>
                        <div class="summary-line"><span>Ongkir:</span><strong>Rp <span id="summary-shipping">10.000</span></strong></div><hr>
                        <div class="summary-line"><h3>Total:</h3><h3>Rp <span id="summary-total"><?php echo number_format((float)$subtotal + 10000); ?></span></h3></div>
                        <input type="hidden" name="shipping_cost" id="shipping_cost_input" value="10000">
                        <button type="submit" class="btn-checkout">Buat Pesanan</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formatRupiah = (number) => new Intl.NumberFormat('id-ID').format(number);

    const updateTotals = () => {
        let newSubtotal = 0;
        document.querySelectorAll('.cart-item-row').forEach(row => {
            const price = parseFloat(row.dataset.price);
            const quantity = parseInt(row.querySelector('.quantity-input').value);
            const itemSubtotal = price * quantity;
            row.querySelector('.item-subtotal').textContent = formatRupiah(itemSubtotal);
            newSubtotal += itemSubtotal;
        });
        const courierSelect = document.getElementById('courier_select');
        const selectedCourier = courierSelect.options[courierSelect.selectedIndex];
        const shippingCost = parseFloat(selectedCourier.dataset.cost);

        document.getElementById('summary-subtotal').textContent = formatRupiah(newSubtotal);
        document.getElementById('summary-shipping').textContent = formatRupiah(shippingCost);
        document.getElementById('shipping_cost_input').value = shippingCost;
        document.getElementById('summary-total').textContent = formatRupiah(newSubtotal + shippingCost);
    };

    document.querySelector('.cart-main').addEventListener('click', function(e) {
        if (e.target.classList.contains('quantity-btn')) {
            const selector = e.target.closest('.quantity-selector');
            const input = selector.querySelector('.quantity-input');
            let currentValue = parseInt(input.value);
            
            if (e.target.classList.contains('btn-plus')) {
                currentValue++;
            } else if (e.target.classList.contains('btn-minus')) {
                currentValue = Math.max(1, currentValue - 1);
            }
            input.value = currentValue;
            input.dispatchEvent(new Event('change'));
        }
    });

    document.querySelectorAll('.quantity-input').forEach(input => input.addEventListener('change', updateTotals));
    document.getElementById('courier_select').addEventListener('change', updateTotals);
    updateTotals();
});
</script>
<?php include_once 'includes/footer.php'; ?>