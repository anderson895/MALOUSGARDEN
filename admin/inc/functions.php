<?php

if (!function_exists('get_ext')) {
    function get_ext($pdo, $fname)
    {
        $up_filename = $_FILES[$fname]["name"];
        $file_basename = substr($up_filename, 0, strripos($up_filename, '.')); // strip extension
        $file_ext = substr($up_filename, strripos($up_filename, '.')); // get extension
        return $file_ext;
    }
}

if (!function_exists('ext_check')) {
    function ext_check($pdo, $allowed_ext, $my_ext)
    {
        $arr1 = explode("|", $allowed_ext);
        $count_arr1 = count($arr1);

        for ($i = 0; $i < $count_arr1; $i++) {
            $arr1[$i] = '.' . $arr1[$i];
        }

        $stat = 0;
        for ($i = 0; $i < $count_arr1; $i++) {
            if ($my_ext === $arr1[$i]) {
                $stat = 1;
                break;
            }
        }

        return $stat === 1;
    }
}

if (!function_exists('get_ai_id')) {
    function get_ai_id($pdo, $tbl_name)
    {
        $statement = $pdo->prepare("SHOW TABLE STATUS LIKE :table");
        $statement->execute(['table' => $tbl_name]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return isset($result['Auto_increment']) ? $result['Auto_increment'] : null;
    }
}

// ---------------------------------------------------------------------
// Ordering, payment and address helpers (panel revisions)
// ---------------------------------------------------------------------

if (!defined('SHIPPING_FEE')) define('SHIPPING_FEE', 100);
if (!defined('DOWN_PAYMENT_RATE')) define('DOWN_PAYMENT_RATE', 0.5);
if (!defined('LOW_STOCK_LEVEL')) define('LOW_STOCK_LEVEL', 5);
if (!defined('OPTION_DOWN_PAYMENT')) define('OPTION_DOWN_PAYMENT', 'Down Payment (50%)');
if (!defined('OPTION_FULL_PAYMENT')) define('OPTION_FULL_PAYMENT', 'Full Payment');

if (!function_exists('peso')) {
    function peso($amount)
    {
        return '₱' . number_format((float)$amount, 2);
    }
}

if (!function_exists('down_payment_amount')) {
    function down_payment_amount($total)
    {
        return ceil($total * DOWN_PAYMENT_RATE);
    }
}

if (!function_exists('address_street_part')) {
    // Everything before the city: address lines and barangay.
    function address_street_part($a)
    {
        $barangay = trim($a['barangay']);
        if ($barangay != '' && !preg_match('/^(brgy|barangay)\b/i', $barangay)) {
            $barangay = 'Brgy. ' . $barangay;
        }
        $parts = array_map('trim', array($a['address_line1'], $a['address_line2'], $barangay));
        return implode(', ', array_filter($parts, 'strlen'));
    }
}

if (!function_exists('format_address')) {
    function format_address($a)
    {
        $parts = array(address_street_part($a), trim($a['city']), trim($a['province'] . ' ' . $a['zip']));
        return implode(', ', array_filter($parts, 'strlen'));
    }
}

if (!function_exists('maps_link')) {
    function maps_link($lat, $lng)
    {
        return 'https://www.google.com/maps?q=' . urlencode($lat . ',' . $lng);
    }
}

if (!function_exists('get_customer_addresses')) {
    function get_customer_addresses($pdo, $cust_id)
    {
        $statement = $pdo->prepare("SELECT * FROM tbl_customer_address WHERE cust_id=? ORDER BY is_default DESC, addr_id DESC");
        $statement->execute(array($cust_id));
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('sync_default_address')) {
    // Makes sure the customer has exactly one default address and copies it
    // into the legacy tbl_customer.cust_s_* fields (and the session copy).
    function sync_default_address($pdo, $cust_id)
    {
        $statement = $pdo->prepare("SELECT addr_id FROM tbl_customer_address WHERE cust_id=? AND is_default=1 ORDER BY addr_id DESC");
        $statement->execute(array($cust_id));
        $defaults = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (count($defaults) != 1) {
            $statement = $pdo->prepare("UPDATE tbl_customer_address SET is_default=0 WHERE cust_id=?");
            $statement->execute(array($cust_id));
            $keep = count($defaults) ? $defaults[0] : null;
            if ($keep === null) {
                $statement = $pdo->prepare("SELECT MAX(addr_id) FROM tbl_customer_address WHERE cust_id=?");
                $statement->execute(array($cust_id));
                $keep = $statement->fetchColumn();
            }
            if ($keep) {
                $statement = $pdo->prepare("UPDATE tbl_customer_address SET is_default=1 WHERE addr_id=?");
                $statement->execute(array($keep));
            }
        }

        $statement = $pdo->prepare("SELECT * FROM tbl_customer_address WHERE cust_id=? AND is_default=1");
        $statement->execute(array($cust_id));
        $a = $statement->fetch(PDO::FETCH_ASSOC);
        $fields = array(
            'cust_s_name'    => $a ? $a['full_name'] : '',
            'cust_s_phone'   => $a ? $a['phone'] : '',
            'cust_s_address' => $a ? address_street_part($a) : '',
            'cust_s_city'    => $a ? $a['city'] : '',
            'cust_s_state'   => $a ? $a['province'] : '',
            'cust_s_zip'     => $a ? $a['zip'] : '',
        );
        $statement = $pdo->prepare("UPDATE tbl_customer SET cust_s_name=?, cust_s_phone=?, cust_s_address=?, cust_s_city=?, cust_s_state=?, cust_s_zip=? WHERE cust_id=?");
        $statement->execute(array_merge(array_values($fields), array($cust_id)));

        if (isset($_SESSION['customer']['cust_id']) && $_SESSION['customer']['cust_id'] == $cust_id) {
            foreach ($fields as $key => $value) {
                $_SESSION['customer'][$key] = $value;
            }
        }
    }
}

if (!function_exists('get_selected_cart_items')) {
    // The cart lines chosen for checkout, priced from tbl_product so the
    // amount shown and the amount recorded always match.
    function get_selected_cart_items($pdo)
    {
        $items = array();
        if (empty($_SESSION['cart_p_id']) || empty($_SESSION['selected_items'])) {
            return $items;
        }
        $statement = $pdo->prepare("SELECT p_name, p_current_price, p_qty, p_featured_photo FROM tbl_product WHERE p_id=?");
        foreach ($_SESSION['cart_p_id'] as $key => $p_id) {
            if (!in_array($p_id, $_SESSION['selected_items'])) {
                continue;
            }
            $statement->execute(array($p_id));
            $product = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                continue;
            }
            $qty = max(1, (int)$_SESSION['cart_p_qty'][$key]);
            $price = (float)$product['p_current_price'];
            $items[] = array(
                'id'    => $p_id,
                'name'  => $product['p_name'],
                'photo' => $product['p_featured_photo'],
                'qty'   => $qty,
                'stock' => (int)$product['p_qty'],
                'price' => $price,
                'total' => $price * $qty,
            );
        }
        return $items;
    }
}

if (!function_exists('clean_reference_no')) {
    // Returns the trimmed GCash reference number, or '' when it looks invalid.
    function clean_reference_no($ref)
    {
        $ref = trim(preg_replace('/\s+/', ' ', strip_tags((string)$ref)));
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9 \-]{4,28}[A-Za-z0-9]$/', $ref) ? $ref : '';
    }
}

