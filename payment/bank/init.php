<?php
ob_start();
session_start();
include("../../admin/inc/config.php");
include("../../admin/inc/functions.php");

function back_to_checkout($message)
{
    header('location: ../../checkout.php?error=' . urlencode($message));
    exit;
}

if (!isset($_SESSION['customer'])) {
    header('location: ../../login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: ../../checkout.php');
    exit;
}
// An upload bigger than post_max_size arrives with an empty $_POST
if (empty($_POST)) {
    back_to_checkout('The uploaded file is too large. Please upload a smaller screenshot.');
}
if (empty($_POST['agree_terms'])) {
    back_to_checkout('Please read and accept the Terms and Conditions before submitting.');
}

$customer = $_SESSION['customer'];

$statement = $pdo->prepare("SELECT * FROM tbl_customer_address WHERE addr_id=? AND cust_id=?");
$statement->execute(array(isset($_POST['address_id']) ? $_POST['address_id'] : 0, $customer['cust_id']));
$address = $statement->fetch(PDO::FETCH_ASSOC);
if (!$address) {
    back_to_checkout('Please choose a shipping address.');
}

$option = isset($_POST['payment_option']) ? $_POST['payment_option'] : '';
if (!in_array($option, array('down', 'full'))) {
    back_to_checkout('Please choose a payment option.');
}

$reference_no = clean_reference_no(isset($_POST['reference_no']) ? $_POST['reference_no'] : '');
if ($reference_no == '') {
    back_to_checkout('Please enter the GCash reference number shown on your receipt.');
}

// Totals are computed here from the database prices, never from the form
$items = get_selected_cart_items($pdo);
if (count($items) == 0) {
    header('location: ../../cart.php');
    exit;
}
$subtotal = 0;
foreach ($items as $item) {
    if ($item['qty'] > $item['stock']) {
        back_to_checkout('Sorry, only ' . max(0, $item['stock']) . ' item(s) of "' . $item['name'] . '" are in stock.');
    }
    $subtotal += $item['total'];
}
$total_amount = $subtotal + SHIPPING_FEE;
if ($option == 'down') {
    $amount_now = down_payment_amount($total_amount);
    $payment_option = OPTION_DOWN_PAYMENT;
    $payment_type = 'Down Payment';
} else {
    $amount_now = $total_amount;
    $payment_option = OPTION_FULL_PAYMENT;
    $payment_type = 'Full Payment';
}

$upload_error = '';
$transaction_image = upload_transaction_image(
    isset($_FILES['transaction_image']) ? $_FILES['transaction_image'] : null,
    '../../assets/uploads/',
    $upload_error
);
if ($transaction_image === false) {
    back_to_checkout($upload_error);
}

$payment_date = date('Y-m-d H:i:s');
$payment_id = time();
$statement = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment WHERE payment_id=?");
do {
    $statement->execute(array($payment_id));
    $taken = $statement->fetchColumn() > 0;
    if ($taken) {
        $payment_id++;
    }
} while ($taken);

$product_ids = array();
foreach ($items as $item) {
    $product_ids[] = $item['id'];
}

// Order header; paid_amount stays 0 until the admin confirms the payment
$statement = $pdo->prepare("INSERT INTO tbl_payment (
                                customer_id,
                                customer_name,
                                customer_email,
                                payment_date,
                                paid_amount,
                                total_amount,
                                shipping_fee,
                                transaction_image,
                                payment_method,
                                payment_option,
                                payment_status,
                                shipping_status,
                                payment_id,
                                product_id,
                                shipping_name,
                                shipping_phone,
                                shipping_address,
                                shipping_lat,
                                shipping_lng
                            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$statement->execute(array(
    $customer['cust_id'],
    $customer['cust_name'],
    $customer['cust_email'],
    $payment_date,
    0,
    $total_amount,
    SHIPPING_FEE,
    $transaction_image,
    'Gcash',
    $payment_option,
    'Pending',
    'Pending',
    $payment_id,
    implode(',', $product_ids),
    $address['full_name'],
    $address['phone'],
    format_address($address),
    $address['latitude'],
    $address['longitude']
));

// The submitted GCash payment, waiting for the admin's confirmation
$statement = $pdo->prepare("INSERT INTO tbl_payment_history (payment_id, amount, payment_type, payment_method, reference_no, transaction_image, status, submitted_at)
                            VALUES (?,?,?,?,?,?,?,?)");
$statement->execute(array($payment_id, $amount_now, $payment_type, 'Gcash', $reference_no, $transaction_image, 'Pending', $payment_date));

$stmt_order = $pdo->prepare("INSERT INTO tbl_order (
                                product_id,
                                product_name,
                                quantity,
                                unit_price,
                                payment_id
                            ) VALUES (?,?,?,?,?)");
$stmt_stock = $pdo->prepare("UPDATE tbl_product SET p_qty=GREATEST(p_qty-?,0) WHERE p_id=?");
foreach ($items as $item) {
    $stmt_order->execute(array($item['id'], $item['name'], $item['qty'], $item['price'], $payment_id));
    $stmt_stock->execute(array($item['qty'], $item['id']));
}

add_order_timeline($pdo, $payment_id, 'Order Placed',
    'Your order of ' . count($items) . ' item(s) with a total of ' . peso($total_amount) . ' has been placed.');
add_order_timeline($pdo, $payment_id, 'Payment Submitted',
    $payment_type . ' of ' . peso($amount_now) . ' via GCash (Ref. No. ' . $reference_no . ') was submitted for verification.');

// Remove the ordered items from the cart
foreach ($_SESSION['cart_p_id'] as $key => $p_id) {
    if (in_array($p_id, $product_ids)) {
        unset($_SESSION['cart_p_id'][$key]);
        unset($_SESSION['cart_p_qty'][$key]);
        unset($_SESSION['cart_p_name'][$key]);
        unset($_SESSION['cart_p_current_price'][$key]);
        unset($_SESSION['cart_p_featured_photo'][$key]);
    }
}
if (count($_SESSION['cart_p_id']) == 0) {
    unset($_SESSION['cart_p_id'], $_SESSION['cart_p_qty'], $_SESSION['cart_p_name'], $_SESSION['cart_p_current_price'], $_SESSION['cart_p_featured_photo']);
} else {
    // Reindex to keep arrays sequential
    $_SESSION['cart_p_id'] = array_values($_SESSION['cart_p_id']);
    $_SESSION['cart_p_qty'] = array_values($_SESSION['cart_p_qty']);
    $_SESSION['cart_p_name'] = array_values($_SESSION['cart_p_name']);
    $_SESSION['cart_p_current_price'] = array_values($_SESSION['cart_p_current_price']);
    $_SESSION['cart_p_featured_photo'] = array_values($_SESSION['cart_p_featured_photo']);
}
unset($_SESSION['selected_items']);

header('location: ../../payment_success.php?payment_id=' . urlencode($payment_id));
exit;
