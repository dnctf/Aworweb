<?php
session_start();
include_once 'includes/db.php';
header('Content-Type: application/json');

$response_data = ['items' => [], 'item_count' => 0];

if (!empty($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
    $variant_ids = array_keys($cart);
    $item_count = 0;
    
    if (!empty($variant_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
        // PERBAIKAN: Mengganti v.name menjadi v.variant_option
        $sql = "SELECT v.id as variant_id, v.variant_option as variant_name, v.price, p.name as product_name, p.image_path FROM product_variants v JOIN products p ON v.product_id = p.id WHERE v.id IN ($id_placeholders)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($variant_ids)), ...$variant_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $quantity = $cart[$row['variant_id']];
            $row['quantity'] = $quantity;
            $response_data['items'][] = $row;
            $item_count += $quantity;
        }
    }
    $response_data['item_count'] = $item_count;
}

echo json_encode($response_data);
?>