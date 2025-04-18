<?php
session_start();
include('database/dbConnect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all orders for the current user, sorted by most recent
$orders_query = "SELECT * FROM orders 
                 WHERE user_id = $user_id 
                 ORDER BY order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - ShopEase</title>
    <?php require 'essentials/commonLink.html' ?>
    <style>
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
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
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-delivered {
            background-color: #dcfce7;
            color: #166534;
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
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Order History</h1>
                <a href="index.php" class="text-blue-600 hover:text-blue-800">Continue Shopping</a>
            </div>

            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <div class="space-y-6">
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="order-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4">
                                    <div class="mb-4 md:mb-0">
                                        <h2 class="text-lg font-semibold">
                                            Order #<?= $order['order_id'] ?>
                                            <span class="status-badge status-<?= $order['status'] ?> ml-2">
                                                <?= $order['status'] ?>
                                            </span>
                                        </h2>
                                        <p class="text-gray-500 text-sm">
                                            Placed on <?= date('F j, Y \a\t g:i a', strtotime($order['order_date'])) ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold">$<?= number_format($order['total'], 2) ?></p>
                                        <a href="order_details.php?order_id=<?= $order['order_id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>

                                <!-- Order Items Preview -->
                                <?php
                                $items_query = "SELECT oi.*, p.prodimagelink as image 
                                               FROM order_items oi
                                               LEFT JOIN products p ON oi.product_id = p.sno
                                               WHERE oi.order_id = {$order['order_id']}
                                               LIMIT 3";
                                $items_result = mysqli_query($conn, $items_query);
                                ?>
                                <div class="border-t pt-4">
                                    <div class="flex flex-wrap -mx-2">
                                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                            <div class="w-1/3 sm:w-1/4 md:w-1/5 px-2 mb-4">
                                                <div class="relative">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                         class="w-full h-24 object-cover rounded">
                                                    <div class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                        <?= $item['quantity'] ?>
                                                    </div>
                                                </div>
                                                <p class="text-sm mt-1 truncate"><?= htmlspecialchars($item['product_name']) ?></p>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>

                                    <?php 
                                    $total_items_query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = {$order['order_id']}";
                                    $total_items_result = mysqli_query($conn, $total_items_query);
                                    $total_items = mysqli_fetch_assoc($total_items_result)['count'];
                                    ?>
                                    <?php if ($total_items > 3): ?>
                                        <div class="text-center mt-2">
                                            <p class="text-sm text-gray-500">
                                                +<?= $total_items - 3 ?> more item<?= ($total_items - 3) > 1 ? 's' : '' ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No orders yet</h3>
                    <p class="mt-1 text-gray-500">You haven't placed any orders with us yet.</p>
                    <div class="mt-6">
                        <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Start Shopping
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include('essentials/footer.php'); ?>
</body>
</html>
<?php mysqli_close($conn); ?>