<?php
// htdocs/checkout_process.php - VERSI FINAL DIPERBAIKI
session_start();
include_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header('Location: cart.php?error=Akses tidak sah atau keranjang Anda kosong.');
    exit();
}

$customer_name = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? '');
$payment_method_id = (int)($_POST['payment_method_id'] ?? 0);
$shipping_courier = trim($_POST['shipping_courier'] ?? '');
$shipping_cost = (float)($_POST['shipping_cost'] ?? 0);
$unique_code = rand(100, 999);
$subtotal = 0;

foreach ($_SESSION['cart'] as $variant_id => $quantity) {
    $stmt = $conn->prepare("SELECT price FROM product_variants WHERE id = ?");
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if($result) { $subtotal += $result['price'] * $quantity; }
}
$total_amount = $subtotal + $shipping_cost;

$conn->begin_transaction();
try {
    $sql = "INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount, unique_code, payment_method_id, shipping_courier, shipping_cost, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt_order = $conn->prepare($sql);
    $stmt_order->bind_param("ssssdidis", $customer_name, $customer_email, $customer_phone, $customer_address, $total_amount, $unique_code, $payment_method_id, $shipping_courier, $shipping_cost);
    $stmt_order->execute();
    $order_id = $conn->insert_id;
    
    // PERBAIKAN KRITIS: Mengganti v.name menjadi v.variant_option
    $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, variant_id, quantity, price, product_name, variant_name) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $variant_id => $quantity) {
        $stmt_item_details = $conn->prepare("SELECT v.price, p.name as product_name, v.variant_option as variant_name FROM product_variants v JOIN products p ON v.product_id = p.id WHERE v.id = ?");
        $stmt_item_details->bind_param("i", $variant_id);
        $stmt_item_details->execute();
        $item_details = $stmt_item_details->get_result()->fetch_assoc();
        if ($item_details) {
            $stmt_order_item->bind_param("iiidss", $order_id, $variant_id, $quantity, $item_details['price'], $item_details['product_name'], $item_details['variant_name']);
            $stmt_order_item->execute();
        }
    }
    $conn->commit();
    unset($_SESSION['cart']);
    header("Location: payment_confirmation.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header('Location: cart.php?error=Database Error: ' . urlencode($e->getMessage()));
    exit();
}
?>