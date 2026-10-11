<?php require_once('header.php'); ?>

<?php
if(!isset($_SESSION['customer'])) {
    header('location: login.php');
    exit;
}

// The order and the payment confirmation that was just submitted
$order = false;
$submission = false;
if(isset($_GET['payment_id'])) {
    $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=? AND customer_email=?");
    $statement->execute(array($_GET['payment_id'], $_SESSION['customer']['cust_email']));
    $order = $statement->fetch(PDO::FETCH_ASSOC);
    if($order) {
        $statement = $pdo->prepare("SELECT * FROM tbl_payment_history WHERE payment_id=? ORDER BY id DESC LIMIT 1");
        $statement->execute(array($order['payment_id']));
        $submission = $statement->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<style>
    .confirm-box { border: 1px solid #c8e6c9; border-radius: 8px; padding: 25px 30px; margin: 30px 0; background: #fff; }
    .confirm-box .confirm-icon { font-size: 54px; color: #2e7d32; text-align: center; line-height: 1; }
    .confirm-box h3 { text-align: center; margin: 10px 0 5px; }
    .confirm-box .lead-text { text-align: center; color: #555; margin-bottom: 20px; }
    .confirm-box table th { width: 45%; background: #f7f7f7; }
</style>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <?php if($order && $submission): ?>
                    <?php
                    $pending_amount = $submission['status'] == 'Pending' ? $submission['amount'] : 0;
                    $expected_balance = max(0, $order['total_amount'] - $order['paid_amount'] - $pending_amount);
                    ?>
                    <div class="confirm-box">
                        <div class="confirm-icon"><i class="fa fa-check-circle"></i></div>
                        <h3><?php echo "Payment Confirmation Submitted"; ?></h3>
                        <p class="lead-text">Thank you! We received your payment confirmation. Our staff will verify it against your GCash reference number.</p>

                        <table class="table table-bordered">
                            <tr><th>Order ID</th><td>#<?php echo htmlspecialchars($order['payment_id']); ?></td></tr>
                            <tr><th>Date Submitted</th><td><?php echo date('M d, Y h:i A', strtotime($submission['submitted_at'])); ?></td></tr>
                            <tr><th>Payment Type</th><td><?php echo htmlspecialchars($submission['payment_type']); ?> (<?php echo htmlspecialchars($submission['payment_method']); ?>)</td></tr>
                            <tr><th>Amount Submitted</th><td><b><?php echo peso($submission['amount']); ?></b></td></tr>
                            <tr><th>GCash Reference No.</th><td><?php echo htmlspecialchars($submission['reference_no']); ?></td></tr>
                            <tr><th>Order Total</th><td><?php echo peso($order['total_amount']); ?></td></tr>
                            <tr><th>Remaining Balance (once verified)</th><td><?php echo peso($expected_balance); ?></td></tr>
                            <tr><th>Payment Status</th><td><?php echo history_status_badge($submission['status']); ?></td></tr>
                        </table>

                        <h4>What happens next?</h4>
                        <ol>
                            <li>Our staff verifies your payment with GCash.</li>
                            <li>Once it is confirmed, the payment status in My Orders changes to <b>Partially Paid (Down Payment)</b> or <b>Fully Paid</b>, and your order is prepared for shipping.</li>
                            <?php if($expected_balance > 0): ?>
                                <li>Your remaining balance of <b><?php echo peso($expected_balance); ?></b> can be paid through GCash from your order page, or in cash upon delivery.</li>
                            <?php endif; ?>
                            <li>You can follow every step in the order timeline.</li>
                        </ol>

                        <p style="margin-top:20px;">
                            <a href="customer-order-detail.php?id=<?php echo urlencode($order['payment_id']); ?>" class="btn btn-success"><i class="fa fa-map-signs"></i> Track My Order</a>
                            <a href="customer-order.php" class="btn btn-primary"><?php echo "My Orders"; ?></a>
                            <a href="dashboard.php" class="btn btn-default"><?php echo "Back to Dashboard"; ?></a>
                        </p>
                    </div>
                <?php else: ?>
                    <p>
                        <h3 style="margin-top:20px;"><?php echo "Your payment is currently pending."; ?></h3>
                        <a href="customer-order.php" class="btn btn-success"><?php echo "My Orders"; ?></a>
                        <a href="dashboard.php" class="btn btn-default"><?php echo "Back to Dashboard"; ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>