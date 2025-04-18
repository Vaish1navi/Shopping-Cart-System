<?php
session_start();
include('database/dbConnect.php'); // Adjust path as needed

// Verify order_id exists
if (!isset($_GET['order_id'])) {
    header("Location: ../index.php");
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
    header("Location: ../index.php");
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

// Calculate subtotal from items
$calculated_subtotal = 0;
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $calculated_subtotal += $item['price'] * $item['quantity'];
    $items[] = $item;
}

// Calculate tax percentage for display
$tax_percentage = $order['subtotal'] > 0 ? round(($order['tax'] / $order['subtotal']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> Details - ShopEase</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .order-item {
            transition: all 0.3s ease;
        }
        .order-summary {
            background-color: #f8fafc;
            border-radius: 0.5rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-processing {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-shipped {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php require 'essentials/header.php' ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Order #<?= $order_id ?></h1>
                <span class="status-badge status-<?= $order['status'] ?>">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h2 class="text-lg font-semibold mb-2">Order Information</h2>
                            <div class="space-y-1 text-gray-700">
                                <p><span class="font-medium">Order Number:</span> #<?= $order['order_id'] ?></p>
                                <p><span class="font-medium">Date:</span> <?= date('F j, Y \a\t g:i a', strtotime($order['order_date'])) ?></p>
                                <p><span class="font-medium">Payment Method:</span> 
                                    <?= strtoupper($order['payment_method']) ?>
                                    <?= $order['payment_method'] === 'cod' ? '(Cash on Delivery)' : '' ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold mb-2">Customer Details</h2>
                            <div class="space-y-1 text-gray-700">
                                <p><span class="font-medium">Name:</span> <?= htmlspecialchars($order['full_name']) ?></p>
                                <p><span class="font-medium">Email:</span> <?= htmlspecialchars($order['email']) ?></p>
                                <p><span class="font-medium">Phone:</span> <?= htmlspecialchars($order['phone']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold mb-2">Shipping Address</h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><?= htmlspecialchars($order['address']) ?></p>
                            <p><?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['pincode']) ?></p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                    <div class="space-y-4 mb-8">
                        <?php if (empty($items)): ?>
                            <p class="text-gray-500">No items found in this order.</p>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <div class="order-item flex border-b pb-4">
                                    <div class="w-20 h-20 overflow-hidden rounded-md mr-4 flex-shrink-0">
                                        <img src="<?= htmlspecialchars($item['image'] ?? '../images/placeholder-product.jpg') ?>" 
                                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between">
                                            <h3 class="font-medium">
                                                <a href="../product.php?id=<?= $item['product_id'] ?>" class="hover:text-blue-600">
                                                    <?= htmlspecialchars($item['product_name']) ?>
                                                </a>
                                            </h3>
                                            <span class="font-bold">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                        </div>
                                        <div class="text-gray-500 mb-1">₹<?= number_format($item['price'], 2) ?> each</div>
                                        <div class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary p-6 rounded-lg">
                        <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal (<?= count($items) ?> items)</span>
                                <span class="font-medium">₹<?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (<?= $tax_percentage ?>%)</span>
                                <span class="font-medium">₹<?= number_format($order['tax'], 2) ?></span>
                            </div>
                            <?php if ($order['payment_method'] === 'cod'): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cash on Delivery</span>
                                <span class="font-medium">+ ₹<?= number_format($order['total'] - $order['subtotal'] - $order['tax'], 2) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total Amount</span>
                                <span>₹<?= number_format($order['total'], 2) ?></span>
                            </div>
                            <?php if ($order['payment_method'] === 'cod'): ?>
                            <p class="text-sm text-gray-500 mt-1">To be paid when product is delivered</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Status Updates -->
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold mb-4">Order Status Updates</h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium">Current Status: <?= ucfirst($order['status']) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            Your order has been received and is being processed.
                                        <?php elseif ($order['status'] === 'processing'): ?>
                                            We're preparing your order for shipment.
                                        <?php elseif ($order['status'] === 'shipped'): ?>
                                            Your order has been shipped.
                                        <?php elseif ($order['status'] === 'completed'): ?>
                                            Your order has been delivered successfully.
                                        <?php elseif ($order['status'] === 'cancelled'): ?>
                                            This order has been cancelled.
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        Last updated: <?= date('F j, Y \a\t g:i a', strtotime($order['order_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="../index.php" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition text-center">
                            Continue Shopping
                        </a>
                        <a href="../order_history.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-50 transition text-center">
                            View All Orders
                        </a>
                        <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-50 transition text-center">
                            <i class="fas fa-print mr-2"></i> Print Receipt
                        </button>
                        <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                        <form action="../cancel_order.php" method="POST" class="sm:ml-auto">
                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                            <button type="submit" class="bg-white border border-red-300 text-red-700 px-6 py-2 rounded-md hover:bg-red-50 transition text-center w-full sm:w-auto">
                                <i class="fas fa-times-circle mr-2"></i> Cancel Order
                            </button>
                        </form>
                        <?php endif; ?>
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