if (!function_exists('upload_transaction_image')) {
    // Validates and stores an uploaded payment screenshot. Returns the new
    // file name, or false with $error set.
    function upload_transaction_image($file, $upload_dir, &$error)
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload the screenshot of your GCash payment.';
            return false;
        }
        if (getimagesize($file['tmp_name']) === false) {
            $error = 'The uploaded file is not an image.';
            return false;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
            $error = 'Only JPG, JPEG, PNG and GIF images are allowed.';
            return false;
        }
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $name = uniqid('transaction_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $name)) {
            $error = 'Failed to save the uploaded image. Please try again.';
            return false;
        }
        return $name;
    }
}

if (!function_exists('add_order_timeline')) {
    function add_order_timeline($pdo, $payment_id, $title, $description = '')
    {
        $statement = $pdo->prepare("INSERT INTO tbl_order_timeline (payment_id, title, description, created_at) VALUES (?,?,?,?)");
        $statement->execute(array($payment_id, $title, $description, date('Y-m-d H:i:s')));
    }
}

if (!function_exists('count_pending_payments')) {
    function count_pending_payments($pdo, $payment_id)
    {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment_history WHERE payment_id=? AND status='Pending'");
        $statement->execute(array($payment_id));
        return (int)$statement->fetchColumn();
    }
}

