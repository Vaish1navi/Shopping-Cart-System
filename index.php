<?php
session_start();
require 'database/dbConnect.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart from homepage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    
    try {
        // Get product details
        $stmt = $conn->prepare("SELECT sno, prodname, prodprice, prodimagelink FROM products WHERE sno = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found");
        }
        
        $product = $result->fetch_assoc();
        
        // Add to cart or increment quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['sno'],
                'name' => $product['prodname'],
                'price' => $product['prodprice'],
                'image' => $product['prodimagelink'],
                'quantity' => 1
            ];
        }
        
        $_SESSION['success'] = "Product added to cart successfully!";
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Fetch featured products
$featured_products = [];
$trending_products = [];
$deals_products = [];

try {
    // Get 4 random products for featured section
    $result = $conn->query("SELECT sno, prodname, prodcategory, prodprice, prodimagelink FROM products ORDER BY RAND() LIMIT 4");
    if ($result) {
        $featured_products = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get 4 different random products for trending section
    $result = $conn->query("SELECT sno, prodname, prodcategory, prodprice, prodimagelink FROM products ORDER BY RAND() LIMIT 4");
    if ($result) {
        $trending_products = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get 4 different random products for deals section
    $result = $conn->query("SELECT sno, prodname, prodcategory, prodprice, prodimagelink FROM products ORDER BY RAND() LIMIT 4");
    if ($result) {
        $deals_products = $result->fetch_all(MYSQLI_ASSOC);
    }
    
} catch (Exception $e) {
    // Handle error if needed
}

// Display success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase - Your One-Stop Online Store</title>
    <?php require 'essentials/commonLink.html' ?>
    <style>
        .hero-bg {
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
        }
        .category-card:hover img {
            transform: scale(1.05);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .add-to-cart-btn {
            transition: all 0.3s ease;
        }
        .add-to-cart-btn.added {
            background-color: #10B981 !important;
        }
        .add-to-cart-btn.added::after {
            content: " ✓";
        }
    </style>
</head>
<body class="bg-gray-50">
    
<?php require 'essentials/header.php' ?>

<?php if (!empty($success)): ?>
    <div class="fixed top-20 right-4 z-50">
        <div class="bg-green-500 text-white px-4 py-2 rounded-md shadow-lg flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('.bg-green-500').style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="fixed top-20 right-4 z-50">
        <div class="bg-red-500 text-white px-4 py-2 rounded-md shadow-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('.bg-red-500').style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>

<main>
    <!-- Hero Section -->
    <section class="hero-bg h-96 flex items-center text-white">
        <div class="container mx-auto px-4">
            <div class="max-w-lg">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Summer Collection 2023</h1>
                <p class="text-xl mb-6">Discover the latest trends with our exclusive summer lineup</p>
                <a href="products.php" class="bg-white text-blue-600 font-medium py-3 px-8 rounded-md hover:bg-gray-100 transition inline-block">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 text-center">Shop By Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                // Get distinct categories from products
                $categories = [];
                $result = $conn->query("SELECT DISTINCT prodcategory FROM products LIMIT 4");
                if ($result) {
                    $categories = $result->fetch_all(MYSQLI_ASSOC);
                }
                
                // Default category images if we don't have enough categories
                $default_categories = [
                    ['name' => 'Electronics', 'image' => 'https://images.unsplash.com/photo-1556740738-b6a63e27c4df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8ZWxlY3Ryb25pY3N8ZW58MHx8MHx8fDA%3D&auto=format&fit=crop&w=500&q=60'],
                    ['name' => 'Clothing', 'image' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8ZmFzaGlvbnxlbnwwfHwwfHx8MA%3D%3D&auto=format&fit=crop&w=500&q=60'],
                    ['name' => 'Home Decor', 'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8aG9tZSUyMGRlY29yfGVufDB8fDB8fHww&auto=format&fit=crop&w=500&q=60'],
                    ['name' => 'Footwear', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8c25lYWtlcnN8ZW58MHx8MHx8fDA%3D&auto=format&fit=crop&w=500&q=60']
                ];
                
                // Display categories
                for ($i = 0; $i < 4; $i++) {
                    $category = $categories[$i] ?? ['prodcategory' => $default_categories[$i]['name']];
                    $image = $default_categories[$i]['image'];
                    $category_name = htmlspecialchars($category['prodcategory']);
                    ?>
                    <a href="products.php?category=<?= urlencode($category_name) ?>" class="category-card bg-gray-50 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="h-48 overflow-hidden">
                            <img src="<?= $image ?>" 
                                alt="<?= $category_name ?>" class="w-full h-full object-cover transition duration-300">
                        </div>
                        <div class="p-4 text-center">
                            <h3 class="font-medium"><?= $category_name ?></h3>
                        </div>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold">Featured Products</h2>
                <a href="products.php" class="text-blue-600 hover:underline">View All</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300" data-product-id="<?= $product['sno'] ?>">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($product['prodimagelink']) ?>" 
                                alt="<?= htmlspecialchars($product['prodname']) ?>" class="w-full h-48 object-cover product-image">
                            <?php if (rand(0, 1)): ?>
                                <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">SALE</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-medium mb-1 product-name"><?= htmlspecialchars($product['prodname']) ?></h3>
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">
                                    <?php
                                    $rating = rand(3, 5);
                                    $half_star = rand(0, 1);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i == $rating + 1 && $half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-gray-500 text-sm ml-2">(<?= rand(10, 100) ?>)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lg product-price">₹<?= number_format($product['prodprice'], 2) ?></span>
                                <?php if (rand(0, 1)): ?>
                                    <span class="text-gray-400 text-sm line-through">₹<?= number_format($product['prodprice'] * 1.2, 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <form method="post" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?= $product['sno'] ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <button type="submit" class="mt-3 w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition add-to-cart-btn <?= isset($_SESSION['cart'][$product['sno']]) ? 'added' : '' ?>">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Promo Banner -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl p-8 md:p-12 text-white">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="md:w-1/2 mb-6 md:mb-0">
                        <h2 class="text-3xl md:text-4xl font-bold mb-4">Summer Sale!</h2>
                        <p class="text-lg mb-6">Up to 50% off on selected items. Limited time offer!</p>
                        <a href="products.php" class="bg-white text-blue-600 font-medium py-3 px-8 rounded-md hover:bg-gray-100 transition inline-block">Shop Now</a>
                    </div>
                    <div class="md:w-1/2 flex justify-center">
                        <img src="https://images.unsplash.com/photo-1523381210434-271e8be1f52b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8Y2xvdGhpbmd8ZW58MHx8MHx8fDA%3D&auto=format&fit=crop&w=500&q=60" 
                            alt="Summer Sale" class="h-64 object-cover rounded-lg shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trending Products Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold">Trending Now</h2>
                <a href="products.php" class="text-blue-600 hover:underline">View All</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($trending_products as $product): ?>
                    <div class="product-card bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300" data-product-id="<?= $product['sno'] ?>">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($product['prodimagelink']) ?>" 
                                alt="<?= htmlspecialchars($product['prodname']) ?>" class="w-full h-48 object-cover product-image">
                            <?php if (rand(0, 1)): ?>
                                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">NEW</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-medium mb-1 product-name"><?= htmlspecialchars($product['prodname']) ?></h3>
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">
                                    <?php
                                    $rating = rand(3, 5);
                                    $half_star = rand(0, 1);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i == $rating + 1 && $half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-gray-500 text-sm ml-2">(<?= rand(10, 100) ?>)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lg product-price">₹<?= number_format($product['prodprice'], 2) ?></span>
                            </div>
                            <form method="post" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?= $product['sno'] ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <button type="submit" class="mt-3 w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition add-to-cart-btn <?= isset($_SESSION['cart'][$product['sno']]) ? 'added' : '' ?>">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Deals of the Day Section -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold">Deals of the Day</h2>
                <div class="flex items-center bg-gray-200 px-3 py-1 rounded-full">
                    <i class="fas fa-clock text-blue-600 mr-2"></i>
                    <span class="font-medium">Ends in: <span id="countdown-timer">12:34:56</span></span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($deals_products as $product): ?>
                    <div class="product-card bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300" data-product-id="<?= $product['sno'] ?>">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($product['prodimagelink']) ?>" 
                                alt="<?= htmlspecialchars($product['prodname']) ?>" class="w-full h-48 object-cover product-image">
                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded"><?= rand(20, 50) ?>% OFF</div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-medium mb-1 product-name"><?= htmlspecialchars($product['prodname']) ?></h3>
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">
                                    <?php
                                    $rating = rand(3, 5);
                                    $half_star = rand(0, 1);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i == $rating + 1 && $half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-gray-500 text-sm ml-2">(<?= rand(10, 100) ?>)</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lg product-price">₹<?= number_format($product['prodprice'], 2) ?></span>
                                <span class="text-gray-400 text-sm line-through">₹<?= number_format($product['prodprice'] * 1.3, 2) ?></span>
                            </div>
                            <form method="post" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?= $product['sno'] ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <button type="submit" class="mt-3 w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition add-to-cart-btn <?= isset($_SESSION['cart'][$product['sno']]) ? 'added' : '' ?>">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shipping-fast text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Free Shipping</h3>
                    <p class="text-gray-600">On all orders over ₹500</p>
                </div>
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exchange-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Easy Returns</h3>
                    <p class="text-gray-600">30-day return policy</p>
                </div>
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Secure Payment</h3>
                    <p class="text-gray-600">100% secure checkout</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include('essentials/footer.php'); ?>

<script>
// Countdown timer for deals section
function updateCountdown() {
    const now = new Date();
    const endOfDay = new Date();
    endOfDay.setHours(23, 59, 59, 999);
    
    const diff = endOfDay - now;
    
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('countdown-timer').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

setInterval(updateCountdown, 1000);
updateCountdown();

// AJAX add to cart functionality
document.addEventListener('DOMContentLoaded', function() {
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('.add-to-cart-btn');
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                }
                return response.text();
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.textContent = 'Add to Cart';
            });
        });
    });
    
    // Update cart count in header
    function updateCartCount() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            fetch('cart.php?action=get_count')
                .then(response => response.json())
                .then(data => {
                    cartCount.textContent = data.count || '0';
                });
        }
    }
    
    updateCartCount();
});
</script>
</body>
</html>