<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_cart = $row['banner_cart'];
}
?>

<?php
$error_message = '';
if(isset($_POST['form1'])) {

    $i = 0;
    $statement = $pdo->prepare("SELECT * FROM tbl_product");
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $i++;
        $table_product_id[$i] = $row['p_id'];
        $table_quantity[$i] = $row['p_qty'];
    }

    if(isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $i = 0;
        $arr1 = [];
        foreach($_POST['selected_items'] as $val) {
            $i++;
            $arr1[$i] = $val;
        }

        $quantities_by_id = [];
        if(isset($_POST['product_id']) && isset($_POST['quantity'])) {
            for($index = 0; $index < count($_POST['product_id']); $index++) {
                $quantities_by_id[$_POST['product_id'][$index]] = $_POST['quantity'][$index];
            }
        }

        $names_by_id = [];
        if(isset($_POST['product_id']) && isset($_POST['product_name'])) {
            for($index = 0; $index < count($_POST['product_id']); $index++) {
                $names_by_id[$_POST['product_id'][$index]] = $_POST['product_name'][$index];
            }
        }

        $arr2 = [];
        $arr3 = [];
        for($i = 1; $i <= count($arr1); $i++) {
            $pid = $arr1[$i];
            $arr2[$i] = isset($quantities_by_id[$pid]) ? $quantities_by_id[$pid] : 1;
            $arr3[$i] = isset($names_by_id[$pid]) ? $names_by_id[$pid] : '';
        }

        $allow_update = 1;
        for($i = 1; $i <= count($arr1); $i++) {
            for($j = 1; $j <= count($table_product_id); $j++) {
                if($arr1[$i] == $table_product_id[$j]) {
                    $temp_index = $j;
                    break;
                }
            }
            if($table_quantity[$temp_index] < $arr2[$i]) {
                $allow_update = 0;
                $error_message .= '"'.$arr2[$i].'" items are not available for "'.$arr3[$i].'"\n';
            } else {
                $session_index = array_search($arr1[$i], $_SESSION['cart_p_id']);
                if($session_index !== false){
                    $_SESSION['cart_p_qty'][$session_index] = $arr2[$i];
                }
            }
        }
        if($allow_update == 0) {
            ?>
            <script>alert('<?php echo $error_message; ?>');</script>
            <?php
        } else {
            ?>
            <script>alert('Selected Items Quantity Update is Successful!');</script>
            <?php
        }
    } else {
        ?>
        <script>alert('No items selected for update.');</script>
        <?php
    }
}

