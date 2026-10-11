<?php require_once('header.php'); ?>

<?php
// Define base URL for images
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://" . $_SERVER['HTTP_HOST'];
$base_url .= str_replace('/admin/order', '', dirname($_SERVER['PHP_SELF']));

$error_message = '';
$success_message = '';
if(isset($_POST['form1'])) {
    $valid = 1;
    if(empty($_POST['subject_text'])) {
        $valid = 0;
        $error_message .= 'Subject can not be empty\n';
    }
    if(empty($_POST['message_text'])) {
        $valid = 0;
        $error_message .= 'Subject can not be empty\n';
    }
    if($valid == 1) {

        $subject_text = strip_tags($_POST['subject_text']);
        $message_text = strip_tags($_POST['message_text']);

        // Getting Customer Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id=?");
        $statement->execute(array($_POST['cust_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $cust_email = $row['cust_email'];
        }



        // Getting Admin Email Address
        $statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $admin_email = $row['contact_email'];
        }



        $order_detail = '';
        $statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {

            if($row['payment_method'] == 'PayPal'):
                $payment_details = '
Transaction Id: '.$row['txnid'].'<br>
        		';
            elseif($row['payment_method'] == 'Stripe'):
                $payment_details = '
Transaction Id: '.$row['txnid'].'<br>
Card number: '.$row['card_number'].'<br>
Card CVV: '.$row['card_cvv'].'<br>
Card Month: '.$row['card_month'].'<br>
Card Year: '.$row['card_year'].'<br>
        		';
            elseif($row['payment_method'] == 'Gcash'):
                $payment_details = '
Transaction Details: <br>'.$row['bank_transaction_info'];
            endif;

            $order_detail .= '
Customer Name: '.$row['customer_name'].'<br>
Customer Email: '.$row['customer_email'].'<br>
Payment Method: '.$row['payment_method'].'<br>
Payment Date: '.$row['payment_date'].'<br>
Payment Details: <br>'.$payment_details.'<br>
Paid Amount: '.$row['paid_amount'].'<br>
Payment Status: '.$row['payment_status'].'<br>
Shipping Status: '.$row['shipping_status'].'<br>
Payment Id: '.$row['payment_id'].'<br>
            ';
        }

        $i=0;
        $statement = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $statement->execute(array($_POST['payment_id']));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $i++;
            $order_detail .= '
<br><b><u>Product Item '.$i.'</u></b><br>
Product Name: '.$row['product_name'].'<br>
Quantity: '.$row['quantity'].'<br>
Unit Price: '.$row['unit_price'].'<br>
            ';
        }

        $statement = $pdo->prepare("INSERT INTO tbl_customer_message (subject,message,order_detail,cust_id) VALUES (?,?,?,?)");
        $statement->execute(array($subject_text,$message_text,$order_detail,$_POST['cust_id']));



        $success_message = 'Your message to customer is sent successfully.';

    }
}
?>
<?php
if($error_message != '') {
    echo "<script>alert('".$error_message."')</script>";
}
if($success_message != '') {
    echo "<script>alert('".$success_message."')</script>";
}
?>

    <section class="content-header">
        <div class="content-header-left">
            <h1>View Customer Orders</h1>
        </div>
    </section>


    <section class="content">

        <?php if(isset($_GET['msg']) && $_GET['msg'] != ''): ?>
            <div class="callout callout-<?php echo (isset($_GET['type']) && $_GET['type'] == 'danger') ? 'danger' : 'success'; ?>">
                <p><?php echo htmlspecialchars($_GET['msg']); ?></p>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">


                <div class="box box-info">

                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer Info</th>
                                <th>Product Details</th>
                                <th>
                                    Payment Information
                                </th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                                <th>Shipping Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i=0;
                            $statement = $pdo->prepare("SELECT * FROM tbl_payment ORDER by id DESC");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                            $stmt_history = $pdo->prepare("SELECT * FROM tbl_payment_history WHERE payment_id=? ORDER BY id");
                            foreach ($result as $row) {
                                $i++;
                                $stmt_history->execute(array($row['payment_id']));
                                $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
                                $pending = 0;
                                foreach ($history as $h) {
                                    if ($h['status'] == 'Pending') {
                                        $pending++;
                                    }
                                }
                                $balance = max(0, round($row['total_amount'] - $row['paid_amount'], 2));
                                $is_pos = (strpos($row['payment_id'], 'TXN-') === 0);
                                ?>
                                <tr class="<?php if($row['payment_status']=='Pending' || $pending > 0){echo 'bg-r';}else{echo 'bg-g';} ?>">
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <b>Id:</b> <?php echo $row['customer_id']; ?><br>
                                        <b>Name:</b><br> <?php echo $row['customer_name']; ?><br>
                                        <b>Email:</b><br> <?php echo $row['customer_email']; ?><br>
                                        <?php if(!empty($row['shipping_address'])): ?>
                                            <b>Ship to:</b><br>
                                            <?php echo htmlspecialchars($row['shipping_name']); ?> &middot; <?php echo htmlspecialchars($row['shipping_phone']); ?><br>
                                            <?php echo nl2br(htmlspecialchars($row['shipping_address'])); ?><br>
                                            <?php if($row['shipping_lat'] !== null && $row['shipping_lng'] !== null): ?>
                                                <a href="<?php echo maps_link($row['shipping_lat'], $row['shipping_lng']); ?>" target="_blank" class="btn btn-default btn-xs" style="margin-top:4px;"><i class="fa fa-map-marker"></i> View on Map</a>
                                            <?php else: ?>
                                                <small class="text-muted">(no GPS pin)</small>
                                            <?php endif; ?>
                                            <br><br>
                                        <?php else: ?>
                                            <?php
                                            $statement2 = $pdo->prepare("SELECT cust_s_address FROM tbl_customer WHERE cust_id=?");
                                            $statement2->execute(array($row['customer_id']));
                                            $result2 = $statement2->fetch(PDO::FETCH_ASSOC);
                                            if ($result2) {
                                                echo '<b>Billing:</b><br>' . $result2['cust_s_address'] . '<br><br>';
                                            } else {
                                                echo '<b>Billing:</b><br> Not available<br><br>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                        <!-- <a href="#" data-toggle="modal" data-target="#model-<?php echo $i; ?>"class="btn btn-success btn-xs" style="width:100%;margin-bottom:4px;">Send Message</a> -->
                                        <div id="model-<?php echo $i; ?>" class="modal fade" role="dialog">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        <h4 class="modal-title" style="font-weight: bold;">Send Message</h4>
                                                    </div>
                                                    <div class="modal-body" style="font-size: 14px">
                                                        <form action="" method="post">
                                                            <input type="hidden" name="cust_id" value="<?php echo $row['customer_id']; ?>">
                                                            <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <td>Subject</td>
                                                                    <td>
                                                                        <input type="text" name="subject_text" class="form-control" style="width: 100%;">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        Message
                                                                    </td>
                                                                    <td>
                                                                        <textarea name="message_text" class="form-control" cols="30" rows="10" style="width:100%;height: 200px;"></textarea>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td></td>
                                                                    <td><input type="submit" value="Send Message" name="form1"></td>
                                                                </tr>
                                                            </table>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
                                        $statement1->execute(array($row['payment_id']));
                                        $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($result1 as $row1) {
                                            echo '<b>Product:</b> '.$row1['product_name'];

                                            echo '<br>(<b>Quantity:</b> '.$row1['quantity'];
                                            echo ', <b>Unit Price:</b> '.peso($row1['unit_price']).')';
                                            echo '<br><br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <b>Payment Method:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
                                        <b>Payment Id:</b> <?php echo $row['payment_id']; ?><br>
                                        <b>Date:</b> <?php echo $row['payment_date']; ?><br>
                                        <b>Option:</b> <?php echo $row['payment_option']; ?><br>
                                        <?php if(count($history) == 0): ?>
                                            <small class="text-muted">Paid at the store (POS).</small>
                                        <?php endif; ?>
                                        <?php foreach($history as $h): ?>
                                            <div class="pay-entry">
                                                <b><?php echo htmlspecialchars($h['payment_type']); ?></b> &middot; <?php echo peso($h['amount']); ?> &middot; <?php echo htmlspecialchars($h['payment_method']); ?><br>
                                                <?php if($h['reference_no'] != ''): ?>
                                                    <b>Ref. No.:</b> <?php echo htmlspecialchars($h['reference_no']); ?><br>
                                                <?php endif; ?>
                                                <small><?php echo $h['submitted_at']; ?></small><br>
                                                <?php echo history_status_badge($h['status']); ?>
                                                <?php if($h['status'] == 'Confirmed' && $h['confirmed_at']): ?>
                                                    <small><?php echo $h['confirmed_at']; ?></small>
                                                <?php endif; ?>
                                                <?php if($h['remarks'] != ''): ?>
                                                    <br><small><i><?php echo htmlspecialchars($h['remarks']); ?></i></small>
                                                <?php endif; ?>
                                                <?php if($h['transaction_image'] != ''): ?>
                                                    <br><a href="../assets/uploads/<?php echo htmlspecialchars($h['transaction_image']); ?>" target="_blank" title="Open full image">
                                                        <img src="../assets/uploads/<?php echo htmlspecialchars($h['transaction_image']); ?>" alt="Transaction Image" style="max-width: 140px; max-height: 160px; border: 1px solid green; margin-top: 4px;"/>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($h['status'] == 'Pending'): ?>
                                                    <div style="margin-top:6px;">
                                                        <a href="payment-verify.php?task=confirm&id=<?php echo $h['id']; ?>" class="btn btn-success btn-xs"
                                                           onclick="return confirm('Confirm that <?php echo peso($h['amount']); ?> (Ref. No. <?php echo htmlspecialchars($h['reference_no']); ?>) was received in GCash?');">Confirm Payment</a>
                                                        <a href="#" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#reject-payment"
                                                           data-id="<?php echo $h['id']; ?>" data-info="<?php echo htmlspecialchars($h['payment_type'].' of '.peso($h['amount']).' - Order #'.$row['payment_id']); ?>">Reject</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <b>Total:</b> <?php echo peso($row['total_amount']); ?><br>
                                        <b>Paid:</b> <?php echo peso($row['paid_amount']); ?><br>
                                        <b>Balance:</b> <span style="color:<?php echo $balance > 0 ? '#c62828' : 'green'; ?>;font-weight:bold;"><?php echo peso($balance); ?></span>
                                    </td>
                                    <td>
                                        <?php echo payment_status_badge($row['payment_status'], $pending); ?>
                                        <br><br>
                                        <?php if(!$is_pos && $balance > 0): ?>
                                            <a href="#" class="btn btn-primary btn-xs" style="width:100%;margin-bottom:4px;" data-toggle="modal" data-target="#record-payment"
                                               data-payment-id="<?php echo $row['payment_id']; ?>" data-balance="<?php echo $balance; ?>">Record Cash Payment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo shipping_status_badge($row['shipping_status']); ?>
                                        <br><br>
                                        <?php
                                        if(in_array($row['payment_status'], array('Partially Paid', 'Completed'))) {
                                            if($row['shipping_status']=='Pending'){
                                                ?>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=To-Ship" class="btn btn-success btn-xs" style="width:100%;margin-bottom:4px;">Mark as To Ship</a>
                                                <?php
                                            }else if($row['shipping_status']=='To-Ship'){?>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Shipped" class="btn btn-primary btn-xs" style="width:100%;margin-bottom:4px;">Mark as Shipped</a>
                                            <?php }else if($row['shipping_status']=='Shipped'){?>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;"
                                                   <?php if($balance > 0): ?>onclick="return confirm('This order still has a balance of <?php echo peso($balance); ?>. Mark it as delivered? (Use Record Cash Payment for the balance collected.)');"<?php endif; ?>>Mark As Delivered</a>
                                            <?php }
                                        } else {
                                            echo '<small>Waiting for payment confirmation</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="print-invoice.php?payment_id=<?php echo $row['payment_id']; ?>" class="btn btn-info btn-xs" style="width:100%;margin-bottom:4px;" target="_blank">Print Invoice</a>
                                        <?php if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == 'superadmin'): ?>

                                        <a href="#" class="btn btn-danger btn-xs" data-href="order-delete.php?id=<?php echo $row['id']; ?>" data-toggle="modal" data-target="#confirm-delete" style="width:100%;">Delete</a>
                                    <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>


    </section>

    <style>
        .pay-entry { border-top: 1px dashed #aaa; margin-top: 6px; padding-top: 6px; }
    </style>

    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Delete Confirmation</h4>
                </div>
                <div class="modal-body">
                    Sure you want to delete this item?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger btn-ok">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject a submitted payment -->
    <div class="modal fade" id="reject-payment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="payment-verify.php" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Reject Payment</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task" value="reject">
                        <input type="hidden" name="id" id="reject-id" value="">
                        <p id="reject-info"></p>
                        <div class="form-group">
                            <label>Reason (shown to the customer) *</label>
                            <textarea name="reason" class="form-control" rows="3" required
                                      placeholder="e.g. The amount on the receipt does not match / reference number not found."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record a cash payment collected on delivery / pick-up -->
    <div class="modal fade" id="record-payment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="payment-verify.php" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Record Cash Payment</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task" value="record">
                        <input type="hidden" name="payment_id" id="record-payment-id" value="">
                        <p>Record the cash collected for <b>Order #<span id="record-order"></span></b>. Remaining balance: <b>&#8369;<span id="record-balance"></span></b></p>
                        <div class="form-group">
                            <label>Amount Received (&#8369;) *</label>
                            <input type="number" name="amount" id="record-amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php require_once('footer.php'); ?>

<script>
    $('#reject-payment').on('show.bs.modal', function(e) {
        var link = $(e.relatedTarget);
        $('#reject-id').val(link.data('id'));
        $('#reject-info').text(link.data('info'));
    });
    $('#record-payment').on('show.bs.modal', function(e) {
        var link = $(e.relatedTarget);
        var balance = parseFloat(link.data('balance'));
        $('#record-payment-id').val(link.data('payment-id'));
        $('#record-order').text(link.data('payment-id'));
        $('#record-balance').text(balance.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#record-amount').attr('max', balance).val(balance);
    });
</script>
