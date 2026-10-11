<?php require_once('header.php'); ?>

<?php
// Check if the customer is logged in or not
if(!isset($_SESSION['customer'])) {
    header('location: login.php');
    exit;
} else {
    // If customer is logged in, but admin make him inactive, then force logout this user.
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=? AND cust_status=?");
    $statement->execute(array($_SESSION['customer']['cust_id'],0));
    $total = $statement->rowCount();
    if($total) {
        header('location: logout.php');
        exit;
    }
}

$payment_id = isset($_GET['id']) ? $_GET['id'] : '';
$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=? AND customer_email=? ORDER BY id");
$statement->execute(array($payment_id, $_SESSION['customer']['cust_email']));
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
if(count($rows) == 0) {
    header('location: customer-order.php');
    exit;
}
$order = $rows[0];

// A POS sale can have several rows for one transaction
$order_total = 0;
$order_paid = 0;
$shipping_fee = 0;
foreach($rows as $r) {
    $order_total += $r['total_amount'];
    $order_paid += $r['paid_amount'];
    $shipping_fee += $r['shipping_fee'];
}
$balance = max(0, round($order_total - $order_paid, 2));
$is_pos = (strpos($order['payment_id'], 'TXN-') === 0);
$pending = count_pending_payments($pdo, $payment_id);
$can_pay = !$is_pos && $balance > 0 && $pending == 0;
// Nothing confirmed yet: the 50% down payment is the minimum
$min_amount = $order_paid > 0 ? 1 : min($balance, down_payment_amount($order_total));

