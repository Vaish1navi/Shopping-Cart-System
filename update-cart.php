<?php
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['cart'])) {
        throw new Exception('Cart not initialized');
    }

    if (!isset($_POST['action'])) {
        throw new Exception('No action specified');
    }

    $product_id = $_POST['product_id'] ?? null;

    if (!$product_id) {
        throw new Exception('Product ID not provided');
    }

    switch ($_POST['action']) {
        case 'update_quantity':
            $new_quantity = intval($_POST['quantity']);
            if ($new_quantity < 1) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
            }
            break;

        case 'remove_item':
            unset($_SESSION['cart'][$product_id]);
            break;

        default:
            throw new Exception('Invalid action');
    }

    $response = [
        'success' => true,
        'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
        'cart_total' => calculateCartTotal(),
        'message' => 'Cart updated successfully'
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

function calculateCartTotal() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return number_format($total, 2);
}

echo json_encode($response);
?>