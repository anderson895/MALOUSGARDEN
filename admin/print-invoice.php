<?php require_once('header.php'); ?>
<?php
// Check if payment_id is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    header('location: view-order.php');
    exit;
}

$payment_id = $_GET['payment_id'];

// Get payment details
$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=?");
$statement->execute(array($payment_id));
$payment = $statement->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('location: view-order.php');
    exit;
}

// Get order items
$statement = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
$statement->execute(array($payment_id));
$order_items = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get customer address if available
$customer_address = '';
if ($payment['customer_id'] > 0) {
    $statement = $pdo->prepare("SELECT cust_s_address FROM tbl_customer WHERE cust_id=?");
    $statement->execute(array($payment['customer_id']));
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $customer_address = $result['cust_s_address'];
    }
}

// Get company info from settings
$company_name = '';
$company_address = '';
$company_phone = '';
$company_email = '';
$company_website = '';
$company_logo = '';

$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $company_name = isset($result['footer_copyright']) ? $result['footer_copyright'] : 'Your Company Name';
    $company_address = isset($result['contact_address']) ? $result['contact_address'] : '';
    $company_phone = isset($result['contact_phone']) ? $result['contact_phone'] : '';
    $company_email = isset($result['contact_email']) ? $result['contact_email'] : '';
    $company_website = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $company_logo = isset($result['logo']) ? $result['logo'] : '';
    
}

// Calculate subtotal and taxes
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['unit_price'] * $item['quantity'];
}
$tax = $subtotal * 0.10; // Assuming 12% tax
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Invoice #<?php echo $payment_id; ?></title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <style type="text/css">
        /* Invoice specific styles for printing */
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                background-color: #fff;
            }
            .no-print {
                display: none !important;
            }
            a {
                text-decoration: none;
                color: #000;
            }
            .invoice-box {
                width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
        }

        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }

        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            background-color: #fff;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .invoice-logo {
            max-width: 200px;
            max-height: 80px;
        }

        .invoice-company {
            text-align: left;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            color: #0077cc;
            font-size: 28px;
            margin: 0 0 5px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .invoice-customer, .invoice-payment {
            width: 48%;
        }

        .invoice-heading {
            background-color: #0077cc;
            color: white;
            padding: 8px 12px;
            font-size: 16px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-items th {
            background-color: #f2f2f2;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }

        .invoice-items td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .item-quantity, .item-price, .item-total {
            text-align: right;
        }

        .invoice-totals {
            width: 40%;
            margin-left: auto;
        }

        .invoice-total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .invoice-grand-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 5px;
        }

        .invoice-footer {
            margin-top: 40px;
            text-align: center;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .btn-print {
            background-color: #0077cc;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-print:hover {
            background-color: #005fa3;
        }

        .text-right {
            text-align: right;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .invoice-header, .invoice-details {
                flex-direction: column;
            }

            .invoice-title {
                text-align: left;
                margin-top: 20px;
            }

            .invoice-customer, .invoice-payment {
                width: 100%;
                margin-bottom: 20px;
            }

            .invoice-totals {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="invoice-header">
        <div class="invoice-company">
            <?php if (!empty($company_logo)): ?>
                <img src="../assets/uploads/<?php echo $company_logo; ?>" alt="Company Logo" class="invoice-logo">
            <?php else: ?>
                <h2><?php echo $company_name; ?></h2>
            <?php endif; ?>
            <p><?php echo $company_address; ?></p>
            <p>Phone: <?php echo $company_phone; ?></p>
            <p>Email: <?php echo $company_email; ?></p>
            <p>Website: <?php echo $company_website; ?></p>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p>Invoice #: <?php echo $payment_id; ?></p>
            <p>Date: <?php echo date('d M Y', strtotime($payment['payment_date'])); ?></p>
            <p>Payment Status: <strong><?php echo $payment['payment_status']; ?></strong></p>
            <p>Shipping Status: <strong><?php echo $payment['shipping_status']; ?></strong></p>
        </div>
    </div>

    <div class="invoice-details">
        <div class="invoice-customer">
            <div class="invoice-heading">Customer Information</div>
            <p><strong>Name:</strong> <?php echo $payment['customer_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $payment['customer_email']; ?></p>
            <?php if (!empty($customer_address)): ?>
                <p><strong>Address:</strong><br> <?php echo nl2br($customer_address); ?></p>
            <?php endif; ?>
        </div>
        <div class="invoice-payment">
            <div class="invoice-heading">Payment Information</div>
            <p><strong>Method:</strong> <?php echo $payment['payment_method']; ?></p>
            <?php if ($payment['payment_method'] == 'PayPal'): ?>
                <p><strong>Transaction ID:</strong> <?php echo $payment['txnid']; ?></p>
            <?php elseif ($payment['payment_method'] == 'Gcash'): ?>
                <p><strong>Transaction Details:</strong> Available in system</p>
            <?php endif; ?>
        </div>
    </div>

    <table class="invoice-items">
        <thead>
        <tr>
            <th width="55%">Product</th>
            <th width="15%" class="text-right">Unit Price</th>
            <th width="10%" class="text-right">Quantity</th>
            <th width="20%" class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($order_items as $item): ?>
            <tr>
                <td><?php echo $item['product_name']; ?></td>
                <td class="text-right">₱ <?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right">₱ <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="invoice-totals">
        <div class="invoice-total-row">
            <div>Subtotal:</div>
            <div>₱ <?php echo number_format($subtotal, 2); ?></div>
        </div>
        <div class="invoice-total-row">
            <div>Tax (10%):</div>
            <div>₱ <?php echo number_format($tax, 2); ?></div>
        </div>
        <div class="invoice-total-row invoice-grand-total">
            <div>TOTAL:</div>
            <div>₱ <?php echo number_format($total, 2); ?></div>
        </div>
    </div>

    <div class="invoice-footer">
        <p>Thank you for your business!</p>
        <p><?php echo $company_name; ?> &copy; <?php echo date('Y'); ?></p>
    </div>
</div><br>
<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button class="btn-print" onclick="window.print();">Print Invoice</button>
    <a href="order.php" class="btn-print" style="background-color: #777;">Back to Orders</a>
</div>

<script>
    // Auto print when the page loads (uncomment if you want automatic print dialog)
    // window.onload = function() {
    //     window.print();
    // }
</script>
</body>
</html>