// New code here: handle proceed to checkout submission
if(isset($_POST['checkout'])) {
    if(isset($_POST['selected_items']) && is_array($_POST['selected_items']) && count($_POST['selected_items']) > 0) {
        // Save selected items in session
        $_SESSION['selected_items'] = $_POST['selected_items'];
        header('Location: checkout.php');
        exit;
    } else {
        ?>
        <script>alert('Please select at least one item to proceed to checkout.');</script>
        <?php
    }
}
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $banner_cart; ?>)">
    <div class="overlay"></div>
    <div class="page-banner-inner">
        <h1><?php echo "Cart"; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <?php if(!isset($_SESSION['cart_p_id'])): ?>
                    <?php echo '<h2 class="text-center">Cart is Empty!!</h2></br>'; ?>
                    <?php echo '<h4 class="text-center">Add products to the cart in order to view it here.</h4>'; ?>
                <?php else: ?>

                <?php
                // Initialize arrays to avoid "undefined variable" notice
                $arr_cart_p_id = [];
                $arr_cart_p_qty = [];
                $arr_cart_p_current_price = [];
                $arr_cart_p_name = [];
                $arr_cart_p_featured_photo = [];

                // Check if session variables exist before accessing them
                if (isset($_SESSION['cart_p_id'])) {
                    $i = 0;
                    foreach ($_SESSION['cart_p_id'] as $key => $value) {
                        $i++;
                        $arr_cart_p_id[$i] = $value;
                    }

                    $i = 0;
                    foreach ($_SESSION['cart_p_qty'] as $key => $value) {
                        $i++;
                        $arr_cart_p_qty[$i] = $value;
                    }

                    $i = 0;
                    foreach ($_SESSION['cart_p_current_price'] as $key => $value) {
                        $i++;
                        $arr_cart_p_current_price[$i] = $value;
                    }

                    $i = 0;
                    foreach ($_SESSION['cart_p_name'] as $key => $value) {
                        $i++;
                        $arr_cart_p_name[$i] = $value;
                    }

                    $i = 0;
                    foreach ($_SESSION['cart_p_featured_photo'] as $key => $value) {
                        $i++;
                        $arr_cart_p_featured_photo[$i] = $value;
                    }
                }
                ?>

                <form action="" method="post">
                <div class="cart">
                    <table class="table table-responsive table-hover table-bordered">
                        <tr>
                            <th><?php echo '#'; ?></th>
                            <th><?php echo "Cart"; ?></th>
                            <th><?php echo "Product Name"; ?></th>
                            <th><?php echo "Price"; ?></th>
                            <th><?php echo "Quantity"; ?></th>
                            <th class="text-right"><?php echo "Total"; ?></th>
                            <th class="text-center" style="width: 80px;"><?php echo "Select"; ?></th>
                            <th class="text-center" style="width: 100px;"><?php echo "Action"; ?></th>
                        </tr>
                        <?php
                        $table_total_price = 0;

                        $i=0;
                        foreach($arr_cart_p_id as $key => $value) 
                        {
                            $i++;
                            $arr_cart_p_id[$i] = $value;
                        }

                        $i = 0;
                        foreach($arr_cart_p_qty as $key => $value) 
                        {
                            $i++;
                            $arr_cart_p_qty[$i] = $value;
                        }

                        $i = 0;
                        foreach($arr_cart_p_current_price as $key => $value) 
                        {
                            $i++;
                            $arr_cart_p_current_price[$i] = $value;
                        }

                        $i = 0;
                        foreach($arr_cart_p_name as $key => $value) 
                        {
                            $i++;
                            $arr_cart_p_name[$i] = $value;
                        }

                        $i = 0;
                        foreach($arr_cart_p_featured_photo as $key => $value) 
                        {
                            $i++;
                            $arr_cart_p_featured_photo[$i] = $value;
                        }
                        ?>
                        <?php for($i=1;$i<=count($arr_cart_p_id);$i++): ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td>
                                <img src="assets/uploads/<?php echo $arr_cart_p_featured_photo[$i]; ?>" alt="">
                            </td>
                            <td><?php echo $arr_cart_p_name[$i]; ?></td>
                            <td><?php echo "₱"; ?><?php echo $arr_cart_p_current_price[$i]; ?></td>
                            <td>
                                <input type="hidden" name="product_id[]" value="<?php echo $arr_cart_p_id[$i]; ?>">
                                <input type="hidden" name="product_name[]" value="<?php echo $arr_cart_p_name[$i]; ?>">
                                <input type="number" class="input-text qty text" step="1" min="1" max="" name="quantity[]" value="<?php echo $arr_cart_p_qty[$i]; ?>" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
                            </td>
                            <td class="text-right">
                                <?php
                                $row_total_price = $arr_cart_p_current_price[$i]*$arr_cart_p_qty[$i];
                                $table_total_price = $table_total_price + $row_total_price;
                                ?>
                                <?php echo "₱"; ?><?php echo $row_total_price; ?>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="selected_items[]" value="<?php echo $arr_cart_p_id[$i]; ?>">
                            </td>
                            <td class="text-center">
                                <a onclick="return confirmDelete();" href="cart-item-delete.php?id=<?php echo $arr_cart_p_id[$i]; ?>" class="trash"><i class="fa fa-trash" style="color:red;"></i></a>
                            </td>
                        </tr>
                        <?php endfor; ?>
                        <tr>
                            <th colspan="7" class="total-text">Total</th>
                            <th class="total-amount"><?php echo "₱"; ?><?php echo $table_total_price; ?></th>
                            <th></th>
                        </tr>
                    </table> 
                </div>

                <div class="cart-buttons">
                    <ul>
                        <li><input type="submit" value="<?php echo "Update Cart"; ?>" class="btn btn-primary" name="form1"></li>
                        <li><a href="index.php" class="btn btn-primary"><?php echo "Continue Shopping"; ?></a></li>
                        <li><input type="submit" value="<?php echo "Proceed to Checkout"; ?>" class="btn btn-primary" name="checkout"></li>
                    </ul>
                </div>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
