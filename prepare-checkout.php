<?php
session_start();

// Check if the config.php file exists before including it
if (file_exists('config.php')) {
    require_once('config.php'); // Include configuration file
} else {
    die("config.php file not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prepare_checkout'])) {

    // Capture the selected items from the form
    $selected_ids = $_POST['selected_items'] ?? [];

    if (empty($selected_ids)) {
        echo "<script>alert('Please select at least one item to checkout.');window.location.href='cart.php';</script>";
        exit;
    }

    // Prepare the checkout array
    $checkout = [];

    // Loop through all product IDs to check if they're selected
    foreach ($_POST['product_id_all'] as $index => $id) {
        if (in_array($id, $selected_ids)) {
            $checkout[] = [
                'id'    => $id,
                'name'  => $_POST['product_name_all'][$index],
                'qty'   => $_POST['quantity_all'][$index],
                'price' => $_POST['price_all'][$index],
                'photo' => $_POST['photo_all'][$index]
            ];
        }
    }

    // Store the selected items in the session for the checkout page
    $_SESSION['checkout'] = $checkout;

    // Redirect to the checkout page
    header('Location: checkout.php');
    exit;
}
?>

