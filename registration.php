<?php require_once('header.php'); ?>
<?php
// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $banner_registration = $row['banner_registration'];
}

// Generate OTP
function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// Send OTP Email using PHPMailer
function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'reyesalthea86@gmail.com'; // Replace with your email
        $mail->Password = 'eqpq xyzq ardt zezc'; // Replace with your password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('reyesalthea86@gmail.com', 'Malous Garden');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification';
        $mail->Body = "Your OTP for registration is: <b>$otp</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle the send OTP request
if (isset($_POST['send_otp'])) {
    $valid = 1;

    // Validation checks (same as your existing checks)
    if(empty($_POST['cust_name'])) {
        $valid = 0;
        $error_message .= "Customer Name can not be empty."."<br>";
    }

    if(empty($_POST['cust_email'])) {
        $valid = 0;
        $error_message .= "Email Address can not be empty"."<br>";
    } else {
        if (filter_var($_POST['cust_email'], FILTER_VALIDATE_EMAIL) === false) {
            $valid = 0;
            $error_message .= "Email address must be valid."."<br>";
        } else {
            $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
            $statement->execute(array($_POST['cust_email']));
            $total = $statement->rowCount();
            if($total) {
                $valid = 0;
                $error_message .= "Email Address Already Exists."."<br>";
            }
        }
    }

    if(empty($_POST['cust_phone'])) {
        $valid = 0;
        $error_message .= "Phone Number can not be empty."."<br>";
    }

    if(empty($_POST['cust_address'])) {
        $valid = 0;
        $error_message .= "Address can not be empty."."<br>";
    }

    if(empty($_POST['cust_city'])) {
        $valid = 0;
        $error_message .= "City can not be empty."."<br>";
    }

    if(empty($_POST['cust_state'])) {
        $valid = 0;
        $error_message .= "State can not be empty."."<br>";
    }

    if(empty($_POST['cust_zip'])) {
        $valid = 0;
        $error_message .= "Pin Code can not be empty."."<br>";
    }

    if( empty($_POST['cust_password']) || empty($_POST['cust_re_password']) ) {
        $valid = 0;
        $error_message .= "Password can not be empty."."<br>";
    }

    if( !empty($_POST['cust_password']) && !empty($_POST['cust_re_password']) ) {
        if($_POST['cust_password'] != $_POST['cust_re_password']) {
            $valid = 0;
            $error_message .= "Passwords do not match."."<br>";
        }
    }

    if($valid == 1) {
        // Store form data in session
        session_start();
        $_SESSION['registration_data'] = $_POST;

        // Generate OTP
        $otp = generateOTP();
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_time'] = time(); // To check expiry if needed

        // Send OTP
        if (sendOTP($_POST['cust_email'], $otp)) {
            $success_message = "OTP sent to your email. Please verify to complete registration.";
            $show_otp_modal = true;
        } else {
            $error_message = "Failed to send OTP. Please try again.";
        }
    }
}

