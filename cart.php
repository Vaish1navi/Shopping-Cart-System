<?php
session_start();
require 'database/dbConnect.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $response = ['success' => false, 'error' => ''];
    
    try {
        switch ($action) {
            case 'update_quantity':
                $new_quantity = (int)$_POST['quantity'];
                if ($new_quantity <= 0) {
                    throw new Exception("Quantity must be at least 1");
                }
                
                if (!isset($_SESSION['cart'][$product_id])) {
                    throw new Exception("Product not found in cart");
                }
                
                // Verify product exists
                $stmt = $conn->prepare("SELECT sno, prodname, prodprice, prodimagelink FROM products WHERE sno = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $stmt->close();
                    throw new Exception("Product not found in database");
                }
                
                $product = $result->fetch_assoc();
                $stmt->close();
                
                // Update session
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['sno'],
                    'name' => $product['prodname'],
                    'price' => $product['prodprice'],
                    'image' => $product['prodimagelink'],
                    'quantity' => $new_quantity
                ];

                // Calculate totals
                $response = calculateCartTotals();
                $response['success'] = true;
                $response['item_total'] = number_format($product['prodprice'] * $new_quantity, 2);
                $response['item_count'] = count($_SESSION['cart']);
                break;

            case 'remove_item':
                if (!isset($_SESSION['cart'][$product_id])) {
                    throw new Exception("Product not found in cart");
                }
                
                // Remove from session
                unset($_SESSION['cart'][$product_id]);

                // Calculate totals after removal
                $response = calculateCartTotals();
                $response['success'] = true;
                $response['item_count'] = count($_SESSION['cart']);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Function to calculate cart totals
// function calculateCartTotals() {
//     $subtotal = 0;
//     foreach ($_SESSION['cart'] as $item) {
//         $subtotal += $item['price'] * $item['quantity'];
//     }
    
//     $tax_rate = 0.08;
//     $tax = $subtotal * $tax_rate;
//     $total = $subtotal + $tax;
    
//     return [
//         'subtotal' => number_format($subtotal, 2),
//         'tax' => number_format($tax, 2),
//         'total' => number_format($total, 2)
//     ];
// }

// In your cart.php, update the calculateCartTotals function:
function calculateCartTotals() {
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Set shipping cost (free over $50, otherwise $5)
    $shipping = ($subtotal >= 50) ? 0 : 5;
    
    // Calculate tax (8% of subtotal + shipping)
    $tax_rate = 0.08;
    $tax = ($subtotal + $shipping) * $tax_rate;
    
    // Calculate total
    $total = $subtotal + $shipping + $tax;
    
    return [
        'subtotal' => number_format($subtotal, 2),
        'shipping' => number_format($shipping, 2),
        'tax' => number_format($tax, 2),
        'total' => number_format($total, 2)
    ];
}

// Get full product details for all items in cart
$cart_items = [];
$totals = ['subtotal' => 0, 'tax' => 0, 'total' => 0];

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $stmt = $conn->prepare("SELECT sno, prodname, prodprice, prodimagelink FROM products WHERE sno IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        $product_id = $product['sno'];
        $quantity = $_SESSION['cart'][$product_id]['quantity'];
        
        $cart_items[$product_id] = [
            'id' => $product['sno'],
            'name' => $product['prodname'],
            'price' => $product['prodprice'],
            'image' => $product['prodimagelink'],
            'quantity' => $quantity
        ];
    }
    $stmt->close();
    
    // Update session with complete product info
    $_SESSION['cart'] = $cart_items;
    
    // Calculate totals
    $totals = calculateCartTotals();
}

