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

$cust_id = $_SESSION['customer']['cust_id'];
$return_to_checkout = (isset($_REQUEST['return']) && $_REQUEST['return'] == 'checkout');
$self = 'customer-billing-shipping-update.php' . ($return_to_checkout ? '?return=checkout' : '');
$labels = array('Home', 'Work', 'Other');

// Set an address as the default one
if(isset($_GET['default'])) {
    $statement = $pdo->prepare("SELECT addr_id FROM tbl_customer_address WHERE addr_id=? AND cust_id=?");
    $statement->execute(array($_GET['default'], $cust_id));
    if($statement->rowCount()) {
        $statement = $pdo->prepare("UPDATE tbl_customer_address SET is_default=IF(addr_id=?,1,0) WHERE cust_id=?");
        $statement->execute(array($_GET['default'], $cust_id));
        sync_default_address($pdo, $cust_id);
    }
    header('location: customer-billing-shipping-update.php?msg=default');
    exit;
}

// Delete an address
if(isset($_GET['delete'])) {
    $statement = $pdo->prepare("DELETE FROM tbl_customer_address WHERE addr_id=? AND cust_id=?");
    $statement->execute(array($_GET['delete'], $cust_id));
    sync_default_address($pdo, $cust_id);
    header('location: customer-billing-shipping-update.php?msg=deleted');
    exit;
}

// Address being edited (or a blank one for "Add New Address")
$form = array(
    'addr_id' => 0, 'label' => 'Home', 'full_name' => $_SESSION['customer']['cust_name'],
    'phone' => $_SESSION['customer']['cust_phone'], 'address_line1' => '', 'address_line2' => '',
    'barangay' => '', 'city' => '', 'province' => '', 'zip' => '', 'latitude' => '', 'longitude' => '',
    'is_default' => 0,
);
if(isset($_GET['edit'])) {
    $statement = $pdo->prepare("SELECT * FROM tbl_customer_address WHERE addr_id=? AND cust_id=?");
    $statement->execute(array($_GET['edit'], $cust_id));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if($row) {
        $form = $row;
    }
}

// Add or update an address
if(isset($_POST['save_address'])) {
    foreach(array('label','full_name','phone','address_line1','address_line2','barangay','city','province','zip','latitude','longitude') as $key) {
        $form[$key] = isset($_POST[$key]) ? trim(strip_tags($_POST[$key])) : '';
    }
    $form['addr_id'] = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;
    $form['is_default'] = isset($_POST['is_default']) ? 1 : 0;
    if(!in_array($form['label'], $labels)) {
        $form['label'] = 'Home';
    }

    $valid = 1;
    if($form['full_name'] == '') {
        $valid = 0;
        $error_message .= "Full Name can not be empty.<br>";
    }
    if(!preg_match('/^(\+?63|0)9\d{9}$/', preg_replace('/[\s\-]/', '', $form['phone']))) {
        $valid = 0;
        $error_message .= "Please enter a valid mobile number (e.g. 09171234567).<br>";
    }
    if($form['address_line1'] == '') {
        $valid = 0;
        $error_message .= "Address Line 1 (House/Unit No., Street) can not be empty.<br>";
    }
    if($form['barangay'] == '') {
        $valid = 0;
        $error_message .= "Barangay can not be empty.<br>";
    }
    if($form['city'] == '') {
        $valid = 0;
        $error_message .= "City / Municipality can not be empty.<br>";
    }
    if($form['province'] == '') {
        $valid = 0;
        $error_message .= "Province can not be empty.<br>";
    }
    if(!preg_match('/^\d{4}$/', $form['zip'])) {
        $valid = 0;
        $error_message .= "ZIP Code must be 4 digits.<br>";
    }
    // The GPS pin is optional, but when given it must be inside the Philippines
    if($form['latitude'] !== '' || $form['longitude'] !== '') {
        if(!is_numeric($form['latitude']) || !is_numeric($form['longitude'])
            || $form['latitude'] < 4 || $form['latitude'] > 22 || $form['longitude'] < 116 || $form['longitude'] > 127) {
            $valid = 0;
            $error_message .= "The map pin must be inside the Philippines.<br>";
        }
    }

    if($form['addr_id']) {
        $statement = $pdo->prepare("SELECT addr_id FROM tbl_customer_address WHERE addr_id=? AND cust_id=?");
        $statement->execute(array($form['addr_id'], $cust_id));
        if(!$statement->rowCount()) {
            $valid = 0;
            $error_message .= "Address not found.<br>";
        }
    }

    if($valid == 1) {
        $lat = $form['latitude'] === '' ? null : round((float)$form['latitude'], 7);
        $lng = $form['longitude'] === '' ? null : round((float)$form['longitude'], 7);
        $values = array(
            $form['label'], $form['full_name'], $form['phone'], $form['address_line1'], $form['address_line2'],
            $form['barangay'], $form['city'], $form['province'], $form['zip'], $lat, $lng,
        );

        if($form['addr_id']) {
            $statement = $pdo->prepare("UPDATE tbl_customer_address SET label=?, full_name=?, phone=?, address_line1=?, address_line2=?,
                                        barangay=?, city=?, province=?, zip=?, latitude=?, longitude=? WHERE addr_id=? AND cust_id=?");
            $statement->execute(array_merge($values, array($form['addr_id'], $cust_id)));
            $addr_id = $form['addr_id'];
        } else {
            $statement = $pdo->prepare("INSERT INTO tbl_customer_address (label, full_name, phone, address_line1, address_line2,
                                        barangay, city, province, zip, latitude, longitude, cust_id, is_default, created_at)
                                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?)");
            $statement->execute(array_merge($values, array($cust_id, date('Y-m-d H:i:s'))));
            $addr_id = $pdo->lastInsertId();
        }

        if($form['is_default']) {
            $statement = $pdo->prepare("UPDATE tbl_customer_address SET is_default=IF(addr_id=?,1,0) WHERE cust_id=?");
            $statement->execute(array($addr_id, $cust_id));
        }
        sync_default_address($pdo, $cust_id);

        if($return_to_checkout) {
            header('location: checkout.php');
            exit;
        }
        header('location: customer-billing-shipping-update.php?msg=saved');
        exit;
    }
}

