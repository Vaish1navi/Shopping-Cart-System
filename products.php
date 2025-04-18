<?php
session_start();
require 'database/dbConnect.php';

// Handle category input securely
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Miscellaneous';

// Define valid categories to prevent SQL injection
$validCategories = ['Clothing', 'Electronics', 'Footwear', 'Home Decor', 'Miscellaneous'];
if (!in_array($category, $validCategories)) {
    $category = 'Miscellaneous';
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add product to cart or increment quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => 1,
            'product_id' => $product_id
        ];
    }
    
    $_SESSION['cart_success'] = true;
    header('Location: ' . $_SERVER['PHP_SELF'] . '?category=' . urlencode($category));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category); ?> Products | ShopEase</title>
    <?php require 'essentials/commonLink.html' ?>
    <style>
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .category-item {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .category-item:hover {
            background-color: #f3f4f6;
            transform: scale(1.02);
        }
        .category-item.active {
            background-color: #e5e7eb;
            font-weight: bold;
        }
        .rating-stars {
            color: #f59e0b;
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ef4444;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .deal-timer {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body class="bg-gray-50">
<div id="overlay" class="fixed inset-0 bg-black/60 bg-opacity-50 z-[60] hidden"></div>

<?php require 'essentials/header.php' ?>

<div class="grid grid-cols-1 md:grid-cols-[1fr_4fr] gap-4 p-4">
    <!-- Sidebar -->
    <div class="md:sticky md:top-20 md:h-[calc(100vh-6rem)]">
        <div class="h-full p-4 flex flex-col gap-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <h3 class="font-bold text-center text-2xl text-indigo-700">Products Category</h3>
            <div class="p-2 bg-indigo-100 text-indigo-800 rounded-lg text-center font-medium">
                <?php echo htmlspecialchars($category); ?>
            </div>
            
            <div>
                <h3 class="font-bold mb-2 text-lg flex items-center gap-2">
                    <i class="fas fa-list"></i>
                    <span>Categories</span>
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($validCategories as $cat): ?>
                        <li class="category-item rounded p-2 <?php echo $cat === $category ? 'active' : '' ?>"
                            onclick="window.location.href='?category=<?php echo urlencode($cat) ?>'">
                            <i class="fas fa-chevron-right mr-2 text-xs text-gray-500"></i>
                            <?php echo htmlspecialchars($cat); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div>
        <?php if (isset($_SESSION['cart_success'])): ?>
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                Product added to cart successfully!
            </div>
            <?php unset($_SESSION['cart_success']); ?>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-lg shadow-sm mb-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-tag mr-2 text-indigo-600"></i>
                <?php echo htmlspecialchars($category); ?> Products
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Sort by:</span>
                <select class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option>Popularity</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                    <option>Newest First</option>
                    <option>Customer Rating</option>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            } else {
                $stmt = $conn->prepare("SELECT * FROM products WHERE prodcategory = ?");
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $prodprice = $row['prodprice'];
                        $discount = 0; // Assuming no discount column in your schema
                        $original_price = $prodprice;
                        $rating = number_format(rand(30, 50) / 10, 1);
                        $ratingCount = rand(50, 999);
                        $randdays = rand(1, 7);
                        $deliverydate = date('l d M', strtotime("+$randdays days"));
                        $dealends = floor(rand(1, 4));
                        
                        echo "
                        <div class='product-card border rounded-lg p-4 flex flex-col gap-3 bg-white relative hover:shadow-md'>
                            " . ($discount > 0 ? "<div class='discount-badge'>{$discount}% OFF</div>" : "") . "
                            <div class='flex items-center justify-center h-48'>
                                <img src='{$row['prodimagelink']}' alt='{$row['prodname']}' class='rounded-md max-h-full max-w-full object-contain'>
                            </div>
                            <h3 class='font-semibold text-gray-800 hover:text-indigo-600 line-clamp-2' title='{$row['prodname']}'>{$row['prodname']}</h3>
                            
                            <div class='flex items-center gap-1 text-sm'>
                                <div class='rating-stars'>
                                    " . str_repeat('<i class="fas fa-star"></i>', floor($rating)) . 
                                    ($rating - floor($rating) >= 0.5 ? '<i class="fas fa-star-half-alt"></i>' : '') . "
                                </div>
                                <span class='text-gray-600'>({$ratingCount})</span>
                            </div>
                            
                            <div class='mt-1'>
                                <span class='deal-timer inline-flex items-center gap-1'>
                                    <i class='fas fa-clock'></i>
                                    <span>Deal ends in {$dealends} day" . ($dealends > 1 ? 's' : '') . "</span>
                                </span>
                            </div>
                            
                            <div class='flex items-baseline gap-2 mt-2'>                    
                                <span class='text-lg font-bold text-green-700'>₹" . number_format($prodprice) . "</span>
                                " . ($discount > 0 ? "<span class='line-through text-gray-400 text-sm'>₹" . number_format($original_price) . "</span>" : "") . "
                            </div>
                            
                            <p class='text-xs text-gray-500 flex items-center gap-1'>
                                <i class='fas fa-truck'></i>
                                <span>FREE delivery by {$deliverydate}</span>
                            </p>
                            
                            <form method='POST' class='mt-2'>
                                <input type='hidden' name='product_id' value='{$row['sno']}'>
                                <button type='submit' class='w-full bg-blue-400 hover:bg-blue-500 text-black px-3 py-2 text-sm rounded-md font-medium flex items-center justify-center gap-2 transition-colors'>
                                    <i class='fas fa-cart-plus'></i>
                                    <span>Add to cart</span>
                                </button>
                            </form>
                        </div>";
                    }
                } else {
                    echo "<div class='col-span-full text-center py-10'>
                            <i class='fas fa-box-open text-4xl text-gray-400 mb-3'></i>
                            <h3 class='text-xl font-medium text-gray-600'>No products found in this category</h3>
                            <p class='text-gray-500 mt-2'>Check back later or browse other categories</p>
                        </div>";
                }
                $stmt->close();
            }
            $conn->close();
            ?>
        </div>
    </div>
</div>

<?php require 'essentials/footer.php' ?>

<script>
    // Add to cart animation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        });
    });
</script>

</body>
</html>