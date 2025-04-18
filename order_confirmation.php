<?php
session_start();
include('database/dbConnect.php'); // Database connection

// Verify order_id exists
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'] ?? null;

// Get order details using prepared statement
$order_query = "SELECT * FROM orders WHERE order_id = ?" . 
               ($user_id ? " AND user_id = ?" : "");
$stmt = $conn->prepare($order_query);

if ($user_id) {
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
    $stmt->bind_param("i", $order_id);
}

$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

// Verify order exists and belongs to user (if logged in)
if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.prodimagelink as image, p.sno as product_id 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.sno
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Calculate subtotal from items to verify against order record
$calculated_subtotal = 0;
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $calculated_subtotal += $item['price'] * $item['quantity'];
    $items[] = $item;
}

// Verify calculations match
$discrepancy = false;
if (abs($calculated_subtotal - $order['subtotal']) > 0.01) {
    $discrepancy = true;
    error_log("Order #$order_id subtotal discrepancy: stored {$order['subtotal']} vs calculated $calculated_subtotal");
}

// Calculate tax percentage for display
$tax_percentage = $order['subtotal'] > 0 ? round(($order['tax'] / $order['subtotal']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ShopEase</title>
    <?php require 'essentials/commonLink.html' ?>
    <style>
        .order-item {
            transition: all 0.3s ease;
        }
        .order-summary {
            background-color: #f8fafc;
            border-radius: 0.5rem;
        }
        .discrepancy-warning {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php require 'essentials/header.php' ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- <?php if ($discrepancy): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Notice</p>
                <p>We detected a discrepancy in your order totals. Our team has been notified and will contact you if any action is needed.</p>
            </div>
            <?php endif; ?> -->

            <div class="bg-white rounded-lg shadow-md overflow-hidden <?= $discrepancy ? 'discrepancy-warning' : '' ?>">
                <!-- Order Confirmation Header -->
                <div class="bg-green-100 px-6 py-4 border-b border-green-200">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <h1 class="text-2xl font-bold text-green-800">Order Confirmed!</h1>
                            <p class="text-green-600">Thank you for your purchase, <?= htmlspecialchars($order['full_name']) ?>!</p>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h2 class="text-lg font-semibold mb-2">Order Information</h2>
                            <div class="space-y-1 text-gray-700">
                                <p><span class="font-medium">Order Number:</span> #<?= $order['order_id'] ?></p>
                                <p><span class="font-medium">Date:</span> <?= date('F j, Y \a\t g:i a', strtotime($order['order_date'])) ?></p>
                                <p><span class="font-medium">Status:</span> <span class="capitalize"><?= $order['status'] ?></span></p>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold mb-2">Payment Method</h2>
                            <div class="flex items-center">
                                <?php if ($order['payment_method'] === 'card'): ?>
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/visa/visa-original.svg" alt="Card" class="h-6 mr-2">
                                    <span>Credit/Debit Card</span>
                                <?php elseif ($order['payment_method'] === 'upi'): ?>
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg" alt="UPI" class="h-6 mr-2">
                                    <span>UPI Payment</span>
                                <?php else: ?>
                                    <i class="fa-solid fa-sack-dollar"></i>
                                    <span> &nbsp;Cash on Delivery</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold mb-2">Shipping Address</h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="font-medium"><?= htmlspecialchars($order['full_name']) ?></p>
                            <p><?= htmlspecialchars($order['address']) ?></p>
                            <p><?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['pincode']) ?></p>
                            <p class="mt-2">Phone: <?= htmlspecialchars($order['phone']) ?></p>
                            <p>Email: <?= htmlspecialchars($order['email']) ?></p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                    <div class="space-y-4 mb-8">
                        <?php foreach ($items as $item): ?>
                            <div class="order-item flex border-b pb-4">
                                <div class="w-20 h-20 overflow-hidden rounded-md mr-4 flex-shrink-0">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium">
                                            <a href="product.php?id=<?= $item['product_id'] ?>" class="hover:text-blue-600">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </a>
                                        </h3>
                                        <span class="font-bold">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                    </div>
                                    <div class="text-gray-500 mb-1">$<?= number_format($item['price'], 2) ?> each</div>
                                    <div class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary p-6 rounded-lg">
                        <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal (<?= count($items) ?> items)</span>
                                <span class="font-medium">$<?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">
                                    Free
                                </span>
                            </div>
                            <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span class="font-medium">-$<?= number_format($order['discount'], 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (<?= $tax_percentage ?>%)</span>
                                <span class="font-medium">$<?= number_format($order['tax'], 2) ?></span>
                            </div>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total</span>
                                <span>$<?= number_format($order['total'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="index.php" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition text-center">
                            Continue Shopping
                        </a>
                        <a href="order_history.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-50 transition text-center">
                            View Order History
                        </a>
                        <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-50 transition text-center">
                            Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include('essentials/footer.php'); ?>
</body>
</html>
<?php 
$conn->close();
?>