$messages = array(
    'saved'   => 'Address saved successfully.',
    'deleted' => 'Address deleted.',
    'default' => 'Default address updated. It will be used automatically at checkout.',
);
if(isset($_GET['msg']) && isset($messages[$_GET['msg']])) {
    $success_message = $messages[$_GET['msg']];
}

$addresses = get_customer_addresses($pdo, $cust_id);
$editing = $form['addr_id'] ? true : false;
// The first address is always the default; when editing the default keep it so
$lock_default = (count($addresses) == 0) || ($editing && $form['is_default']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
    .address-card { border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin-bottom: 20px; background: #fff; min-height: 190px; }
    .address-card.is-default { border: 2px solid #2e7d32; }
    .address-card h4 { margin: 0 0 8px; font-size: 17px; }
    .address-card p { margin: 0 0 6px; }
    .address-card .actions a { margin-right: 6px; }
    .gps-ok { color: #2e7d32; font-weight: bold; }
    .gps-missing { color: #c77700; font-weight: bold; }
    #address-map { height: 320px; border: 1px solid #ccc; border-radius: 6px; margin: 10px 0; }
    .address-form { border: 1px solid #ddd; border-radius: 6px; padding: 20px; background: #fafafa; }
    .address-form h3 { margin-top: 0; }
    .gps-box { background: #fff; border: 1px dashed #9ccc65; border-radius: 6px; padding: 12px 15px; margin-bottom: 15px; }
    #gps-status { margin: 8px 0 0; }
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
                    if($success_message != '') {
                        echo "<div class='success' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$success_message."</div>";
                    }
                    ?>

                    <h3><?php echo "My Addresses"; ?></h3>
                    <p class="text-muted">Your <b>default address</b> is selected automatically at checkout. You can save other addresses (e.g. work) and choose one when you check out.</p>
                    <?php if($return_to_checkout): ?>
                        <p><a href="checkout.php" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Checkout</a></p>
                    <?php endif; ?>

                    <div class="row">
                        <?php if(count($addresses) == 0): ?>
                            <div class="col-md-12"><p class="gps-missing">You have no saved address yet. Please add one below.</p></div>
                        <?php endif; ?>
                        <?php foreach($addresses as $a): ?>
                            <div class="col-md-6">
                                <div class="address-card <?php echo $a['is_default'] ? 'is-default' : ''; ?>">
                                    <h4>
                                        <i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($a['label']); ?>
                                        <?php if($a['is_default']): ?><span class="label label-success">Default</span><?php endif; ?>
                                    </h4>
                                    <p><b><?php echo htmlspecialchars($a['full_name']); ?></b> &middot; <?php echo htmlspecialchars($a['phone']); ?></p>
                                    <p><?php echo htmlspecialchars(format_address($a)); ?></p>
                                    <p>
                                        <?php if($a['latitude'] !== null && $a['longitude'] !== null): ?>
                                            <span class="gps-ok"><i class="fa fa-check-circle"></i> Pinned on map</span>
                                            &middot; <a href="<?php echo maps_link($a['latitude'], $a['longitude']); ?>" target="_blank">View on map</a>
                                        <?php else: ?>
                                            <span class="gps-missing"><i class="fa fa-exclamation-triangle"></i> No map pin</span>
                                            <small class="text-muted">(add one for accurate delivery)</small>
                                        <?php endif; ?>
                                    </p>
                                    <p class="actions">
                                        <a href="customer-billing-shipping-update.php?edit=<?php echo $a['addr_id']; ?><?php echo $return_to_checkout ? '&return=checkout' : ''; ?>#address-form" class="btn btn-primary btn-xs">Edit</a>
                                        <?php if(!$a['is_default']): ?>
                                            <a href="customer-billing-shipping-update.php?default=<?php echo $a['addr_id']; ?>" class="btn btn-success btn-xs">Set as Default</a>
                                        <?php endif; ?>
                                        <a href="customer-billing-shipping-update.php?delete=<?php echo $a['addr_id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this address?');">Delete</a>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form action="<?php echo $self; ?>" method="post" class="address-form" id="address-form">
                        <h3><?php echo $editing ? 'Edit Address' : 'Add New Address'; ?></h3>
                        <input type="hidden" name="addr_id" value="<?php echo (int)$form['addr_id']; ?>">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="">Label</label>
                                <select name="label" class="form-control">
                                    <?php foreach($labels as $label): ?>
                                        <option value="<?php echo $label; ?>" <?php echo $form['label'] == $label ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5 form-group">
                                <label for="">Full Name (Receiver) *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($form['full_name']); ?>" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="">Mobile Number *</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($form['phone']); ?>" placeholder="09171234567" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="">Province *</label>
                                <input type="text" class="form-control" name="province" id="province" list="province-list" value="<?php echo htmlspecialchars($form['province']); ?>" autocomplete="off" required>
                                <datalist id="province-list"></datalist>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="">City / Municipality *</label>
                                <input type="text" class="form-control" name="city" id="city" list="city-list" value="<?php echo htmlspecialchars($form['city']); ?>" autocomplete="off" required>
                                <datalist id="city-list"></datalist>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="">Barangay *</label>
                                <input type="text" class="form-control" name="barangay" id="barangay" list="barangay-list" value="<?php echo htmlspecialchars($form['barangay']); ?>" autocomplete="off" required>
                                <datalist id="barangay-list"></datalist>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 form-group">
                                <label for="">Address Line 1 * <small class="text-muted">(House/Unit No., Building, Street)</small></label>
                                <input type="text" class="form-control" name="address_line1" value="<?php echo htmlspecialchars($form['address_line1']); ?>" placeholder="e.g. 123 Rizal St." required>
                            </div>
                            <div class="col-md-5 form-group">
                                <label for="">Address Line 2 <small class="text-muted">(Subdivision/Village, Landmark - optional)</small></label>
                                <input type="text" class="form-control" name="address_line2" value="<?php echo htmlspecialchars($form['address_line2']); ?>" placeholder="e.g. Near the chapel">
                            </div>
                            <div class="col-md-2 form-group">
                                <label for="">ZIP Code *</label>
                                <input type="text" class="form-control" name="zip" value="<?php echo htmlspecialchars($form['zip']); ?>" maxlength="4" placeholder="2000" required>
                            </div>
                        </div>

                        <div class="gps-box">
                            <label><i class="fa fa-crosshairs"></i> Pin your exact location (GPS) <small class="text-muted">- recommended for accurate delivery</small></label><br>
                            <button type="button" class="btn btn-success btn-sm" id="use-location"><i class="fa fa-location-arrow"></i> Use my current location</button>
                            <button type="button" class="btn btn-default btn-sm" id="clear-pin">Clear pin</button>
                            <p id="gps-status" class="text-muted"></p>
                            <div id="address-map"></div>
                            <small class="text-muted">Tip: tap the map or drag the pin to adjust it to your exact gate/door.</small>
                            <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars((string)$form['latitude']); ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars((string)$form['longitude']); ?>">
                        </div>

                        <div class="form-group">
                            <?php if($lock_default): ?>
                                <input type="hidden" name="is_default" value="1">
                                <label><input type="checkbox" checked disabled> Default address</label>
                            <?php else: ?>
                                <label><input type="checkbox" name="is_default" value="1" <?php echo $form['is_default'] ? 'checked' : ''; ?>> Set as default address</label>
                            <?php endif; ?>
                        </div>
                        <input type="submit" class="btn btn-primary" value="<?php echo "Save Address"; ?>" name="save_address">
                        <?php if($editing): ?>
                            <a href="<?php echo $self; ?>" class="btn btn-default">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once('footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    $(function() {
        // ---------- Province / City / Barangay suggestions (PSGC API) ----------
        var PSGC = 'https://psgc.gitlab.io/api/';
        var NCR = 'Metro Manila (NCR)';
        var provinceCodes = {}, cityCodes = {};

        function fill(list, items) {
            var html = '';
            $.each(items, function(i, name) { html += '<option value="' + $('<div>').text(name).html() + '">'; });
            $(list).html(html);
        }
        function sortByName(data) {
            return data.sort(function(a, b) { return a.name.localeCompare(b.name); });
        }
        function loadCities() {
            var province = $('#province').val();
            var url = province === NCR ? PSGC + 'regions/130000000/cities-municipalities/'
                    : (provinceCodes[province] ? PSGC + 'provinces/' + provinceCodes[province] + '/cities-municipalities/' : null);
            cityCodes = {};
            fill('#city-list', []);
            if (!url) return;
            $.getJSON(url, function(data) {
                fill('#city-list', $.map(sortByName(data), function(c) { cityCodes[c.name] = c.code; return c.name; }));
                loadBarangays();
            });
        }
        function loadBarangays() {
            var code = cityCodes[$('#city').val()];
            fill('#barangay-list', []);
            if (!code) return;
            $.getJSON(PSGC + 'cities-municipalities/' + code + '/barangays/', function(data) {
                fill('#barangay-list', $.map(sortByName(data), function(b) { return b.name; }));
            });
        }
        $.getJSON(PSGC + 'provinces/', function(data) {
            var names = $.map(sortByName(data), function(p) { provinceCodes[p.name] = p.code; return p.name; });
            names.unshift(NCR);
            fill('#province-list', names);
            loadCities();
        });
        $('#province').on('change', loadCities);
        $('#city').on('change', loadBarangays);

        // ---------- GPS pin (Leaflet + OpenStreetMap) ----------
        if (typeof L === 'undefined') {
            $('#gps-status').text('The map could not be loaded. Check your internet connection; you can still save the address without a pin.');
            $('#address-map').hide();
            return;
        }
        var lat = parseFloat($('#latitude').val()), lng = parseFloat($('#longitude').val());
        var hasPin = !isNaN(lat) && !isNaN(lng);
        var map = L.map('address-map').setView(hasPin ? [lat, lng] : [12.8797, 121.7740], hasPin ? 17 : 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        var marker = null;

        function showStatus() {
            if (marker) {
                var p = marker.getLatLng();
                $('#gps-status').html('<span class="gps-ok"><i class="fa fa-check-circle"></i> Pinned:</span> ' + p.lat.toFixed(6) + ', ' + p.lng.toFixed(6));
            } else {
                $('#gps-status').html('<span class="gps-missing">No pin yet</span> - use your current location or tap the map.');
            }
        }
        function setPin(latlng) {
            if (!marker) {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', function() { setPin(marker.getLatLng()); });
            } else {
                marker.setLatLng(latlng);
            }
            $('#latitude').val(latlng.lat.toFixed(7));
            $('#longitude').val(latlng.lng.toFixed(7));
            showStatus();
        }
        if (hasPin) setPin(L.latLng(lat, lng));
        showStatus();

        map.on('click', function(e) { setPin(e.latlng); });

        $('#use-location').on('click', function() {
            if (!navigator.geolocation) {
                $('#gps-status').text('Your browser does not support GPS location. Please tap the map to pin your address.');
                return;
            }
            $('#gps-status').text('Getting your location...');
            navigator.geolocation.getCurrentPosition(function(pos) {
                var p = L.latLng(pos.coords.latitude, pos.coords.longitude);
                setPin(p);
                map.setView(p, 17);
            }, function() {
                $('#gps-status').html('<span class="gps-missing">Could not get your location.</span> Please allow location access, or tap the map to pin it manually.');
            }, { enableHighAccuracy: true, timeout: 15000 });
        });

        $('#clear-pin').on('click', function() {
            if (marker) { map.removeLayer(marker); marker = null; }
            $('#latitude').val('');
            $('#longitude').val('');
            showStatus();
        });
    });
</script>