$subtotal = $totals['subtotal'];
$tax = $totals['tax'];
$total = $totals['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - ShopEase</title>
    <?php require 'essentials/commonLink.html' ?>
    <style>
        .cart-item-removed {
            transition: all 0.3s ease;
            opacity: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
            border: none;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
        }
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php require 'essentials/header.php' ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Your Shopping Cart</h1>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="lg:w-2/3 bg-white p-6 rounded-lg shadow">
                <div id="cart-items-container">
                    <?php if (empty($cart_items)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg text-gray-600 mb-4">Your cart is empty</p>
                            <a href="products.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                                Continue Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="flex border-b py-6 cart-item" data-product-id="<?= htmlspecialchars($id) ?>">
                                <div class="w-24 h-24 overflow-hidden rounded-md mr-4">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium"><?= htmlspecialchars($item['name']) ?></h3>
                                        <span class="font-bold item-total">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                    </div>
                                    <div class="text-gray-500 mb-2">₹<?= number_format($item['price'], 2) ?> each</div>
                                    <div class="flex items-center">
                                        <div class="flex items-center">
                                            <button class="decrease-quantity px-3 py-1 bg-gray-200 rounded-l hover:bg-gray-300" 
                                                    data-id="<?= htmlspecialchars($id) ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" value="<?= $item['quantity'] ?>" min="1" 
                                                   class="quantity-input border-t border-b border-gray-300 py-1 text-center"
                                                   data-id="<?= htmlspecialchars($id) ?>">
                                            <button class="increase-quantity px-3 py-1 bg-gray-200 rounded-r hover:bg-gray-300" 
                                                    data-id="<?= htmlspecialchars($id) ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button class="remove-item ml-4 text-red-600 hover:text-red-700 text-sm" 
                                                data-id="<?= htmlspecialchars($id) ?>">
                                            <i class="fas fa-trash mr-1"></i> Remove
                                        </button>
                                        <div class="loading-spinner ml-2"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:w-1/3 bg-white p-6 rounded-lg shadow h-fit sticky top-4">
                <h3 class="font-bold text-lg mb-4">Order Summary</h3>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal (<span id="item-count"><?= count($cart_items) ?></span> items)</span>
                        <span class="font-medium" id="subtotal">₹<?= $subtotal ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium">Free</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-medium" id="tax">₹<?= $tax ?></span>
                    </div>
                </div>
                
                <div class="border-t pt-4 mb-6">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span id="total">₹<?= $total ?></span>
                    </div>
                </div>
                
                <form action="checkout.php" method="post">
                    <input type="hidden" name="cart_data" value="<?= htmlspecialchars(json_encode($cart_items)) ?>">
                    <input type="hidden" name="subtotal" value="<?= str_replace('₹', '', $subtotal) ?>">
                    <input type="hidden" name="tax" value="<?= str_replace('₹', '', $tax) ?>">
                    <input type="hidden" name="total" value="<?= str_replace('₹', '', $total) ?>">
                    
                    <button type="submit" id="checkout-btn" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 transition disabled:opacity-50" 
                            <?= empty($cart_items) ? 'disabled' : '' ?>>
                        Proceed to Checkout
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include('essentials/footer.php'); ?>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to show loading spinner
    function showLoading(element) {
        const spinner = element.querySelector('.loading-spinner') || element.closest('.cart-item').querySelector('.loading-spinner');
        spinner.style.display = 'block';
    }
    
    // Helper function to hide loading spinner
    function hideLoading(element) {
        const spinner = element.querySelector('.loading-spinner') || element.closest('.cart-item').querySelector('.loading-spinner');
        spinner.style.display = 'none';
    }
    
    // Helper function to show error
    function showError(message) {
        alert('Error: ' + message);
    }

    // Quantity adjustments
    document.querySelectorAll('.decrease-quantity, .increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const input = this.closest('.flex').querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (this.classList.contains('decrease-quantity')) {
                if (quantity > 1) quantity--;
            } else {
                quantity++;
            }
            
            input.value = quantity;
            updateQuantity(productId, quantity, this);
        });
    });
    
    // Direct quantity input
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.id;
            const quantity = parseInt(this.value);
            if (quantity > 0) {
                updateQuantity(productId, quantity, this);
            } else {
                this.value = 1;
                updateQuantity(productId, 1, this);
            }
        });
    });
    
    // Remove item
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this item?')) {
                const productId = this.dataset.id;
                const itemElement = this.closest('.cart-item');
                showLoading(this);
                
                const formData = new FormData();
                formData.append('action', 'remove_item');
                formData.append('product_id', productId);
                
                fetch('cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        itemElement.classList.add('cart-item-removed');
                        setTimeout(() => {
                            itemElement.remove();
                            updateCartSummary(data);
                            
                            // If cart is now empty, show empty cart message
                            if (data.item_count === 0) {
                                document.getElementById('cart-items-container').innerHTML = `
                                    <div class="text-center py-12">
                                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg text-gray-600 mb-4">Your cart is empty</p>
                                        <a href="products.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                                            Continue Shopping
                                        </a>
                                    </div>
                                `;
                                document.getElementById('checkout-btn').disabled = true;
                            }
                        }, 300);
                    } else {
                        showError(data.error || 'Failed to remove item');
                    }
                })
                .catch(error => {
                    showError(error.message);
                })
                .finally(() => {
                    hideLoading(this);
                });
            }
        });
    });
    
    function updateQuantity(productId, quantity, element) {
        showLoading(element);
        
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const itemElement = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                if (itemElement) {
                    itemElement.querySelector('.item-total').textContent = `₹${data.item_total}`;
                    updateCartSummary(data);
                }
            } else {
                showError(data.error || 'Failed to update quantity');
                // Reset to previous value
                element.value = element.dataset.previousValue || 1;
            }
        })
        .catch(error => {
            showError(error.message);
            // Reset to previous value
            element.value = element.dataset.previousValue || 1;
        })
        .finally(() => {
            hideLoading(element);
        });
    }
    
    function updateCartSummary(data) {
        if (data.subtotal) {
            document.getElementById('subtotal').textContent = `₹${data.subtotal}`;
            document.getElementById('tax').textContent = `₹${data.tax}`;
            document.getElementById('total').textContent = `₹${data.total}`;
        }
        if (data.item_count !== undefined) {
            document.getElementById('item-count').textContent = data.item_count;
        }
    }
    
    // Store initial values for quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.dataset.previousValue = input.value;
    });
});
</script>
</body>
</html>