if (!function_exists('recalc_order_payment')) {
    // Re-totals an online order from its confirmed payments and returns
    // array(total, paid, balance, status). POS sales (several rows per
    // payment_id) are always fully paid and are left alone.
    function recalc_order_payment($pdo, $payment_id)
    {
        if (strpos($payment_id, 'TXN-') === 0) {
            return null;
        }
        $statement = $pdo->prepare("SELECT total_amount FROM tbl_payment WHERE payment_id=?");
        $statement->execute(array($payment_id));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) != 1) {
            return null;
        }
        $total = round((float)$rows[0]['total_amount'], 2);

        $statement = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM tbl_payment_history WHERE payment_id=? AND status='Confirmed'");
        $statement->execute(array($payment_id));
        $paid = round((float)$statement->fetchColumn(), 2);

        if ($paid <= 0) {
            $status = 'Pending';
        } elseif ($paid >= $total) {
            $status = 'Completed';
        } else {
            $status = 'Partially Paid';
        }

        $statement = $pdo->prepare("UPDATE tbl_payment SET paid_amount=?, payment_status=? WHERE payment_id=?");
        $statement->execute(array($paid, $status, $payment_id));

        return array('total' => $total, 'paid' => $paid, 'balance' => max(0, round($total - $paid, 2)), 'status' => $status);
    }
}

if (!function_exists('payment_status_badge')) {
    function payment_status_badge($status, $pending = 0)
    {
        if ($status == 'Completed') {
            return '<span class="label label-success">Fully Paid</span>';
        }
        if ($status == 'Partially Paid') {
            $html = '<span class="label label-primary">Partially Paid (Down Payment)</span>';
            if ($pending > 0) {
                $html .= ' <span class="label label-warning">Balance payment for verification</span>';
            }
            return $html;
        }
        if ($pending > 0) {
            return '<span class="label label-warning">For Verification</span>';
        }
        // The storefront theme recolours label-danger green, so red is set inline
        return '<span class="label label-danger" style="background-color:#c62828;">Unpaid</span>';
    }
}

if (!function_exists('shipping_status_badge')) {
    function shipping_status_badge($status)
    {
        $map = array(
            'Pending'   => array('default', 'Pending'),
            'To-Ship'   => array('info', 'To Ship'),
            'Shipped'   => array('primary', 'Shipped'),
            'Completed' => array('success', 'Delivered'),
        );
        $s = isset($map[$status]) ? $map[$status] : array('default', $status);
        return '<span class="label label-' . $s[0] . '">' . htmlspecialchars($s[1]) . '</span>';
    }
}

