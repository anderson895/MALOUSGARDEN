<?php require_once('header.php'); ?>


<?php
// Next shipping step => timeline entry shown to the customer
$tasks = array(
	'To-Ship'   => array('Preparing to Ship', 'Your order is packed and ready to ship.'),
	'Shipped'   => array('Shipped', 'Your order has been handed over to the courier and is on its way.'),
	'Completed' => array('Delivered', 'Your order has been delivered.'),
);

if( !isset($_REQUEST['id']) || !isset($_REQUEST['task']) || !isset($tasks[$_REQUEST['task']]) ) {
	header('location: order.php');
	exit;
} else {
	// Check the id is valid or not
	$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE id=?");
	$statement->execute(array($_REQUEST['id']));
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	if( !$row ) {
		header('location: order.php');
		exit;
	}
}

// Shipping starts only after the down payment (or full payment) is confirmed
if( !in_array($row['payment_status'], array('Partially Paid', 'Completed')) ) {
	header('location: order.php?' . http_build_query(array('msg' => 'Please confirm the payment before updating the shipping status.', 'type' => 'danger')));
	exit;
}
?>

<?php
	$statement = $pdo->prepare("UPDATE tbl_payment SET shipping_status=? WHERE id=?");
	$statement->execute(array($_REQUEST['task'],$_REQUEST['id']));

	$description = $tasks[$_REQUEST['task']][1];
	$balance = max(0, $row['total_amount'] - $row['paid_amount']);
	if($_REQUEST['task'] == 'Completed' && $balance > 0) {
		$description .= ' Remaining balance: ' . peso($balance) . '.';
	}
	add_order_timeline($pdo, $row['payment_id'], $tasks[$_REQUEST['task']][0], $description);

	header('location: order.php');
?>