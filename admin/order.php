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
                                <th>Paid Amount</th>
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
                            foreach ($result as $row) {
                                $i++;
                                ?>
                                <tr class="<?php if($row['payment_status']=='Pending'){echo 'bg-r';}else{echo 'bg-g';} ?>">
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <b>Id:</b> <?php echo $row['customer_id']; ?><br>
                                        <b>Name:</b><br> <?php echo $row['customer_name']; ?><br>
                                        <b>Email:</b><br> <?php echo $row['customer_email']; ?><br>
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
                                            echo ', <b>Unit Price:</b> '.$row1['unit_price'].')';
                                            echo '<br><br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($row['payment_method'] == 'PayPal'): ?>
                                            <b>Payment Method:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
                                            <b>Payment Id:</b> <?php echo $row['payment_id']; ?><br>
                                            <b>Date:</b> <?php echo $row['payment_date']; ?><br>
                                            <b>Transaction Id:</b> <?php echo $row['txnid']; ?><br>
                                        <?php elseif($row['payment_method'] == 'Gcash'): ?>
    <b>Payment Method:</b> <?php echo '<span style="color:red;"><b>'.$row['payment_method'].'</b></span>'; ?><br>
    <b>Payment Id:</b> <?php echo $row['payment_id']; ?><br>
    <b>Date:</b> <?php echo $row['payment_date']; ?><br>

    <b>Transaction Information:</b> <br><?php echo isset($row['bank_transaction_info']) ? $row['bank_transaction_info'] : 'N/A'; ?><br>
    <b>Transaction Image:</b><br>
    
    <!-- Try different image paths -->
    <?php if(!empty($row['transaction_image'])): ?>
        <?php
        // Check if the image file exists
        $img_path = $_SERVER['DOCUMENT_ROOT'] . '/Malou\'sGarden/assets/uploads/' . $row['transaction_image'];
        $img_path_alt = $_SERVER['DOCUMENT_ROOT'] . '/Malou%27sGarden/assets/uploads/' . $row['transaction_image'];
        ?>
        
        <!-- Direct link with hardcoded path for testing -->

        <!-- Relative path -->
        <img src="../assets/uploads/<?php echo htmlspecialchars($row['transaction_image']); ?>"
             alt="Transaction Image"
             style="max-width: 200px; max-height: 200px; border: 1px solid green;"/>
    <?php else: ?>
        <p>No image value available in 'transaction_image' field</p>
    <?php endif; ?>
    <br>
<?php endif; ?>
                                    </td>
                                    <td>₱ <?php echo $row['paid_amount']; ?></td>
                                    <td>
                                        <?php echo $row['payment_status']; ?>
                                        <br><br>
                                        <?php
                                        if($row['payment_status']=='Pending'){
                                            ?>
                                            <a href="order-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-success btn-xs" style="width:100%;margin-bottom:4px;">Mark Complete</a>
                                            
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $row['shipping_status']; ?>
                                        <br><br>
                                        <?php
                                        if($row['payment_status']=='Completed') {
                                            if($row['shipping_status']=='Pending'){
                                                ?>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;">Mark Complete</a>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=To-Ship" class="btn btn-success btn-xs" style="width:100%;margin-bottom:4px;">To-Ship</a>
                                                <?php
                                            }else if($row['shipping_status']=='To-Ship'){?>
                                                <a href="shipping-change-status.php?id=<?php echo $row['id']; ?>&task=Completed" class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;">Mark As Delivered</a>
                                                                                             
                                            <?php }
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


<?php require_once('footer.php'); ?>