// Customer pays (part of) the balance through GCash
if(isset($_POST['pay_balance'])) {
    $amount = isset($_POST['amount']) ? round((float)$_POST['amount'], 2) : 0;
    $reference_no = clean_reference_no(isset($_POST['reference_no']) ? $_POST['reference_no'] : '');

    $valid = 1;
    if(!$can_pay) {
        $valid = 0;
        $error_message .= "This order has no balance to pay, or a payment is still being verified.<br>";
    }
    if($valid && ($amount < $min_amount || $amount > $balance)) {
        $valid = 0;
        $error_message .= "Please enter an amount from ".peso($min_amount)." to ".peso($balance).".<br>";
    }
    if($valid && $reference_no == '') {
        $valid = 0;
        $error_message .= "Please enter the GCash reference number shown on your receipt.<br>";
    }
    if($valid) {
        $upload_error = '';
        $transaction_image = upload_transaction_image(isset($_FILES['transaction_image']) ? $_FILES['transaction_image'] : null, 'assets/uploads/', $upload_error);
        if($transaction_image === false) {
            $valid = 0;
            $error_message .= $upload_error."<br>";
        }
    }
    if($valid) {
        if($order_paid > 0) {
            $payment_type = 'Balance Payment';
        } else {
            $payment_type = $amount >= $order_total ? 'Full Payment' : 'Down Payment';
        }
        $statement = $pdo->prepare("INSERT INTO tbl_payment_history (payment_id, amount, payment_type, payment_method, reference_no, transaction_image, status, submitted_at)
                                    VALUES (?,?,?,?,?,?,?,?)");
        $statement->execute(array($payment_id, $amount, $payment_type, 'Gcash', $reference_no, $transaction_image, 'Pending', date('Y-m-d H:i:s')));

        add_order_timeline($pdo, $payment_id, 'Payment Submitted',
            $payment_type.' of '.peso($amount).' via GCash (Ref. No. '.$reference_no.') was submitted for verification.');

        header('location: payment_success.php?payment_id='.urlencode($payment_id));
        exit;
    }
}

$statement = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
$statement->execute(array($payment_id));
$items = $statement->fetchAll(PDO::FETCH_ASSOC);
$subtotal = 0;
foreach($items as $item) {
    $subtotal += (float)$item['unit_price'] * (int)$item['quantity'];
}

$statement = $pdo->prepare("SELECT * FROM tbl_payment_history WHERE payment_id=? ORDER BY id DESC");
$statement->execute(array($payment_id));
$history = $statement->fetchAll(PDO::FETCH_ASSOC);

$statement = $pdo->prepare("SELECT * FROM tbl_order_timeline WHERE payment_id=? ORDER BY created_at DESC, id DESC");
$statement->execute(array($payment_id));
$timeline = $statement->fetchAll(PDO::FETCH_ASSOC);

// Progress tracker
$paid_states = array('Partially Paid', 'Completed');
$ship = $order['shipping_status'];
if($order['payment_status'] == 'Completed') {
    $payment_step = 'Fully Paid';
} elseif($order['payment_status'] == 'Partially Paid') {
    $payment_step = 'Down Payment Confirmed';
} else {
    $payment_step = 'Payment Verification';
}
$steps = array(
    array('Order Placed', true, 'fa-shopping-cart'),
    array($payment_step, in_array($order['payment_status'], $paid_states), 'fa-money'),
    array('To Ship', in_array($ship, array('To-Ship', 'Shipped', 'Completed')), 'fa-archive'),
    array('Shipped', in_array($ship, array('Shipped', 'Completed')), 'fa-truck'),
    array('Delivered', $ship == 'Completed', 'fa-home'),
);
$current_step = count($steps);
foreach($steps as $i => $step) {
    if(!$step[1]) {
        $current_step = $i;
        break;
    }
}
?>

<style>
    .order-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .order-head h3 { margin: 0; }
    .tracker { display: flex; margin: 25px 0 30px; }
    .tracker .step { flex: 1; text-align: center; position: relative; color: #999; }
    .tracker .step:before { content: ''; position: absolute; top: 22px; left: -50%; width: 100%; height: 4px; background: #ddd; z-index: 0; }
    .tracker .step:first-child:before { display: none; }
    .tracker .step .dot { width: 48px; height: 48px; line-height: 44px; border-radius: 50%; border: 3px solid #ddd; background: #fff; margin: 0 auto 8px; position: relative; z-index: 1; font-size: 18px; }
    .tracker .step.done { color: #2e7d32; }
    .tracker .step.done .dot { border-color: #2e7d32; background: #2e7d32; color: #fff; }
    .tracker .step.done:before { background: #2e7d32; }
    .tracker .step.active { color: #ef6c00; font-weight: bold; }
    .tracker .step.active .dot { border-color: #ef6c00; color: #ef6c00; }
    .summary-table td, .summary-table th { padding: 6px 8px !important; }
    .money-row { display: flex; justify-content: space-between; padding: 4px 0; }
    .money-row.balance { font-size: 18px; font-weight: bold; border-top: 1px dashed #ccc; margin-top: 4px; padding-top: 8px; }
    .timeline { list-style: none; padding: 0 0 0 28px; position: relative; margin: 0; }
    .timeline:before { content: ''; position: absolute; left: 9px; top: 4px; bottom: 4px; width: 3px; background: #c8e6c9; }
    .timeline li { position: relative; margin-bottom: 18px; }
    .timeline li:before { content: ''; position: absolute; left: -25px; top: 4px; width: 15px; height: 15px; border-radius: 50%; background: #fff; border: 3px solid #2e7d32; }
    .timeline li:first-child:before { background: #2e7d32; }
    .timeline .when { color: #888; font-size: 12px; }
    .timeline .title { font-weight: bold; }
    .pay-steps { background: #f4fbf4; border: 1px solid #c8e6c9; border-radius: 6px; padding: 12px 15px; margin-bottom: 15px; }
    .pay-steps ol { padding-left: 20px; margin-bottom: 0; }
    @media (max-width: 767px) {
        .tracker { flex-direction: column; }
        .tracker .step { text-align: left; display: flex; align-items: center; margin-bottom: 10px; }
        .tracker .step:before { display: none; }
        .tracker .step .dot { margin: 0 10px 0 0; }
    }
    @media print {
        .top, .header, .nav, .user-sidebar, .footer-bottom, .scrollup, .no-print { display: none !important; }
        a[href]:after { content: none !important; }
    }
</style>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php require_once('customer-sidebar.php'); ?>
            </div>
            <div class="col-md-12">
                <div class="user-content">
                    <?php
                    if($error_message != '') {
                        echo "<div class='error' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$error_message."</div>";
                    }
                    ?>

                    <div class="order-head">
                        <div>
                            <h3>Order #<?php echo htmlspecialchars($order['payment_id']); ?></h3>
                            <span class="text-muted">Placed on <?php echo date('M d, Y h:i A', strtotime($order['payment_date'])); ?></span>
                        </div>
                        <div class="no-print">
                            <a href="customer-order.php" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> My Orders</a>
                            <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
                        </div>
                    </div>

                    <div class="tracker">
                        <?php foreach($steps as $i => $step): ?>
                            <div class="step <?php echo $step[1] ? 'done' : ($i == $current_step ? 'active' : ''); ?>">
                                <div class="dot"><i class="fa <?php echo $step[2]; ?>"></i></div>
                                <div><?php echo $step[0]; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="panel panel-default">
                                <div class="panel-heading"><b><i class="fa fa-list"></i> Order Summary</b></div>
                                <div class="panel-body">
                                    <table class="table table-bordered summary-table">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-right">Unit Price</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                        <?php foreach($items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td class="text-right"><?php echo peso($item['unit_price']); ?></td>
                                                <td class="text-right"><?php echo (int)$item['quantity']; ?></td>
                                                <td class="text-right"><?php echo peso((float)$item['unit_price'] * (int)$item['quantity']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                    <div class="money-row"><span>Subtotal</span><span><?php echo peso($subtotal); ?></span></div>
                                    <div class="money-row"><span>Shipping Fee</span><span><?php echo peso($shipping_fee); ?></span></div>
                                    <div class="money-row"><b>Order Total</b><b><?php echo peso($order_total); ?></b></div>
                                    <div class="money-row"><span>Amount Paid (confirmed)</span><span style="color:#2e7d32;"><?php echo peso($order_paid); ?></span></div>
                                    <div class="money-row balance"><span>Balance</span><span style="color:<?php echo $balance > 0 ? '#c62828' : '#2e7d32'; ?>;"><?php echo peso($balance); ?></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="panel panel-default">
                                <div class="panel-heading"><b><i class="fa fa-info-circle"></i> Status</b></div>
                                <div class="panel-body">
                                    <p><b>Payment Status:</b> <?php echo payment_status_badge($order['payment_status'], $pending); ?></p>
                                    <p><b>Shipping Status:</b> <?php echo shipping_status_badge($order['shipping_status']); ?></p>
                                    <p><b>Payment Option:</b> <?php echo htmlspecialchars($order['payment_option']); ?></p>
                                    <p style="margin-bottom:0;"><b>Payment Method:</b> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading"><b><i class="fa fa-map-marker"></i> Shipping Address</b></div>
                                <div class="panel-body">
                                    <?php if(!empty($order['shipping_address'])): ?>
                                        <p style="margin-bottom:4px;"><b><?php echo htmlspecialchars($order['shipping_name']); ?></b> &middot; <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                        <p style="margin-bottom:4px;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                        <?php if($order['shipping_lat'] !== null && $order['shipping_lng'] !== null): ?>
                                            <a href="<?php echo maps_link($order['shipping_lat'], $order['shipping_lng']); ?>" target="_blank"><i class="fa fa-map-marker"></i> View pinned location on map</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted" style="margin:0;">Walk-in / store purchase.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading"><b><i class="fa fa-credit-card"></i> Payment History</b></div>
                        <div class="panel-body table-responsive">
                            <?php if(count($history) == 0): ?>
                                <p class="text-muted" style="margin:0;">Paid in full at the store (<?php echo htmlspecialchars($order['payment_method']); ?>).</p>
                            <?php else: ?>
                                <table class="table table-bordered table-striped" style="margin-bottom:0;">
                                    <tr>
                                        <th>Date Submitted</th>
                                        <th>Type</th>
                                        <th>Method</th>
                                        <th>Reference No.</th>
                                        <th class="text-right">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                    <?php foreach($history as $h): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y h:i A', strtotime($h['submitted_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($h['payment_type']); ?></td>
                                            <td><?php echo htmlspecialchars($h['payment_method']); ?></td>
                                            <td><?php echo $h['reference_no'] != '' ? htmlspecialchars($h['reference_no']) : '&ndash;'; ?></td>
                                            <td class="text-right"><?php echo peso($h['amount']); ?></td>
                                            <td>
                                                <?php echo history_status_badge($h['status']); ?>
                                                <?php if($h['status'] == 'Confirmed' && $h['confirmed_at']): ?>
                                                    <br><small class="text-muted">Confirmed <?php echo date('M d, Y h:i A', strtotime($h['confirmed_at'])); ?></small>
                                                <?php endif; ?>
                                                <?php if($h['remarks'] != ''): ?>
                                                    <br><small class="text-danger"><?php echo htmlspecialchars($h['remarks']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($can_pay): ?>
                        <div class="panel panel-success no-print" id="pay-balance">
                            <div class="panel-heading"><b><i class="fa fa-mobile"></i> <?php echo $order_paid > 0 ? 'Pay Balance via GCash' : 'Submit Payment via GCash'; ?></b></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="pay-steps">
                                            <b>How to Pay</b>
                                            <ol>
                                                <li>Enter the amount you will pay: from <b><?php echo peso($min_amount); ?></b> up to your full balance of <b><?php echo peso($balance); ?></b>.</li>
                                                <li>Scan the QR code with GCash (or send to the GCash number) and pay that exact amount.</li>
                                                <li>Type the <b>GCash Reference No.</b> from your receipt.</li>
                                                <li>Upload the screenshot of your receipt, then click <b>Submit Confirmation</b>.</li>
                                                <li>We will verify it, and your balance is updated once confirmed.</li>
                                            </ol>
                                        </div>
                                        <div style="display:flex; align-items:flex-start;">
                                            <div style="flex:1;">
                                                <p style="margin-bottom:5px;"><strong>GCash Name:</strong> MA*K TR****N M.</p>
                                                <p style="margin-bottom:5px;"><strong>GCash Number:</strong> 09511717939</p>
                                            </div>
                                            <div style="flex:1; text-align:center;">
                                                <img src="gcash.jpg" alt="GCash QR Code" style="max-width:150px; border:1px solid #ddd;">
                                                <p style="margin-top:5px; font-size:12px;">Scan to pay</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <form action="customer-order-detail.php?id=<?php echo urlencode($payment_id); ?>#pay-balance" method="post" enctype="multipart/form-data" id="balance_form">
                                            <div class="form-group">
                                                <label for="">Amount to Pay (₱) *</label>
                                                <input type="number" name="amount" id="pay-amount" class="form-control" step="0.01"
                                                       min="<?php echo $min_amount; ?>" max="<?php echo $balance; ?>" value="<?php echo $balance; ?>" required>
                                                <small class="text-muted">Pay part of it now or the whole balance. <a href="#" id="pay-full">Pay full balance (<?php echo peso($balance); ?>)</a></small>
                                            </div>
                                            <div class="form-group">
                                                <label for="">GCash Reference No. *</label>
                                                <input type="text" name="reference_no" class="form-control" placeholder="e.g. 1012 345 678901" maxlength="30"
                                                       pattern="[A-Za-z0-9 \-]{6,30}" title="Enter the reference number shown on your GCash receipt" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="">Transaction Image * <br><span style="font-size:12px;font-weight:normal;">(Upload the screenshot of your GCash receipt)</span></label>
                                                <input type="file" name="transaction_image" class="form-control" id="transactionImage" accept="image/*" required>
                                                <img id="previewImg" src="#" alt="Image Preview" style="display:none; margin-top:10px; width:220px; max-width:100%; border:1px solid #ccc;">
                                            </div>
                                            <input type="hidden" name="pay_balance" value="1">
                                            <input type="submit" class="btn btn-primary" value="Submit Confirmation" id="balanceSubmit">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif($balance > 0 && $pending > 0): ?>
                        <div class="alert alert-warning no-print">
                            <i class="fa fa-clock-o"></i> Your latest payment is being verified. Your balance will be updated once it is confirmed, and you can then pay the remaining <?php echo peso($balance); ?> here or in cash upon delivery.
                        </div>
                    <?php elseif($balance <= 0): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> This order is fully paid. Thank you!
                        </div>
                    <?php endif; ?>

                    <div class="panel panel-default">
                        <div class="panel-heading"><b><i class="fa fa-history"></i> Order Timeline</b></div>
                        <div class="panel-body">
                            <ul class="timeline">
                                <?php foreach($timeline as $t): ?>
                                    <li>
                                        <div class="when"><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></div>
                                        <div class="title"><?php echo htmlspecialchars($t['title']); ?></div>
                                        <div><?php echo htmlspecialchars($t['description']); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

<script>
    $(function() {
        $('#pay-full').on('click', function(e) {
            e.preventDefault();
            $('#pay-amount').val(<?php echo json_encode($balance); ?>);
        });

        $('#transactionImage').on('change', function() {
            var file = this.files[0];
            if (!file) {
                $('#previewImg').hide();
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        });

        $('#balance_form').on('submit', function(e) {
            var amount = parseFloat($('#pay-amount').val()) || 0;
            var text = '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (!confirm('Submit your payment confirmation of ' + text + '?\n\nPlease make sure the amount you sent and the GCash reference number are correct.')) {
                e.preventDefault();
                return;
            }
            $('#balanceSubmit').prop('disabled', true).val('Submitting...');
        });
    });
</script>
