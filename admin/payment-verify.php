<?php require_once('header.php'); ?>

<?php
// Confirms or rejects a payment the customer submitted, or records a cash
// payment collected by the staff. Every action is added to the order timeline.

function back_to_orders($message, $type = 'success')
{
    header('location: order.php?' . http_build_query(array('msg' => $message, 'type' => $type)));
    exit;
}

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';

if ($task == 'confirm' || $task == 'reject') {
    $statement = $pdo->prepare("SELECT * FROM tbl_payment_history WHERE id=?");
    $statement->execute(array(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0));
    $h = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$h || $h['status'] != 'Pending') {
        back_to_orders('This payment was already processed.', 'danger');
    }
    $ref = $h['reference_no'] != '' ? ' (Ref. No. ' . $h['reference_no'] . ')' : '';

    if ($task == 'confirm') {
        $summary = recalc_order_payment($pdo, $h['payment_id']);
        if (!$summary) {
            back_to_orders('Order not found.', 'danger');
        }
        if ($h['amount'] > $summary['balance']) {
            back_to_orders('This payment (' . peso($h['amount']) . ') is more than the remaining balance ('
                . peso($summary['balance']) . '). Please reject it instead.', 'danger');
        }

        $statement = $pdo->prepare("UPDATE tbl_payment_history SET status='Confirmed', confirmed_at=? WHERE id=?");
        $statement->execute(array(date('Y-m-d H:i:s'), $h['id']));
        $summary = recalc_order_payment($pdo, $h['payment_id']);

        $description = $h['payment_type'] . ' of ' . peso($h['amount']) . $ref . ' has been received and confirmed.';
        $description .= $summary['balance'] > 0
            ? ' Remaining balance: ' . peso($summary['balance']) . '.'
            : ' Your order is now fully paid.';
        add_order_timeline($pdo, $h['payment_id'], 'Payment Confirmed', $description);

        back_to_orders('Payment confirmed for order #' . $h['payment_id'] . '. '
            . ($summary['balance'] > 0 ? 'Remaining balance: ' . peso($summary['balance']) . '.' : 'The order is fully paid.'));
    }

    // Reject: the reason is shown to the customer
    $reason = isset($_POST['reason']) ? trim(strip_tags($_POST['reason'])) : '';
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || $reason == '') {
        back_to_orders('Please give a reason for rejecting the payment.', 'danger');
    }
    $statement = $pdo->prepare("UPDATE tbl_payment_history SET status='Rejected', remarks=? WHERE id=?");
    $statement->execute(array($reason, $h['id']));
    recalc_order_payment($pdo, $h['payment_id']);

    add_order_timeline($pdo, $h['payment_id'], 'Payment Rejected',
        $h['payment_type'] . ' of ' . peso($h['amount']) . $ref . ' could not be verified: ' . $reason
        . ' Please submit a new payment confirmation in My Orders.');

    back_to_orders('Payment rejected for order #' . $h['payment_id'] . '. The customer can submit a new payment confirmation.');
}

if ($task == 'record' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_id = isset($_POST['payment_id']) ? $_POST['payment_id'] : '';
    $amount = isset($_POST['amount']) ? round((float)$_POST['amount'], 2) : 0;

    $summary = recalc_order_payment($pdo, $payment_id);
    if (!$summary) {
        back_to_orders('Order not found.', 'danger');
    }
    if ($amount <= 0 || $amount > $summary['balance']) {
        back_to_orders('The amount must be from ' . peso(0.01) . ' to the remaining balance of ' . peso($summary['balance']) . '.', 'danger');
    }

    if ($summary['paid'] > 0) {
        $payment_type = 'Balance Payment';
    } else {
        $payment_type = $amount >= $summary['total'] ? 'Full Payment' : 'Down Payment';
    }
    $now = date('Y-m-d H:i:s');
    $statement = $pdo->prepare("INSERT INTO tbl_payment_history (payment_id, amount, payment_type, payment_method, status, remarks, submitted_at, confirmed_at)
                                VALUES (?,?,?,?,?,?,?,?)");
    $statement->execute(array($payment_id, $amount, $payment_type, 'Cash', 'Confirmed',
        'Recorded by ' . $_SESSION['user']['full_name'], $now, $now));
    $summary = recalc_order_payment($pdo, $payment_id);

    $description = 'Cash payment of ' . peso($amount) . ' has been received.';
    $description .= $summary['balance'] > 0
        ? ' Remaining balance: ' . peso($summary['balance']) . '.'
        : ' Your order is now fully paid.';
    add_order_timeline($pdo, $payment_id, 'Payment Received', $description);

    back_to_orders('Cash payment of ' . peso($amount) . ' recorded for order #' . $payment_id . '. '
        . ($summary['balance'] > 0 ? 'Remaining balance: ' . peso($summary['balance']) . '.' : 'The order is fully paid.'));
}

back_to_orders('Unknown action.', 'danger');
