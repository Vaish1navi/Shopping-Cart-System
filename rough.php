<?php
session_start();
require 'database/dbConnect.php'; // Your database connection file
require 'recommendations.php'; // Recommendation functions

// Calculate cart totals
$subtotal = 0;
$cart_items = $_SESSION['cart'] ?? [];

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax_rate = 0.08; // 8% tax
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;

// Get product recommendations based on items in cart
$recommendations = getProductRecommendations(array_keys($cart_items), $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - ShopEase</title>
    <?php require 'essentials/commonLink.html' ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .recommendation-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .recommendation-img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        .recommendation-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 3em;
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
                            <a href="index.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                                Continue Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="flex border-b py-6 cart-item" data-product-id="<?= htmlspecialchars($id) ?>">
                                <div class="w-24 h-24 overflow-hidden rounded-md mr-4">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium"><?= htmlspecialchars($item['name']) ?></h3>
                                        <span class="font-bold item-total">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                    </div>
                                    <div class="text-gray-500 mb-2">$<?= number_format($item['price'], 2) ?> each</div>
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
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Product Recommendations -->
                <?php if (!empty($cart_items) && !empty($recommendations)): ?>
                <div class="mt-12">
                    <h2 class="text-2xl font-bold mb-6">You Might Also Like</h2>
                    <div class="recommendations-grid">
                        <?php foreach ($recommendations as $product): ?>
                        <div class="recommendation-card bg-white">
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="recommendation-img">
                            <div class="p-4">
                                <h3 class="font-medium text-gray-800 mb-1 recommendation-title">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h3>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="font-bold">$<?= number_format($product['price'], 2) ?></span>
                                    <button class="add-to-cart bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition"
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>"
                                            data-price="<?= $product['price'] ?>"
                                            data-image="<?= htmlspecialchars($product['image']) ?>">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:w-1/3 bg-white p-6 rounded-lg shadow h-fit sticky top-4">
                <h3 class="font-bold text-lg mb-4">Order Summary</h3>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal (<?= count($cart_items) ?> items)</span>
                        <span class="font-medium" id="subtotal">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium">Free</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-medium" id="tax">$<?= number_format($tax, 2) ?></span>
                    </div>
                </div>
                
                <div class="border-t pt-4 mb-6">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span id="total">$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <button id="checkout-btn" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 transition disabled:opacity-50" 
                        <?= empty($cart_items) ? 'disabled' : '' ?>>
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </main>

    <?php include('essentials/footer.php'); ?>

    <script src="scripts/cart.js"></script>
    <script>
        // Add to cart functionality for recommended products
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productData = {
                    id: productId,
                    name: this.getAttribute('data-name'),
                    price: parseFloat(this.getAttribute('data-price')),
                    image: this.getAttribute('data-image'),
                    quantity: 1
                };
                
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                this.disabled = true;
                
                // Send AJAX request to add to cart
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(productData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg';
                        toast.textContent = 'Product added to cart!';
                        document.body.appendChild(toast);
                        
                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                        
                        // Refresh the page to update cart
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert('Error adding product to cart');
                        this.innerHTML = 'Add to Cart';
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = 'Add to Cart';
                    this.disabled = false;
                });
            });
        });
    </script>
</body>
</html>