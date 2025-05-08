<?php
ob_start();
session_start();
include("../../admin/inc/config.php");
include("../../admin/inc/functions.php");

if (!isset($_REQUEST['msg'])) {
    if (!isset($_FILES['transaction_image']) || $_FILES['transaction_image']['error'] !== UPLOAD_ERR_OK) {
        header('location: ../../checkout.php?error=File upload failed');
        exit;
    } else {
        $payment_date = date('Y-m-d H:i:s');
        $payment_id = time();

        $transaction_image = $_FILES['transaction_image'];
        $upload_dir = '../../assets/uploads/';
        // Ensure upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $imageFileType = strtolower(pathinfo($transaction_image['name'], PATHINFO_EXTENSION));

        // Validate image file
        $check = getimagesize($transaction_image['tmp_name']);
        if ($check === false) {
            header('location: ../../checkout.php?error=File is not an image');
            exit;
        }

        // Allowed extensions
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($imageFileType, $allowed_extensions)) {
            header('location: ../../checkout.php?error=Invalid file type');
            exit;
        }

        // Generate a unique file name to avoid overwriting
        $unique_name = uniqid('transaction_', true) . '.' . $imageFileType;
        $upload_file = $upload_dir . $unique_name;

        if (!move_uploaded_file($transaction_image['tmp_name'], $upload_file)) {
            header('location: ../../checkout.php?error=Failed to move uploaded file');
            exit;
        }

        // Insert payment record
        $statement = $pdo->prepare("INSERT INTO tbl_payment (   
                                customer_id,
                                customer_name,
                                customer_email,
                                payment_date,
                                paid_amount,                          
                                transaction_image,
                                payment_method,
                                payment_status,
                                shipping_status,
                                payment_id,
                                product_id
                            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

        // Using product IDs string for payment record
        $product_ids_str = isset($_POST['product_ids']) ? $_POST['product_ids'] : '';
        $statement->execute(array(
            $_SESSION['customer']['cust_id'],
            $_SESSION['customer']['cust_name'],
            $_SESSION['customer']['cust_email'],
            $payment_date,
            $_POST['amount'],
            $unique_name, // Save the file name to DB, not full path
            'Gcash',
            'Pending',
            'Pending',
            $payment_id,
            $product_ids_str
        ));

        // Explode product IDs (comma-separated string)
        $selected_product_ids = array();
        if (!empty($product_ids_str)) {
            $selected_product_ids = explode(',', $product_ids_str);
            // Trim whitespace
            $selected_product_ids = array_map('trim', $selected_product_ids);
        }

        // Get cart session arrays
        $cart_p_id = isset($_SESSION['cart_p_id']) ? $_SESSION['cart_p_id'] : array();
        $cart_p_qty = isset($_SESSION['cart_p_qty']) ? $_SESSION['cart_p_qty'] : array();
        $cart_p_name = isset($_SESSION['cart_p_name']) ? $_SESSION['cart_p_name'] : array();
        $cart_p_current_price = isset($_SESSION['cart_p_current_price']) ? $_SESSION['cart_p_current_price'] : array();
        $cart_p_featured_photo = isset($_SESSION['cart_p_featured_photo']) ? $_SESSION['cart_p_featured_photo'] : array();

        foreach ($selected_product_ids as $selected_id) {
            $key = array_search($selected_id, $cart_p_id);
            if ($key !== false) {
                // Gather product info
                $product_id = $cart_p_id[$key];
                $product_name = $cart_p_name[$key];
                $quantity = $cart_p_qty[$key];
                $unit_price = $cart_p_current_price[$key];

                // Insert order record
                $stmt_order = $pdo->prepare("INSERT INTO tbl_order (
                                        product_id,
                                        product_name,
                                        quantity, 
                                        unit_price, 
                                        payment_id
                                    ) VALUES (?,?,?,?,?)");
                $stmt_order->execute(array(
                    $product_id,
                    $product_name,
                    $quantity,
                    $unit_price,
                    $payment_id
                ));

                // Update stock accordingly
                $stmt_stock = $pdo->prepare("SELECT p_qty FROM tbl_product WHERE p_id=?");
                $stmt_stock->execute([$product_id]);
                $current_stock = $stmt_stock->fetchColumn();
                $new_stock = max(0, $current_stock - $quantity);
                $stmt_update = $pdo->prepare("UPDATE tbl_product SET p_qty=? WHERE p_id=?");
                $stmt_update->execute([$new_stock, $product_id]);

                // Remove from cart arrays
                unset($cart_p_id[$key]);
                unset($cart_p_qty[$key]);
                unset($cart_p_name[$key]);
                unset($cart_p_current_price[$key]);
                unset($cart_p_featured_photo[$key]);
            }
        }

        // Reindex to keep arrays sequential
        $_SESSION['cart_p_id'] = array_values($cart_p_id);
        $_SESSION['cart_p_qty'] = array_values($cart_p_qty);
        $_SESSION['cart_p_name'] = array_values($cart_p_name);
        $_SESSION['cart_p_current_price'] = array_values($cart_p_current_price);
        $_SESSION['cart_p_featured_photo'] = array_values($cart_p_featured_photo);

        header('location: ../../payment_success.php');
        exit;
    }
}
?>
