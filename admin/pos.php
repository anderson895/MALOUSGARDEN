<?php require_once('header.php'); ?>

<?php
// Initialize variables
$error_message = '';
$success_message = '';
$products = array();
$categories = array();
$cart_items = array();
$total_amount = 0;

// Database connection using PDO (assumed to exist in header.php)
// Fetch product categories
$statement = $pdo->prepare("SELECT * FROM tbl_category ORDER BY tcat_name ASC");
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch products
if(isset($_GET['category']) && !empty($_GET['category'])) {
    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE tcat_id=? AND p_is_active=1 ORDER BY p_name ASC");
    $statement->execute(array($_GET['category']));
} else {
    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=1 ORDER BY p_name ASC");
    $statement->execute();
}
$products = $statement->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX quantity update
// Handle AJAX quantity update
if(isset($_POST['action']) && $_POST['action'] == 'update_quantity') {
    $index = $_POST['index'];
    $new_quantity = $_POST['quantity'];

    if(isset($_SESSION['cart_items'][$index])) {
        $_SESSION['cart_items'][$index]['quantity'] = $new_quantity;

        // Calculate new total for the item
        $item_total = $_SESSION['cart_items'][$index]['price'] * $new_quantity;

        echo json_encode([
            'success' => true,
            'item_total' => number_format($item_total, 2),
            'message' => 'Quantity updated'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
}
// Handle cart operations
if(isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Get product details
    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
    $statement->execute(array($product_id));
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    if($product) {
        // Check if item already exists in cart
        $cart_key = array_search($product_id, array_column(isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : array(), 'id'));


        if($cart_key !== false) {
            // Update quantity
            $_SESSION['cart_items'][$cart_key]['quantity'] += $quantity;
        } else {
            // Add new item
            $_SESSION['cart_items'][] = array(
                'id' => $product_id,
                'name' => $product['p_name'],
                'price' => $product['p_current_price'],
                'quantity' => $quantity,
                'image' => $product['p_featured_photo']
            );
        }

        $success_message = 'Product added to cart successfully.';
    }
}

// Remove item from cart
if(isset($_GET['remove_item'])) {
    $item_index = $_GET['remove_item'];
    if(isset($_SESSION['cart_items'][$item_index])) {
        unset($_SESSION['cart_items'][$item_index]);
        $_SESSION['cart_items'] = array_values($_SESSION['cart_items']); // Reindex array
        $success_message = 'Item removed from cart.';
    }
}

// Clear cart
if(isset($_GET['clear_cart'])) {
    $_SESSION['cart_items'] = array();
    $success_message = 'Cart has been cleared.';
}

// Process checkout
if(isset($_POST['checkout'])) {
    if(!isset($_SESSION['cart_items']) || count($_SESSION['cart_items']) == 0) {
        $error_message = 'Your cart is empty. Please add items to proceed.';
    } else {
        // Generate unique transaction ID
        $payment_id = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);

        // Get customer info
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : 0;
        $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : 'Walk-in Customer';
        $customer_email = isset($_POST['customer_email']) ? $_POST['customer_email'] : '';
        $payment_method = $_POST['payment_method'];

        // Calculate total
        $total_amount = 0;
        foreach($_SESSION['cart_items'] as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }

        // Insert transaction into payment table for each product
        foreach($_SESSION['cart_items'] as $item) {
            $statement = $pdo->prepare("INSERT INTO tbl_payment (customer_id, customer_name, customer_email, payment_date, paid_amount, payment_method, payment_status, shipping_status, payment_id, product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $statement->execute(array(
                $customer_id,
                $customer_name,
                $customer_email,
                date('Y-m-d H:i:s'),
                $item['price'] * $item['quantity'],
                $payment_method,
                'Completed',
                'Pending',
                $payment_id,
                $item['id']
            ));

            // Save order details to tbl_order
            $statement = $pdo->prepare("INSERT INTO tbl_order (product_id, product_name, quantity, unit_price, payment_id) VALUES (?, ?, ?, ?, ?)");
            $statement->execute(array(
                $item['id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $payment_id
            ));

            // Update inventory
            $statement = $pdo->prepare("UPDATE tbl_product SET p_qty = p_qty - ? WHERE p_id = ?");
            $statement->execute(array($item['quantity'], $item['id']));
        }

        // Clear cart after successful checkout
        $_SESSION['cart_items'] = array();

        $success_message = 'Transaction completed successfully! Transaction ID: ' . $payment_id;
    }
}

// Load cart items from session
$cart_items = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : array();

// Calculate cart total
foreach($cart_items as $item) {
    $total_amount += ($item['price'] * $item['quantity']);
}

// Display error/success messages
if(!empty($error_message)) {
    echo "<script>alert('" . $error_message . "');</script>";
}
if(!empty($success_message)) {
    echo "<script>alert('" . $success_message . "');</script>";
}
?>

    <!-- Content Header -->
    <section class="content-header">
        <div class="content-header-left">
            <h1><i class="fa fa-shopping-cart"></i> Point of Sale (POS) System</h1>
        </div>
        <div class="content-header-right">
            <a href="index.php" class="btn btn-primary btn-sm">Dashboard</a>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <!-- Products Section (Left Side) -->
            <div class="col-md-8">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Products</h3>
                        <div class="box-tools">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <!--                                <input type="text" id="product-search" class="form-control pull-right" placeholder="Search products...">-->
                                <!--                                <div class="input-group-btn">-->
                                <!--                                    <button type="button" class="btn btn-default"><i class="fa fa-search"></i></button>-->
                                <!--                                </div>-->
                            </div>
                        </div>
                    </div>

                    <!-- Category Filters -->
                    <div class="box-body" style="padding-bottom: 0;">
                        <div class="category-filters">
                            <a href="POS.php" class="btn btn-default <?php if(!isset($_GET['category'])) echo 'active'; ?>">All</a>
                            <?php foreach($categories as $category): ?>
                                <a href="POS.php?category=<?php echo $category['tcat_id']; ?>"
                                   class="btn btn-default <?php if(isset($_GET['category']) && $_GET['category'] == $category['tcat_id']) echo 'active'; ?>">
                                    <?php echo $category['tcat_name']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="box-body">
                        <div class="row product-grid">
                            <?php if(count($products) > 0): ?>
                                <?php foreach($products as $product): ?>
                                    <div class="col-md-3 col-sm-4 col-xs-6 product-item" data-name="<?php echo strtolower($product['p_name']); ?>">
                                        <div class="product-box" onclick="quickAdd(<?php echo $product['p_id']; ?>, '<?php echo $product['p_name']; ?>', <?php echo $product['p_current_price']; ?>)">
                                            <div class="product-image">
                                                <?php if(!empty($product['p_featured_photo'])): ?>
                                                    <img src="../assets/uploads/<?php echo $product['p_featured_photo']; ?>" alt="<?php echo $product['p_name']; ?>">
                                                <?php else: ?>
                                                    <img src="../assets/img/no-photo.jpg" alt="No Photo">
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-info">
                                                <h4><?php echo $product['p_name']; ?></h4>
                                                <div class="product-price">₱<?php echo number_format($product['p_current_price'], 2); ?></div>
                                                <div class="product-stock <?php echo ($product['p_qty'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                                    <?php echo ($product['p_qty'] > 0) ? 'In Stock (' . $product['p_qty'] . ')' : 'Out of Stock'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-md-12">
                                    <div class="alert alert-info">No products found in this category.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Section (Right Side) -->
            <div class="col-md-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-shopping-basket"></i> Current Cart</h3>
                        <?php if(count($cart_items) > 0): ?>
                            <div class="box-tools pull-right">
                                <a href="POS.php?clear_cart=1" class="btn btn-box-tool text-danger" onclick="return confirm('Are you sure you want to clear the cart?');">
                                    <i class="fa fa-trash"></i> Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="box-body cart-items-container">
                        <?php if(count($cart_items) > 0): ?>
                            <table class="table table-striped cart-table">
                                <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($cart_items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <div class="input-group input-group-sm quantity-control">
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default btn-flat qty-btn" data-action="decrease" data-index="<?php echo $index; ?>">-</button>
                                            </span>
                                                <input type="text" class="form-control text-center qty-input" value="<?php echo $item['quantity']; ?>" data-index="<?php echo $index; ?>">
                                                <span class="input-group-btn">
                                                <button type="button" class="btn btn-default btn-flat qty-btn" data-action="increase" data-index="<?php echo $index; ?>">+</button>
                                            </span>
                                            </div>
                                        </td>
                                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td>
                                            <a href="POS.php?remove_item=<?php echo $index; ?>" class="text-danger">
                                                <i class="fa fa-times"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="cart-summary">
                                <div class="cart-total">
                                    <span class="label">Subtotal:</span>
                                    <span class="value">₱<?php echo number_format($total_amount, 2); ?></span>
                                </div>
                                <div class="cart-total">
                                    <span class="label">Tax (12%):</span>
                                    <span class="value">₱<?php echo number_format($total_amount * 0.12, 2); ?></span>
                                </div>
                                <div class="cart-total grand-total">
                                    <span class="label">Total:</span>
                                    <span class="value">₱<?php echo number_format($total_amount * 1.12, 2); ?></span>
                                </div>
                            </div>

                            <div class="checkout-section">
                                <button type="button" class="btn btn-primary btn-block btn-lg" data-toggle="modal" data-target="#checkout-modal">
                                    <i class="fa fa-check-circle"></i> Checkout
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                <i class="fa fa-shopping-cart fa-3x"></i>
                                <p>Your cart is empty</p>
                                <small>Add products by clicking on items in the product grid</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Add Modal -->
    <div class="modal fade" id="quick-add-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add to Cart</h4>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="modal-product-id">
                        <div class="form-group">
                            <label>Product</label>
                            <input type="text" class="form-control" id="modal-product-name" readonly>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="text" class="form-control" id="modal-product-price" readonly>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="form-control" name="quantity" id="modal-product-quantity" value="1" min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkout-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Checkout</h4>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Name</label>
                                    <input type="text" class="form-control" name="customer_name" placeholder="Walk-in Customer">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer ID (Optional)</label>
                                    <input type="text" class="form-control" name="customer_id" placeholder="Enter customer ID if available">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Customer Email (Optional)</label>
                            <input type="email" class="form-control" name="customer_email" placeholder="Enter email if available">
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="Gcash">Gcash</option>
                                <option value="PayPal">PayPal</option>
                            </select>
                        </div>
                        <div class="cart-summary modal-summary">
                            <div class="cart-total">
                                <span class="label">Subtotal:</span>
                                <span class="value">₱<?php echo number_format($total_amount, 2); ?></span>
                            </div>
                            <div class="cart-total">
                                <span class="label">Tax (12%):</span>
                                <span class="value">₱<?php echo number_format($total_amount * 0.12, 2); ?></span>
                            </div>
                            <div class="cart-total grand-total">
                                <span class="label">Total:</span>
                                <span class="value">₱<?php echo number_format($total_amount * 1.12, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="checkout" class="btn btn-success"><i class="fa fa-credit-card"></i> Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CSS for POS -->
    <style>
        /* General Styling */
        .content {
            margin-bottom: 30px;
        }

        /* Category Filters */
        .category-filters {
            margin-bottom: 15px;
            padding: 10px 0;
            overflow-x: auto;
            white-space: nowrap;
        }

        .category-filters .btn {
            margin-right: 5px;
            border-radius: 20px;
            transition: all 0.3s;
        }

        .category-filters .btn.active {
            background-color: #3c8dbc;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Product Grid */
        .product-grid {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .product-item {
            padding: 8px;
            margin-bottom: 15px;
        }

        .product-box {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-image {
            height: 120px;
            overflow: hidden;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-info {
            padding: 10px;
            background: white;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-info h4 {
            margin: 0 0 5px;
            font-size: 14px;
            font-weight: bold;
            height: 32px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-price {
            font-weight: bold;
            color: #3c8dbc;
            margin-bottom: 5px;
        }

        .product-stock {
            font-size: 12px;
            margin-top: auto;
        }

        .in-stock {
            color: #27ae60;
        }

        .out-of-stock {
            color: #e74c3c;
        }

        /* Cart Section */
        .cart-items-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .cart-table {
            margin-bottom: 10px;
        }

        .cart-table th,
        .cart-table td {
            vertical-align: middle !important;
        }

        .quantity-control {
            max-width: 100px;
        }

        .qty-input {
            text-align: center;
        }

        .cart-summary {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e0e0e0;
        }

        .checkout-section {
            margin-top: 20px;
        }

        /* Modal Styling */
        .modal-summary {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        /* Responsive Adjustments */
        @media (max-width: 767px) {
            .product-item {
                width: 50%;
            }
        }

        @media (max-width: 480px) {
            .product-item {
                width: 100%;
            }
        }
    </style>

    <!-- JavaScript for POS functionality -->
    <script>
        $(document).ready(function() {
            // Product search functionality
            $('#product-search').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();
                $('.product-item').each(function() {
                    var productName = $(this).data('name');
                    if (productName.indexOf(searchText) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Quantity control buttons
            $('.qty-btn').on('click', function() {
                var action = $(this).data('action');
                var index = $(this).data('index');
                var inputElement = $('.qty-input[data-index="' + index + '"]');
                var currentValue = parseInt(inputElement.val());
                var newValue = currentValue;

                if (action === 'increase') {
                    newValue = currentValue + 1;
                    inputElement.val(newValue);
                } else if (action === 'decrease' && currentValue > 1) {
                    newValue = currentValue - 1;
                    inputElement.val(newValue);
                } else {
                    return; // No change needed
                }

                // Update quantity via AJAX
                updateCartItemQuantity(index, newValue);
            });

            // Quantity input change
            $('.qty-input').on('change', function() {
                var index = $(this).data('index');
                var newValue = parseInt($(this).val());

                if (isNaN(newValue) || newValue < 1) {
                    $(this).val(1);
                    newValue = 1;
                }

                // Update quantity via AJAX
                updateCartItemQuantity(index, newValue);
            });

            // Function to update cart item quantity
            // Function to update cart item quantity
            function updateCartItemQuantity(index, quantity) {
                $.ajax({
                    url: 'POS.php',
                    type: 'POST',
                    data: {
                        action: 'update_quantity',
                        index: index,
                        quantity: quantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update the item total price
                            var row = $('.qty-input[data-index="' + index + '"]').closest('tr');
                            var totalCell = row.find('td:eq(3)');
                            totalCell.text('₱' + response.item_total);

                            // Reload the page to update the cart totals
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating the cart.');
                    }
                });
            }
        });

        // Quick add product to cart
        function quickAdd(productId, productName, productPrice) {
            $('#modal-product-id').val(productId);
            $('#modal-product-name').val(productName);
            $('#modal-product-price').val('₱' + productPrice.toFixed(2));
            $('#modal-product-quantity').val(1);
            $('#quick-add-modal').modal('show');
        }
    </script>

<?php require_once('footer.php'); ?>