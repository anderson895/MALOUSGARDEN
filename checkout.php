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

$items = get_selected_cart_items($pdo);
if(count($items) == 0) {
    header('location: cart.php');
    exit;
}

$table_total_price = 0;
foreach($items as $item) {
    $table_total_price += $item['total'];
}
$shipping_cost = SHIPPING_FEE;
$final_total = $table_total_price + $shipping_cost;
$down_payment = down_payment_amount($final_total);
?>

<style>
    .address-option { display: block; border: 1px solid #ddd; border-radius: 6px; padding: 12px 15px 12px 38px; margin-bottom: 10px; font-weight: normal; cursor: pointer; position: relative; background: #fff; }
    .address-option input[type=radio] { position: absolute; left: 14px; top: 15px; }
    .address-option.selected { border: 2px solid #2e7d32; background: #f4fbf4; }
    .address-option p { margin: 0 0 4px; }
    .gps-ok { color: #2e7d32; font-weight: bold; }
    .gps-missing { color: #c77700; font-weight: bold; }
    .pay-steps { background: #f4fbf4; border: 1px solid #c8e6c9; border-radius: 6px; padding: 12px 15px; margin-bottom: 15px; }
    .pay-steps h4 { margin-top: 0; color: #2e7d32; }
    .pay-steps ol { padding-left: 20px; margin-bottom: 0; }
    .pay-steps li { margin-bottom: 4px; }
    .pay-option { border: 1px solid #ddd; border-radius: 6px; padding: 8px 12px; margin: 0 0 8px; background: #fff; }
    .amount-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px 15px; margin-bottom: 15px; background: #fff; }
    .amount-box div { display: flex; justify-content: space-between; padding: 3px 0; }
    .amount-box .now { font-size: 18px; color: #2e7d32; border-top: 1px dashed #ccc; margin-top: 4px; padding-top: 8px; }
</style>

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

                <?php if(isset($_GET['error']) && $_GET['error'] != ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <?php if(!isset($_SESSION['customer'])): ?>
                    <p>
                        <p>If you don't have an account, <a href="registration.php" class="btn btn-sm btn-success">Sign Up</a></p>
                        <p>Already have an account? <a href="login.php" class="btn btn-sm btn-primary">Log In</a></p>
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
                            <?php foreach($items as $i => $item): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td>
                                        <img src="assets/uploads/<?php echo $item['photo']; ?>" alt=""/>
                                    </td>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo peso($item['price']); ?></td>
                                    <td><?php echo $item['qty']; ?></td>
                                    <td class="text-right"><?php echo peso($item['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <th colspan="5" class="total-text"><?php echo "Sub Total"; ?></th>
                                <th class="total-amount"><?php echo peso($table_total_price); ?></th>
                            </tr>
                            <tr>
                                <td colspan="5" class="total-text"><?php echo "Shipping Fee"; ?></td>
                                <td class="total-amount"><?php echo peso($shipping_cost); ?></td>
                            </tr>
                            <tr>
                                <th colspan="5" class="total-text"><?php echo "Total"; ?></th>
                                <th class="total-amount"><?php echo peso($final_total); ?></th>
                            </tr>
                        </table>
                    </div>

                    <?php $addresses = get_customer_addresses($pdo, $_SESSION['customer']['cust_id']); ?>
                    <form action="payment/bank/init.php" method="post" id="bank_form" enctype="multipart/form-data">
                    <div class="shipping-payment">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="special"><?php echo "Shipping Address"; ?></h3>
                                <?php if(count($addresses) == 0): ?>
                                    <div style="color:red;font-size:18px;margin-bottom:30px;">
                                        You don't have a shipping address yet. Please <a href="customer-billing-shipping-update.php?return=checkout" style="color:red;text-decoration:underline;">add your address</a> to continue.
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Your default address is selected. Choose another one if needed.</p>
                                    <?php foreach($addresses as $k => $a): ?>
                                        <label class="address-option <?php echo $k == 0 ? 'selected' : ''; ?>">
                                            <input type="radio" name="address_id" value="<?php echo $a['addr_id']; ?>" <?php echo $k == 0 ? 'checked' : ''; ?> required>
                                            <p>
                                                <b><?php echo htmlspecialchars($a['label']); ?></b>
                                                <?php if($a['is_default']): ?><span class="label label-success">Default</span><?php endif; ?>
                                            </p>
                                            <p><?php echo htmlspecialchars($a['full_name']); ?> &middot; <?php echo htmlspecialchars($a['phone']); ?></p>
                                            <p><?php echo htmlspecialchars(format_address($a)); ?></p>
                                            <?php if($a['latitude'] !== null && $a['longitude'] !== null): ?>
                                                <p class="gps-ok"><i class="fa fa-map-marker"></i> Pinned on map (GPS)</p>
                                            <?php else: ?>
                                                <p class="gps-missing"><i class="fa fa-exclamation-triangle"></i> No map pin</p>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                    <a href="customer-billing-shipping-update.php?return=checkout" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add new address</a>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <h3 class="special"><?php echo "Payment Section"; ?></h3>
                                <?php if(count($addresses) == 0): ?>
                                    <p class="text-muted">Add a shipping address first to continue with the payment.</p>
                                <?php else: ?>
                                    <div class="pay-steps">
                                        <h4><i class="fa fa-list-ol"></i> How to Pay</h4>
                                        <ol>
                                            <li>Choose <b>50% Down Payment</b> or <b>Full Payment</b>.</li>
                                            <li>Open GCash and scan the QR code (or send to the GCash number). Pay the exact <b>Amount to Pay Now</b>.</li>
                                            <li>Type the <b>GCash Reference No.</b> shown on your receipt.</li>
                                            <li>Upload the screenshot of your GCash receipt.</li>
                                            <li>Read and accept the Terms and Conditions, then click <b>Submit Confirmation</b>.</li>
                                            <li>We will verify your payment. Follow the status in <b>My Orders</b>.</li>
                                        </ol>
                                    </div>

                                    <div class="form-group">
                                        <label for=""><?php echo "Payment Method"; ?></label>
                                        <input type="text" class="form-control" value="GCash" readonly>
                                        <input type="hidden" name="payment_method" value="Gcash">
                                    </div>

                                    <div class="form-group">
                                        <label for=""><?php echo "Payment Option"; ?> *</label>
                                        <div class="radio pay-option">
                                            <label>
                                                <input type="radio" name="payment_option" value="down" checked>
                                                <b>50% Down Payment</b> &ndash; pay <?php echo peso($down_payment); ?> now, the <?php echo peso($final_total - $down_payment); ?> balance later (GCash or cash upon delivery)
                                            </label>
                                        </div>
                                        <div class="radio pay-option">
                                            <label>
                                                <input type="radio" name="payment_option" value="full">
                                                <b>Full Payment</b> &ndash; pay <?php echo peso($final_total); ?> now
                                            </label>
                                        </div>
                                    </div>

                                    <div class="amount-box">
                                        <div><span>Order Total</span><b><?php echo peso($final_total); ?></b></div>
                                        <div><span>Remaining Balance</span><b id="balance-after"><?php echo peso($final_total - $down_payment); ?></b></div>
                                        <div class="now"><span>Amount to Pay Now</span><b id="amount-now"><?php echo peso($down_payment); ?></b></div>
                                    </div>

                                    <!-- GCash Payment Info Section -->
                                    <div id="gcashPaymentInfo" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                                        <h4 style="margin-top: 0; color: #2c3e50;">GCash Payment Details</h4>
                                        <p>Scan the QR code or send your payment to the GCash number below.</p>
                                        <div style="display: flex; margin-bottom: 15px;">
                                            <div style="flex: 1;">
                                                <p style="margin-bottom: 5px;"><strong>GCash Name:</strong> MA*K TR****N M.</p>
                                                <p style="margin-bottom: 5px;"><strong>GCash Number:</strong> 09511717939</p>
                                            </div>
                                            <div style="flex: 1; text-align: center;">
                                                <img src="gcash.jpg" alt="GCash QR Code" style="max-width: 150px; border: 1px solid #ddd;">
                                                <p style="margin-top: 5px; font-size: 12px;">Scan to pay</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for=""><?php echo "GCash Reference No."; ?> *</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="e.g. 1012 345 678901" maxlength="30"
                                               pattern="[A-Za-z0-9 \-]{6,30}" title="Enter the reference number shown on your GCash receipt" required>
                                    </div>

                                    <div class="form-group">
                                        <label for=""><?php echo "Transaction Image"; ?> * <br><span style="font-size:12px;font-weight:normal;">(<?php echo "Upload the screenshot of your GCash receipt"; ?>)</span></label>
                                        <input type="file" name="transaction_image" class="form-control" id="transactionImage" accept="image/*" required>
                                        <div id="imagePreview" style="margin-top: 10px;">
                                            <img id="previewImg" src="#" alt="Image Preview" style="display: none; width: 300px; height: auto; max-width: 100%; border: 1px solid #ccc;">
                                        </div>
                                    </div>

                                    <!-- Terms & Conditions Agreement -->
                                    <div class="form-group">
                                        <input type="checkbox" id="agreeCheck" name="agree_terms" value="1" disabled required>
                                        <label for="agreeCheck">
                                            I have read and agree to the
                                            <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a>
                                        </label>
                                        <br><small class="text-muted">Open the Terms and Conditions and click "I Accept" to continue.</small>
                                    </div>

                                    <!-- Submit Confirmation Button -->
                                    <div class="form-group">
                                        <input type="submit" class="btn btn-primary" value="<?php echo 'Submit Confirmation'; ?>" name="form3" id="submitButton" disabled>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </form>

                    <!-- Terms and Conditions Modal -->
                    <div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="termsModalLabel">Terms and Conditions</h4>
                                </div>

                                <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                                    <p><strong>Payment Terms & Conditions</strong></p>
                                    <p><strong>1. Payment Options</strong><br>
                                    Payments are made through GCash. You may pay a 50% down payment or the full amount. A 50% down payment is required to confirm your order. The remaining balance can be paid anytime through GCash from your order page in My Orders, or in cash upon delivery.</p>

                                    <p><strong>2. Payment Verification</strong><br>
                                    Every payment is verified by our staff using the GCash reference number and the receipt screenshot you upload. A payment with a wrong amount, an invalid reference number or an unclear screenshot will be rejected, and you will be asked to submit a new payment confirmation. You can see the status of each payment in My Orders.</p>

                                    <p><strong>3. Shipping Fee and Address</strong><br>
                                    A flat shipping fee of <?php echo peso(SHIPPING_FEE); ?> is added to every order. Please make sure your shipping address, mobile number and map pin are correct to avoid delivery delays.</p>

                                    <p><strong>4. Cancellation Policy</strong><br>
                                    You may cancel your order while your payment is still for verification. Once your payment has been confirmed by the admin, cancellation will no longer be allowed. To cancel, contact us through the Get In Touch page.</p>

                                    <p><strong>5. Delivery Schedule</strong><br>
                                    Standard delivery timeframe is within 2&ndash;3 working days after the down payment is confirmed.<br>
                                    You can follow the progress of your order in its timeline under My Orders.</p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" id="acceptTerms" class="btn btn-success" data-dismiss="modal">I Accept</button>
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
    $(function() {
        var total = <?php echo json_encode((float)$final_total); ?>;
        var downPayment = <?php echo json_encode((float)$down_payment); ?>;

        function peso(n) {
            return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Amount to pay now / remaining balance follow the chosen payment option
        function updateAmounts() {
            var now = $('input[name="payment_option"]:checked').val() === 'full' ? total : downPayment;
            $('#amount-now').text(peso(now));
            $('#balance-after').text(peso(total - now));
        }
        $('input[name="payment_option"]').on('change', updateAmounts);
        updateAmounts();

        // Highlight the chosen shipping address
        $('input[name="address_id"]').on('change', function() {
            $('.address-option').removeClass('selected');
            $(this).closest('.address-option').addClass('selected');
        });

        // Terms: the box unlocks after "I Accept"; the button follows the box
        $('#acceptTerms').on('click', function() {
            $('#agreeCheck').prop('disabled', false).prop('checked', true).trigger('change');
        });
        $('#agreeCheck').on('change', function() {
            $('#submitButton').prop('disabled', !this.checked);
        });

        // Preview the uploaded receipt
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

        $('#bank_form').on('submit', function(e) {
            if (!confirm('Submit your payment confirmation?\n\nPlease make sure the amount you sent and the GCash reference number are correct.')) {
                e.preventDefault();
                return;
            }
            $('#submitButton').prop('disabled', true).val('Submitting...');
        });
    });
</script>
