<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $banner_checkout = $row['banner_checkout'];
}
?>

<?php
if(!isset($_SESSION['cart_p_id'])) {
    header('location: cart.php');
    exit;
}

if(!isset($_SESSION['selected_items']) || !is_array($_SESSION['selected_items']) || count($_SESSION['selected_items']) == 0) {
    header('location: cart.php');
    exit;
}
$selected_items = $_SESSION['selected_items'];
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $banner_checkout; ?>)">
    <div class="overlay"></div>
    <div class="page-banner-inner">
        <h1><?php echo "Checkout"; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <?php if(!isset($_SESSION['customer'])): ?>
                    <p>
                        <a href="login.php" class="btn btn-md btn-danger"><?php echo "Please login as customer to checkout"; ?></a>
                    </p>
                <?php else: ?>

                    <h3 class="special"><?php echo "Order Details"; ?></h3>
                    <div class="cart">
                        <table class="table table-responsive table-hover table-bordered">
                            <tr>
                                <th>#</th>
                                <th><?php echo "Photo"; ?></th>
                                <th><?php echo "Product Name"; ?></th>
                                <th><?php echo "Price"; ?></th>
                                <th><?php echo "Quantity"; ?></th>
                                <th class="text-right"><?php echo "Total"; ?></th>
                            </tr>
                            <?php
                            $table_total_price = 0;

                            $i=0;
                            foreach($_SESSION['cart_p_id'] as $key => $value) {
                                $i++;
                                $arr_cart_p_id[$i] = $value;
                            }
                            $i=0;
                            foreach($_SESSION['cart_p_qty'] as $key => $value) {
                                $i++;
                                $arr_cart_p_qty[$i] = $value;
                            }
                            $i=0;
                            foreach($_SESSION['cart_p_current_price'] as $key => $value) {
                                $i++;
                                $arr_cart_p_current_price[$i] = $value;
                            }
                            $i=0;
                            foreach($_SESSION['cart_p_name'] as $key => $value) {
                                $i++;
                                $arr_cart_p_name[$i] = $value;
                            }
                            $i=0;
                            foreach($_SESSION['cart_p_featured_photo'] as $key => $value) {
                                $i++;
                                $arr_cart_p_featured_photo[$i] = $value;
                            }
                            ?>
                            <?php
                            $count_selected_items = 0;
                            for($idx=1; $idx<=count($arr_cart_p_id); $idx++):
                                if(in_array($arr_cart_p_id[$idx], $selected_items)):
                                    $count_selected_items++;
                                    $row_total_price = $arr_cart_p_current_price[$idx]*$arr_cart_p_qty[$idx];
                                    $table_total_price += $row_total_price;
                                    ?>
                                    <tr>
                                        <td><?php echo $count_selected_items; ?></td>
                                        <td>
                                            <img src="assets/uploads/<?php echo $arr_cart_p_featured_photo[$idx]; ?>" alt=""/>
                                        </td>
                                        <td><?php echo $arr_cart_p_name[$idx]; ?></td>
                                        <td><?php echo "₱"; ?><?php echo $arr_cart_p_current_price[$idx]; ?></td>
                                        <td><?php echo $arr_cart_p_qty[$idx]; ?></td>
                                        <td class="text-right">
                                            <?php echo "₱"; ?><?php echo $row_total_price; ?>
                                        </td>
                                    </tr>
                                <?php
                                endif;
                            endfor;
                            ?>
                            <tr>
                                <th colspan="7" class="total-text"><?php echo "Sub Total"; ?></th>
                                <th class="total-amount"><?php echo "₱"; ?><?php echo $table_total_price; ?></th>
                            </tr>
                            <tr id="shipping-row">
                                <td colspan="7" class="total-text"><?php echo "Shipping Cost"; ?></td>
                                <td class="total-amount"><?php echo "₱"; ?><?php echo 100; ?></td>
                            </tr>
                            <tr>
                                <th colspan="7" class="total-text"><?php echo "Total"; ?></th>
                                <th class="total-amount" id="final-total">
                                    <?php
                                    $shipping_cost = 100;
                                    $final_total = $table_total_price + $shipping_cost;
                                    ?>
                                    <?php echo "₱"; ?><?php echo $final_total; ?>
                                </th>
                            </tr>

                        </table>
                    </div>
                    <script>
                        $(document).ready(function() {
                            $('#submitButton').on('click', function(e) {
                                e.preventDefault();
                                var confirmation = confirm("Are you sure you want to proceed with the payment?");
                                if (confirmation) {
                                    document.getElementById('bank_form').submit();
                                }
                            });

                            // Show/hide payment info based on selected payment method
                            $('#advFieldsStatus').change(function() {
                                var selectedMethod = $(this).val();
                                var tableTotal = <?php echo $table_total_price; ?>;
                                var shippingCost = 100;

                                // Hide all payment info sections first
                                $('#gcashPaymentInfo').hide();
                                $('#pickupPaymentInfo').hide();

                                // Show the appropriate payment info section
                                if (selectedMethod === 'Gcash') {
                                    $('#gcashPaymentInfo').show();
                                    $('#shipping-row').show();
                                    var finalTotal = tableTotal + shippingCost;
                                    $('#final-total').html('₱' + finalTotal);
                                    $('input[name="amount"]').val(finalTotal);
                                } else if (selectedMethod === 'Pick up') {
                                    $('#pickupPaymentInfo').show();
                                    $('#shipping-row').hide();
                                    $('#final-total').html('₱' + tableTotal);
                                    $('input[name="amount"]').val(tableTotal);
                                }

                                $('#bank_form').attr('action', 'payment/bank/init.php');
                            });

                            $('#advFieldsStatus').select2({
                                minimumResultsForSearch: Infinity
                            });
                        });
                    </script>
                    <div class="shipping-payment">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="special"><?php echo "Shipping Address"; ?></h3>
                                <table class="table table-responsive table-bordered table-hover table-striped bill-address">
                                    <tr>
                                        <td><?php echo "Full Name"; ?></td>
                                        <td><?php echo $_SESSION['customer']['cust_s_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo "Phone Number"; ?></td>
                                        <td><?php echo $_SESSION['customer']['cust_s_phone']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo "Address"; ?></td>
                                        <td><?php echo nl2br($_SESSION['customer']['cust_s_address']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo "City"; ?></td>
                                        <td><?php echo $_SESSION['customer']['cust_s_city']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo "State"; ?></td>
                                        <td><?php echo $_SESSION['customer']['cust_s_state']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo "Zip Code"; ?></td>
                                        <td><?php echo $_SESSION['customer']['cust_s_zip']; ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h3 class="special"><?php echo "Payment Section"; ?></h3>
                                <div class="row">
                                    <?php
                                    $checkout_access = 1;
                                    if(
                                        ($_SESSION['customer']['cust_s_name']=='') ||
                                        ($_SESSION['customer']['cust_s_phone']=='') ||
                                        ($_SESSION['customer']['cust_s_address']=='') ||
                                        ($_SESSION['customer']['cust_s_city']=='') ||
                                        ($_SESSION['customer']['cust_s_state']=='') ||
                                        ($_SESSION['customer']['cust_s_zip']=='')
                                    ) {
                                        $checkout_access = 0;
                                    }
                                    ?>
                                    <?php if($checkout_access == 0): ?>
                                        <div class="col-md-12">
                                            <div style="color:red;font-size:22px;margin-bottom:50px;">
                                                You must have to fill up all the shipping information from your dashboard panel to checkout the order. Please fill up the information going to <a href="customer-billing-shipping-update.php" style="color:red;text-decoration:underline;">this link</a>.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <form action="payment/bank/init.php" method="post" id="bank_form" enctype="multipart/form-data">
                                            <div class="col-md-12 form-group">
                                                <label for=""><?php echo "Select Payment Method"; ?> *</label>
                                                <select name="payment_method" class="form-control select2" id="advFieldsStatus" required>
                                                    <option value=""><?php echo "Select a Method"; ?></option>
                                                    <option value="Pick up">Pick up</option>
                                                    <option value="Gcash">Gcash</option>
                                                </select>
                                            </div>
                                            <input type="hidden" name="amount" value="<?php echo $final_total; ?>">

                                            <?php
                                            $selected_product_ids = [];
                                            $selected_product_names = [];
                                            foreach ($selected_items as $sel) {
                                                $idx = array_search($sel, $arr_cart_p_id);
                                                if ($idx !== false) {
                                                    $selected_product_ids[] = $arr_cart_p_id[$idx];
                                                    $selected_product_names[] = $arr_cart_p_name[$idx];
                                                }
                                            }
                                            ?>
                                            <input type="hidden" name="product_ids" value="<?php echo implode(',', $selected_product_ids); ?>">
                                            <input type="hidden" name="product_names" value="<?php echo implode(',', $selected_product_names); ?>">

                                            <!-- GCash Payment Info Section -->
                                            <div id="gcashPaymentInfo" style="display: none; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                                                <h4 style="margin-top: 0; color: #2c3e50;">GCash Payment Details</h4>
                                                <p>Please send payment using GCash and upload the transaction screenshot below.</p>
<!--                                                <p><strong>50% down payment required</strong></p>-->
                                                <div style="display: flex; margin-bottom: 15px;">
                                                    <div style="flex: 1;">
                                                        <p style="margin-bottom: 5px;"><strong>GCash Name:</strong> MA*K TR****N M.</p>
                                                        <p style="margin-bottom: 5px;"><strong>GCash Number:</strong> 09511717939</p>
                                                    </div>
                                                    <div style="flex: 1; text-align: center;">
                                                        <!-- Replace with your actual QR code image -->
                                                        <img src="gcash.jpg" alt="GCash QR Code" style="max-width: 150px; border: 1px solid #ddd;">
                                                        <p style="margin-top: 5px; font-size: 12px;">Scan to pay</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pick-up Payment Info Section -->
                                            <div id="pickupPaymentInfo" style="display: none; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                                                <h4 style="margin-top: 0; color: #2c3e50;">Pick-up Payment Details</h4>
                                                <p>For pick-up orders, you can pay in cash upon collection.</p>
                                                <p><strong>or you can pay 50% down payment via GCash:</strong></p>
                                                <div style="display: flex; margin-bottom: 15px;">
                                                    <div style="flex: 1;">
                                                        <p style="margin-bottom: 5px;"><strong>GCash Name:</strong> MA*K TR****N M.</p>
                                                        <p style="margin-bottom: 5px;"><strong>GCash Number:</strong> 09511717939</p>
                                                    </div>
                                                    <div style="flex: 1; text-align: center;">
                                                        <!-- Replace with your actual QR code image -->
                                                        <img src="gcash.jpg" alt="GCash QR Code" style="max-width: 150px; border: 1px solid #ddd;">
                                                        <p style="margin-top: 5px; font-size: 12px;">Scan to pay</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12 form-group">
                                                <label for=""><?php echo "Transaction Image"; ?> <br><span style="font-size:12px;font-weight:normal;">(<?php echo "Include transaction image for confirmation"; ?>)</span></label>
                                                <input type="file" name="transaction_image" class="form-control" id="transactionImage" accept="image/*" required>
                                                <div id="imagePreview" style="margin-top: 10px;">
                                                    <img id="previewImg" src="#" alt="Image Preview" style="display: none; width: 300px; height: auto; max-width: 100%; border: 1px solid #ccc;">
                                                </div>
                                            </div>

                                            <script>
                                                document.getElementById('transactionImage').addEventListener('change', function (event) {
                                                    var file = event.target.files[0];
                                                    var reader = new FileReader();

                                                    reader.onload = function (e) {
                                                        var previewImg = document.getElementById('previewImg');
                                                        previewImg.style.display = 'block';
                                                        previewImg.src = e.target.result;
                                                    };

                                                    if (file) {
                                                        reader.readAsDataURL(file);
                                                    }
                                                });
                                            </script>

                                            <div class="col-md-12 form-group">
                                                <input type="submit" class="btn btn-primary" value="<?php echo "Pay Now"; ?>" name="form3" id="submitButton">
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

<script>
    $(document).ready(function() {
        $('#submitButton').on('click', function(e) {
            e.preventDefault();
            var confirmation = confirm("Are you sure you want to proceed with the payment?");
            if (confirmation) {
                document.getElementById('bank_form').submit();
            }
        });

        // Show/hide payment info based on selected payment method
        $('#advFieldsStatus').change(function() {
            var selectedMethod = $(this).val();

            // Hide all payment info sections first
            $('#gcashPaymentInfo').hide();
            $('#pickupPaymentInfo').hide();

            // Show the appropriate payment info section
            if (selectedMethod === 'Gcash') {
                $('#gcashPaymentInfo').show();
            } else if (selectedMethod === 'Pick up') {
                $('#pickupPaymentInfo').show();
            }

            $('#bank_form').attr('action', 'payment/bank/init.php');
        });

        $('#advFieldsStatus').select2({
            minimumResultsForSearch: Infinity
        });
    });
</script>