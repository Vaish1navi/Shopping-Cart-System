<?php
function getProductRecommendations($cart_product_ids, $conn = null) {
    // If database connection is available, use database recommendations
    if ($conn) {
        return getDatabaseRecommendations($cart_product_ids, $conn);
    }
    
    // Fallback to mock recommendations if no database
    return getMockRecommendations($cart_product_ids);
}

function getDatabaseRecommendations($cart_product_ids, $conn) {
    if (empty($cart_product_ids)) {
        // If cart is empty, return popular products
        $stmt = $conn->prepare("
            SELECT p.* FROM products p
            LEFT JOIN product_recommendations r ON p.product_id = r.recommended_id
            GROUP BY p.product_id
            ORDER BY COUNT(r.product_id) DESC, p.popularity DESC
            LIMIT 4
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get recommendations based on items in cart
    $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));
    $stmt = $conn->prepare("
        SELECT p.*, SUM(r.strength) as recommendation_strength 
        FROM products p
        JOIN product_recommendations r ON p.product_id = r.recommended_id
        WHERE r.product_id IN ($placeholders)
        AND p.product_id NOT IN ($placeholders)
        GROUP BY p.product_id
        ORDER BY recommendation_strength DESC
        LIMIT 4
    ");
    
    // Bind parameters twice (for both WHERE conditions)
    $types = str_repeat('i', count($cart_product_ids) * 2);
    $params = array_merge($cart_product_ids, $cart_product_ids);
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMockRecommendations($cart_product_ids) {
    // Sample product data
    $all_products = [
        1 => ['id' => 1, 'name' => 'Wireless Headphones', 'price' => 99.99, 'image' => 'images/headphones.jpg'],
        2 => ['id' => 2, 'name' => 'Bluetooth Speaker', 'price' => 59.99, 'image' => 'images/speaker.jpg'],
        3 => ['id' => 3, 'name' => 'Phone Case', 'price' => 19.99, 'image' => 'images/case.jpg'],
        4 => ['id' => 4, 'name' => 'Screen Protector', 'price' => 9.99, 'image' => 'images/protector.jpg'],
        5 => ['id' => 5, 'name' => 'Charging Cable', 'price' => 12.99, 'image' => 'images/cable.jpg'],
        6 => ['id' => 6, 'name' => 'Smart Watch', 'price' => 199.99, 'image' => 'images/watch.jpg'],
        7 => ['id' => 7, 'name' => 'Wireless Earbuds', 'price' => 79.99, 'image' => 'images/earbuds.jpg'],
        8 => ['id' => 8, 'name' => 'Laptop Stand', 'price' => 29.99, 'image' => 'images/stand.jpg'],
    ];
    
    // Remove products already in cart
    $available_products = array_diff_key($all_products, array_flip($cart_product_ids));
    
    // Simple recommendation logic
    if (in_array(1, $cart_product_ids)) {
        // Headphones in cart
        return [
            $all_products[2], // Speaker
            $all_products[5], // Cable
            $all_products[7], // Earbuds
            $all_products[6]  // Smart Watch
        ];
    } elseif (in_array(3, $cart_product_ids)) {
        // Phone case in cart
        return [
            $all_products[4], // Screen protector
            $all_products[5], // Cable
            $all_products[1], // Headphones
            $all_products[7]  // Earbuds
        ];
    } else {
        // Default popular items (first 4 available products)
        return array_slice($available_products, 0, 4);
    }
}
?>