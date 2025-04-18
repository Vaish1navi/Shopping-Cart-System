<?php
// Include your database connection and header
include('database/dbConnect.php');
include 'essentials/header.php';
include 'essentials/commonLink.html';

// Get the search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($query)) {
    $search_query = "%$query%";

    $stmt = $conn->prepare("SELECT * FROM products 
                            WHERE prodname LIKE ? 
                            AND status = 'active'");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $products = [];
}
?>

<style>
    .product-card {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .product-image {
        height: 200px;
        object-fit: contain;
        padding: 20px;
        background: #f8fafc;
        transition: transform 0.3s ease;
    }
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    .discount-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #ef4444;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .add-to-cart-btn {
        transition: all 0.3s ease;
        background-color: #3b82f6;
        color: white;
        border-radius: 4px;
        padding: 8px 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .add-to-cart-btn:hover {
        background-color: #2563eb;
        transform: translateY(-2px);
    }
    .price {
        font-weight: bold;
        color: #10b981;
        font-size: 1.1rem;
    }
    .original-price {
        text-decoration: line-through;
        color: #9ca3af;
        font-size: 0.9rem;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Search Results for "<?= htmlspecialchars($query) ?>"</h1>
    
    <?php if (empty($query)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6 rounded">
            <p>Please enter a search term.</p>
        </div>
    <?php elseif (empty($products)): ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 p-4 mb-6 rounded">
            <p>No products found matching your search.</p>
            <a href="products.php" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                Browse all products &rarr;
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): 
                // Calculate discount if available (assuming you might add this later)
                $has_discount = false;
                $original_price = $product['prodprice'];
                $discounted_price = $product['prodprice'];
                if ($has_discount) {
                    $discounted_price = $original_price * 0.9; // 10% discount example
                }
            ?>
                <div class="product-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                    <?php if ($has_discount): ?>
                        <div class="discount-badge">10% OFF</div>
                    <?php endif; ?>
                    
                    <a href="product.php?id=<?= $product['sno'] ?>">
                        <img src="<?= htmlspecialchars($product['prodimagelink']) ?>" 
                             alt="<?= htmlspecialchars($product['prodname']) ?>" 
                             class="product-image w-full">
                    </a>
                    
                    <div class="p-4">
                        <a href="product.php?id=<?= $product['sno'] ?>">
                            <h3 class="font-semibold text-lg mb-2 hover:text-blue-600 transition">
                                <?= htmlspecialchars($product['prodname']) ?>
                            </h3>
                        </a>
                        
                        <div class="flex items-center mb-3">
                            <div class="rating-stars text-yellow-400">
                                <?php 
                                $rating = 4; // Assuming a default rating
                                echo str_repeat('<i class="fas fa-star"></i>', floor($rating));
                                if ($rating - floor($rating) >= 0.5) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                }
                                ?>
                            </div>
                            <span class="text-gray-600 text-sm ml-2">(<?= rand(10, 100) ?>)</span>
                        </div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="price">₹<?= number_format($discounted_price, 2) ?></span>
                                <?php if ($has_discount): ?>
                                    <span class="original-price ml-2">₹<?= number_format($original_price, 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <form method="post" action="add_to_cart.php" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?= $product['sno'] ?>">
                            <button type="submit" class="add-to-cart-btn w-full">
                                <i class="fas fa-cart-plus"></i>
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart animation
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            // Simulate AJAX request (replace with actual fetch request)
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 1500);
            }, 800);
            
            // Here you would normally do an actual AJAX request
            // fetch('add_to_cart.php', {
            //     method: 'POST',
            //     body: new FormData(this)
            // }).then(response => {
            //     // Handle response
            // });
        });
    });
});
</script>

<?php include 'essentials/footer.php'; ?>