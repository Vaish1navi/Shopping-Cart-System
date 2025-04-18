<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

file_put_contents('debug.log', print_r($_POST, true), FILE_APPEND);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['product_id']) || !isset($_POST['product_name']) || !isset($_POST['product_price'])) {
        throw new Exception('Missing product data');
    }

    $product_id = $_POST['product_id'];
    $product_name = trim($_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_image = isset($_POST['product_image']) ? $_POST['product_image'] : '';

    if (empty($product_id) || empty($product_name) || $product_price <= 0) {
        throw new Exception('Invalid product data');
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'quantity' => 1
        ];
    }

    $response = [
        'success' => true,
        'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
        'message' => 'Product added to cart'
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>