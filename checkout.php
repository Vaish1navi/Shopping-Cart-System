<?php
session_start();
include('database/dbConnect.php'); // Database connection

// Verify cart data exists
if (!isset($_POST['cart_data'])) {
    header("Location: cart.php");
    exit();
}

// Decode cart data
$cart_items = json_decode($_POST['cart_data'], true);
$subtotal = floatval($_POST['subtotal']);
$tax = floatval($_POST['tax']);
$total = floatval($_POST['total']);

// Process order if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $required = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'payment_method'];
    $errors = [];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = "This field is required";
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    // Validate phone number
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = "Invalid phone number";
    }

    if (empty($errors)) {
        $user_id = $_SESSION['user_id'] ?? null;
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $state = mysqli_real_escape_string($conn, $_POST['state']);
        $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            $order_query = "INSERT INTO orders (user_id, full_name, email, phone, address, city, state, pincode,
                            payment_method, subtotal, tax, total, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

            $stmt = mysqli_prepare($conn, $order_query);
            mysqli_stmt_bind_param($stmt, "issssssssddd", $user_id, $full_name, $email, $phone, $address,
                                 $city, $state, $pincode, $payment_method, $subtotal, $tax, $total);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Order creation failed: " . mysqli_error($conn));
            }

            $order_id = mysqli_insert_id($conn);

            $item_query = "INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                          VALUES (?, ?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);

            foreach ($cart_items as $product_id => $item) {
                mysqli_stmt_bind_param($item_stmt, "iisdi", $order_id, $product_id,
                                     $item['name'], $item['price'], $item['quantity']);
                if (!mysqli_stmt_execute($item_stmt)) {
                    throw new Exception("Order item insertion failed: " . mysqli_error($conn));
                }
            }

            // Clear cart if everything succeeded
            unset($_SESSION['cart']);

            // Commit transaction
            mysqli_commit($conn);

            header("Location: order_confirmation.php?order_id=$order_id");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to place order. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ShopEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#1e40af',
                    }
                }
            }
        }
    </script>
    <style>
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .payment-method {
            transition: all 0.2s ease;
        }
        .payment-method.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include('essentials/header.php'); ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">Checkout</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Shipping Information -->
                <div class="lg:w-2/3 bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-6 pb-2 border-b">Shipping Information</h2>
                    
                    <form id="checkout-form" method="POST">
                        <input type="hidden" name="cart_data" value="<?php echo htmlspecialchars(json_encode($cart_items)); ?>">
                        <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                        <input type="hidden" name="tax" value="<?php echo $tax; ?>">
                        <input type="hidden" name="total" value="<?php echo $total; ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="full_name" name="full_name" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                <?php if (isset($errors['full_name'])): ?>
                                    <p class="error-message"><?php echo $errors['full_name']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" name="email" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <p class="error-message"><?php echo $errors['email']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <p class="error-message"><?php echo $errors['phone']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="pincode" class="block text-sm font-medium text-gray-700 mb-1">Postal/Zip Code</label>
                                <input type="text" id="pincode" name="pincode" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>">
                                <?php if (isset($errors['pincode'])): ?>
                                    <p class="error-message"><?php echo $errors['pincode']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <textarea id="address" name="address" rows="2" 
                                      class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <p class="error-message"><?php echo $errors['address']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" id="city" name="city" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                <?php if (isset($errors['city'])): ?>
                                    <p class="error-message"><?php echo $errors['city']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
                                <input type="text" id="state" name="state" 
                                       class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary"
                                       value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                <?php if (isset($errors['state'])): ?>
                                    <p class="error-message"><?php echo $errors['state']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        
                        <h2 class="text-xl font-semibold mb-6 pb-2 border-b">Payment Method</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <input type="radio" id="cod" name="payment_method" value="cod" class="hidden" 
                                       <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cod') ? 'checked' : 'checked'; ?>>
                                <label for="cod" class="payment-method block p-4 border rounded-md cursor-pointer">
                                    <div class="flex items-center">
                                        <i class="fas fa-money-bill-wave text-2xl text-green-600 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium">Cash on Delivery</h3>
                                            <p class="text-sm text-gray-500">Pay when you receive your order</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div>
                                <input type="radio" id="card" name="payment_method" value="card" class="hidden"
                                       <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'card') ? 'checked' : ''; ?>>
                                <label for="card" class="payment-method block p-4 border rounded-md cursor-pointer">
                                    <div class="flex items-center">
                                        <i class="far fa-credit-card text-2xl text-blue-600 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium">Credit/Debit Card</h3>
                                            <p class="text-sm text-gray-500">Pay securely with your card</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($errors['payment_method'])): ?>
                            <p class="error-message mb-4"><?php echo $errors['payment_method']; ?></p>
                        <?php endif; ?>
                        
                        <!-- Card Details (shown when card payment selected) -->
                        <div id="card-details" class="bg-gray-50 p-4 rounded-md mb-6 hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456"
                                           class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="card_name" class="block text-sm font-medium text-gray-700 mb-1">Name on Card</label>
                                    <input type="text" id="card_name" name="card_name" placeholder="John Doe"
                                           class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY"
                                           class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123"
                                           class="w-full px-4 py-2 border rounded-md focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-8">
                            <button type="submit" name="place_order" 
                                    class="bg-primary hover:bg-secondary text-white px-6 py-3 rounded-md font-medium transition">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:w-1/3 bg-white p-6 rounded-lg shadow h-fit sticky top-4">
                    <h2 class="text-xl font-semibold mb-6 pb-2 border-b">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6">
                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 overflow-hidden rounded-md mr-3">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <h3 class="font-medium"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-sm text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                                <span class="font-medium">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium">Free</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-medium">$<?php echo number_format($tax, 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mb-6">
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-md">
                        <h3 class="font-medium text-blue-800 mb-2"><i class="fas fa-shield-alt mr-2"></i>Secure Checkout</h3>
                        <p class="text-sm text-blue-600">Your information is protected by 256-bit SSL encryption</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include('essentials/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Show/hide card details
                    const cardDetails = document.getElementById('card-details');
                    if (this.getAttribute('for') === 'card') {
                        cardDetails.classList.remove('hidden');
                    } else {
                        cardDetails.classList.add('hidden');
                    }
                });
            });

            // Initialize selected payment method
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (selectedMethod) {
                document.querySelector(`label[for="${selectedMethod.id}"]`).classList.add('selected');
                if (selectedMethod.value === 'card') {
                    document.getElementById('card-details').classList.remove('hidden');
                }
            }

            // Form validation
            const form = document.getElementById('checkout-form');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate required fields
                const requiredFields = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'pincode'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        isValid = false;
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                            const error = document.createElement('p');
                            error.className = 'error-message';
                            error.textContent = 'This field is required';
                            field.parentNode.insertBefore(error, field.nextSibling);
                        }
                    }
                });

                // Validate email format
                const email = document.getElementById('email');
                if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                    isValid = false;
                    if (!email.nextElementSibling || !email.nextElementSibling.classList.contains('error-message')) {
                        const error = document.createElement('p');
                        error.className = 'error-message';
                        error.textContent = 'Invalid email format';
                        email.parentNode.insertBefore(error, email.nextSibling);
                    }
                }

                // Validate phone number
                const phone = document.getElementById('phone');
                if (phone.value && !/^[0-9]{10,15}$/.test(phone.value)) {
                    isValid = false;
                    if (!phone.nextElementSibling || !phone.nextElementSibling.classList.contains('error-message')) {
                        const error = document.createElement('p');
                        error.className = 'error-message';
                        error.textContent = 'Invalid phone number';
                        phone.parentNode.insertBefore(error, phone.nextSibling);
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Format card number
            const cardNumber = document.getElementById('card_number');
            if (cardNumber) {
                cardNumber.addEventListener('input', function(e) {
                    let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formatted = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) formatted += ' ';
                        formatted += value[i];
                    }
                    this.value = formatted;
                });
            }

            // Format expiry date
            const expiryDate = document.getElementById('expiry_date');
            if (expiryDate) {
                expiryDate.addEventListener('input', function(e) {
                    let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    this.value = value;
                });
            }
        });
    </script>
</body>
</html>