if (!function_exists('get_inventory_report')) {
    // Stock on hand and sales per product, for the inventory report page and
    // its PDF. $stock is all|in|low|out; dates are Y-m-d.
    function get_inventory_report($pdo, $from, $to, $category = 0, $stock = 'all')
    {
        // Items of paid orders (down payment or full) in the period. tbl_payment is
        // grouped per payment_id first: a POS sale has one row per item, and a plain
        // join would count its items several times.
        $statement = $pdo->prepare("SELECT o.product_id,
                                           SUM(CAST(o.quantity AS UNSIGNED)) AS units_sold,
                                           SUM(CAST(o.quantity AS DECIMAL(10,2)) * CAST(o.unit_price AS DECIMAL(10,2))) AS sales
                                      FROM tbl_order o
                                      JOIN (SELECT payment_id FROM tbl_payment
                                             WHERE payment_status IN ('Partially Paid','Completed')
                                               AND payment_date >= ? AND payment_date <= ?
                                             GROUP BY payment_id) p ON p.payment_id = o.payment_id
                                     GROUP BY o.product_id");
        $statement->execute(array($from . ' 00:00:00', $to . ' 23:59:59'));
        $sales = array();
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sales[$row['product_id']] = $row;
        }

        $sql = "SELECT p.p_id, p.p_name, p.p_current_price, p.p_qty, c.tcat_name
                  FROM tbl_product p JOIN tbl_category c ON c.tcat_id = p.tcat_id";
        $params = array();
        if ($category) {
            $sql .= " WHERE p.tcat_id = ?";
            $params[] = $category;
        }
        $statement = $pdo->prepare($sql . " ORDER BY c.tcat_name, p.p_name");
        $statement->execute($params);

        $wanted = array('in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock');
        $rows = array();
        $summary = array('products' => 0, 'stock_units' => 0, 'stock_value' => 0, 'low' => 0, 'out' => 0, 'units_sold' => 0, 'sales' => 0);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $qty = (int)$p['p_qty'];
            if ($qty <= 0) {
                $status = 'Out of Stock';
            } elseif ($qty <= LOW_STOCK_LEVEL) {
                $status = 'Low Stock';
            } else {
                $status = 'In Stock';
            }
            if (isset($wanted[$stock]) && $wanted[$stock] != $status) {
                continue;
            }
            $price = (float)$p['p_current_price'];
            $row = array(
                'id'          => $p['p_id'],
                'name'        => $p['p_name'],
                'category'    => $p['tcat_name'],
                'price'       => $price,
                'qty'         => $qty,
                'status'      => $status,
                'units_sold'  => isset($sales[$p['p_id']]) ? (int)$sales[$p['p_id']]['units_sold'] : 0,
                'sales'       => isset($sales[$p['p_id']]) ? (float)$sales[$p['p_id']]['sales'] : 0,
                'stock_value' => max(0, $qty) * $price,
            );
            $rows[] = $row;

            $summary['products']++;
            $summary['stock_units'] += max(0, $qty);
            $summary['stock_value'] += $row['stock_value'];
            $summary['units_sold'] += $row['units_sold'];
            $summary['sales'] += $row['sales'];
            if ($status == 'Low Stock') {
                $summary['low']++;
            } elseif ($status == 'Out of Stock') {
                $summary['out']++;
            }
        }
        return array('rows' => $rows, 'summary' => $summary);
    }
}

if (!function_exists('inventory_report_filters')) {
    // Reads and cleans the report filters from the query string.
    function inventory_report_filters($input)
    {
        $date = '/^\d{4}-\d{2}-\d{2}$/';
        $from = (isset($input['from']) && preg_match($date, $input['from'])) ? $input['from'] : date('Y-m-01');
        $to = (isset($input['to']) && preg_match($date, $input['to'])) ? $input['to'] : date('Y-m-d');
        if ($from > $to) {
            list($from, $to) = array($to, $from);
        }
        $category = isset($input['category']) ? (int)$input['category'] : 0;
        $stock = (isset($input['stock']) && in_array($input['stock'], array('all', 'in', 'low', 'out'))) ? $input['stock'] : 'all';
        return array($from, $to, $category, $stock);
    }
}

if (!function_exists('history_status_badge')) {
    function history_status_badge($status)
    {
        $map = array('Pending' => 'warning', 'Confirmed' => 'success', 'Rejected' => 'danger');
        $class = isset($map[$status]) ? $map[$status] : 'default';
        $text = $status == 'Pending' ? 'For Verification' : $status;
        $style = $class == 'danger' ? ' style="background-color:#c62828;"' : '';
        return '<span class="label label-' . $class . '"' . $style . '>' . htmlspecialchars($text) . '</span>';
    }
}