// Handle OTP verification and registration
if (isset($_POST['verify_otp'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['otp']) || !isset($_SESSION['registration_data'])) {
        $error_message = "Session expired. Please try again.";
    } else if ($_POST['otp'] != $_SESSION['otp']) {
        $error_message = "Invalid OTP. Please try again.";
    } else {
        // OTP verified, proceed with registration
        $form_data = $_SESSION['registration_data'];

        // Insert into the database (same as your existing code)
        $statement = $pdo->prepare("INSERT INTO tbl_customer (
                                    cust_name,                                       
                                    cust_email,
                                    cust_phone,                                    
                                    cust_address,
                                    cust_city,
                                    cust_state,
                                    cust_zip,                                                                               
                                    cust_s_name,                                       
                                    cust_s_phone,                                       
                                    cust_s_address,
                                    cust_s_city,
                                    cust_s_state,
                                    cust_s_zip,
                                    cust_password,                                       
                                    cust_status
                                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $statement->execute(array(
            strip_tags($form_data['cust_name']),
            strip_tags($form_data['cust_email']),
            strip_tags($form_data['cust_phone']),
            strip_tags($form_data['cust_address']),
            strip_tags($form_data['cust_city']),
            strip_tags($form_data['cust_state']),
            strip_tags($form_data['cust_zip']),
            '',
            '',
            '',
            '',
            '',
            '',
            strip_tags($form_data['cust_password']),
            1
        ));

        // Clear session data
        unset($_SESSION['registration_data']);
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);

        $success_message = "Your registration has been completed successfully!";
    }
}
?>

    <div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_registration; ?>);">
        <div class="inner">
            <h1><?php echo "Customer Registration"; ?></h1>
        </div>
    </div>

    <div class="page">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="user-content">
                        <form action="" method="post" id="registrationForm">

                            <div class="row">
                                <div class="col-md-2"></div>
                                <div class="col-md-8">

                                    <?php
                                    if($error_message != '') {
                                        echo "<div class='error' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$error_message."</div>";
                                    }
                                    if($success_message != '') {
                                        echo "<div class='success' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$success_message."</div>";
                                    }
                                    ?>

                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Full Name"; ?> *</label>
                                        <input type="text" class="form-control" name="cust_name" value="<?php if(isset($_POST['cust_name'])){echo $_POST['cust_name'];} ?>">
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Email Address"; ?> *</label>
                                        <input type="email" class="form-control" name="cust_email" value="<?php if(isset($_POST['cust_email'])){echo $_POST['cust_email'];} ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Phone Number"; ?> *</label>
                                        <input type="text" class="form-control" name="cust_phone" value="<?php if(isset($_POST['cust_phone'])){echo $_POST['cust_phone'];} ?>">
                                    </div>
                                    <div class="col-md-12 form-group">
                                        <label for=""><?php echo "Address"; ?> *</label>
                                        <textarea name="cust_address" class="form-control" cols="30" rows="10" style="height:70px;"><?php if(isset($_POST['cust_address'])){echo $_POST['cust_address'];} ?></textarea>
                                    </div>

                                    <!-- New dropdown for state -->
                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "State/Province"; ?> *</label>
                                        <select class="form-control" name="cust_state" id="state-dropdown">
                                            <option value="">Select State</option>
                                        </select>
                                    </div>


                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "City/Municipality"; ?> *</label>
                                        <select class="form-control" name="cust_city" id="city-dropdown" disabled>
                                            <option value="">Select City</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Zip Code"; ?> *</label>
                                        <input type="text" class="form-control" name="cust_zip" value="<?php if(isset($_POST['cust_zip'])){echo $_POST['cust_zip'];} ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Password"; ?> *</label>
                                        <input type="password" class="form-control" name="cust_password" id="password"
                                               pattern="^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{6,}$"
                                               title="Password must be at least 6 characters and include at least one number and one symbol">
                                        <small class="form-text text-muted">Must be at least 6 characters with at least one number and one symbol</small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Retype Password"; ?> *</label>
                                        <input type="password" class="form-control" name="cust_re_password" id="confirm_password">
                                        <small class="form-text text-danger" id="password-match-error" style="display: none;">Passwords do not match</small>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for=""></label>
                                        <input type="submit" class="btn btn-danger" value="<?php echo "Continue"; ?>" name="send_otp">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Add this to your existing document ready function or create a new one
        $(document).ready(function() {
            // Password matching validation
            $("#password, #confirm_password").on("keyup", function() {
                const password = $("#password").val();
                const confirmPassword = $("#confirm_password").val();

                if (confirmPassword === "") {
                    $("#password-match-error").hide();
                } else if (password !== confirmPassword) {
                    $("#password-match-error").show();
                } else {
                    $("#password-match-error").hide();
                }
            });

            // Form submission validation
            $("#registrationForm").on("submit", function(e) {
                const password = $("#password").val();
                const confirmPassword = $("#confirm_password").val();

                // Check password complexity using regex
                const passwordRegex = /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{6,}$/;

                if (!passwordRegex.test(password)) {
                    e.preventDefault();
                    alert("Password must be at least 6 characters and include at least one number and one symbol");
                    return false;
                }

                // Check if passwords match
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert("Passwords do not match");
                    return false;
                }
            });
        });
    </script>

    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">OTP Verification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="otp">Enter OTP sent to your email</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                        </div>
                        <button type="submit" name="verify_otp" class="btn btn-primary">Verify & Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add jQuery if not already included -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- For country-state-city API -->
    <script>
        $(document).ready(function() {
            // Show OTP modal if needed
            <?php if(isset($show_otp_modal) && $show_otp_modal): ?>
            $('#otpModal').modal('show');
            <?php endif; ?>

            var savedState = "<?php echo isset($_POST['cust_state']) ? $_POST['cust_state'] : ''; ?>";
            var savedCity = "<?php echo isset($_POST['cust_city']) ? $_POST['cust_city'] : ''; ?>";

            // Load all provinces
            $.getJSON("https://psgc.gitlab.io/api/provinces/", function(data) {
                var stateOptions = '<option value="">Select State/Province</option>';

                // Sort provinces alphabetically
                data.sort(function(a, b) {
                    return a.name.localeCompare(b.name);
                });

                // Add provinces to dropdown
                data.forEach(function(province) {
                    var selected = (province.name === savedState) ? "selected" : "";
                    stateOptions += '<option value="' + province.name + '" data-code="' + province.code + '" ' + selected + '>' + province.name + '</option>';
                });

                $("#state-dropdown").html(stateOptions);

                // If there's a saved state, load its cities
                if(savedState) {
                    var provinceCode = $("#state-dropdown option:selected").attr("data-code");
                    if(provinceCode) {
                        loadCities(provinceCode);
                    }
                }
            });

            // Function to load cities for a province
            function loadCities(provinceCode) {
                $("#city-dropdown").html('<option value="">Select City/Municipality</option>').prop('disabled', true);

                $.getJSON("https://psgc.gitlab.io/api/provinces/" + provinceCode + "/cities-municipalities/", function(cityData) {
                    // Sort cities alphabetically
                    cityData.sort(function(a, b) {
                        return a.name.localeCompare(b.name);
                    });

                    // Add cities to dropdown
                    cityData.forEach(function(city) {
                        var selected = (city.name === savedCity) ? "selected" : "";
                        $("#city-dropdown").append('<option value="' + city.name + '" ' + selected + '>' + city.name + '</option>');
                    });

                    $("#city-dropdown").prop('disabled', false);
                });
            }

            // When state changes, load cities
            $("#state-dropdown").change(function() {
                var provinceCode = $("option:selected", this).attr("data-code");
                if(provinceCode) {
                    loadCities(provinceCode);
                } else {
                    $("#city-dropdown").html('<option value="">Select City/Municipality</option>').prop('disabled', true);
                }
            });
        });
    </script>

<?php require_once('footer.php'); ?>