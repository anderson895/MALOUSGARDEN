<?php require_once('header.php'); ?>
<?php

error_reporting(0);
// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP account of the shop (kept out of git)
if (file_exists('admin/inc/mail-config.php')) {
    require_once('admin/inc/mail-config.php');
}

// No spam: one code per minute, at most 5 codes per session, 10-minute expiry
define('OTP_RESEND_SECONDS', 60);
define('OTP_MAX_SENDS', 5);
define('OTP_TTL_SECONDS', 600);
define('OTP_MAX_ATTEMPTS', 5);

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

// Send the OTP email - the only email this system sends
function sendOTP($email, $otp) {
    if (!defined('SMTP_USER')) {
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $minutes = OTP_TTL_SECONDS / 60;
        $mail->isHTML(true);
        $mail->Subject = "Your Malou's Garden verification code";
        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                <div style="background:#2e7d32;color:#fff;padding:16px 20px;font-size:18px;font-weight:bold;">Malou\'s Garden</div>
                <div style="padding:20px;color:#333;font-size:14px;line-height:1.5;">
                    <p>Hello,</p>
                    <p>Use this code to verify your email address and finish creating your Malou\'s Garden account:</p>
                    <p style="font-size:30px;font-weight:bold;letter-spacing:6px;text-align:center;margin:24px 0;">'.$otp.'</p>
                    <p>This code expires in '.$minutes.' minutes. If you did not sign up, you can safely ignore this email.</p>
                    <p style="color:#888;font-size:12px;border-top:1px solid #eee;padding-top:10px;">We only email you to verify your account. We do not send promotional emails.</p>
                </div>
            </div>';
        $mail->AltBody = "Malou's Garden\n\nYour verification code is: ".$otp."\n\nThis code expires in ".$minutes." minutes. If you did not sign up, you can safely ignore this email.\n\nWe only email you to verify your account. We do not send promotional emails.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Sends a new code unless one was sent to the same address less than a minute
// ago or the session already used up its codes. Returns '' when a code was sent.
function issueOTP($email) {
    if (isset($_SESSION['otp_time'], $_SESSION['otp_email']) && $_SESSION['otp_email'] == $email) {
        $wait = OTP_RESEND_SECONDS - (time() - $_SESSION['otp_time']);
        if ($wait > 0) {
            return "We already sent a code to ".htmlspecialchars($email).". You can request a new one in ".$wait." seconds.";
        }
    }
    $sends = isset($_SESSION['otp_sends']) ? $_SESSION['otp_sends'] : 0;
    if ($sends >= OTP_MAX_SENDS) {
        return "Too many verification codes were requested. Please try again later.";
    }
    if (!defined('SMTP_USER')) {
        return "Failed to send the verification code: the email service is not set up yet.";
    }
    $otp = generateOTP();
    if (!sendOTP($email, $otp)) {
        return "Failed to send OTP. Please try again.";
    }
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_sends'] = $sends + 1;
    $_SESSION['otp_attempts'] = 0;
    return '';
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
        $_SESSION['registration_data'] = $_POST;

        $otp_error = issueOTP($_POST['cust_email']);
        if ($otp_error == '') {
            $success_message = "OTP sent to your email. Please verify to complete registration.";
            $show_otp_modal = true;
        } elseif (isset($_SESSION['otp']) && isset($_SESSION['otp_email']) && $_SESSION['otp_email'] == $_POST['cust_email']) {
            // A code is already on its way: just ask for it again
            $error_message = $otp_error;
            $show_otp_modal = true;
        } else {
            $error_message = $otp_error;
        }
    }
}

// Resend the code (cooldown and limit still apply)
if (isset($_POST['resend_otp'])) {
    if (!isset($_SESSION['registration_data'])) {
        $error_message = "Session expired. Please try again.";
    } else {
        $otp_error = issueOTP($_SESSION['registration_data']['cust_email']);
        if ($otp_error == '') {
            $success_message = "A new code was sent to your email.";
        } else {
            $error_message = $otp_error;
        }
        $show_otp_modal = isset($_SESSION['otp']);
    }
}

// Handle OTP verification and registration
if (isset($_POST['verify_otp'])) {
    if (!isset($_SESSION['otp']) || !isset($_SESSION['registration_data'])) {
        $error_message = "Session expired. Please try again.";
    } else if (time() - $_SESSION['otp_time'] > OTP_TTL_SECONDS) {
        $error_message = "Your code has expired. Please click \"Resend code\" to get a new one.";
        $show_otp_modal = true;
    } else if ($_SESSION['otp_attempts'] >= OTP_MAX_ATTEMPTS) {
        $error_message = "Too many wrong codes. Please click \"Resend code\" to get a new one.";
        $show_otp_modal = true;
    } else if ($_POST['otp'] != $_SESSION['otp']) {
        $_SESSION['otp_attempts']++;
        $error_message = "Invalid OTP. Please try again.";
        $show_otp_modal = true;
    } else {
        // OTP verified, proceed with registration
        $form_data = $_SESSION['registration_data'];

        // The registration address is also the first (default) shipping address
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
            strip_tags($form_data['cust_name']),
            strip_tags($form_data['cust_phone']),
            strip_tags($form_data['cust_address']),
            strip_tags($form_data['cust_city']),
            strip_tags($form_data['cust_state']),
            strip_tags($form_data['cust_zip']),
            strip_tags($form_data['cust_password']),
            1
        ));
        $new_cust_id = $pdo->lastInsertId();

        $statement = $pdo->prepare("INSERT INTO tbl_customer_address (cust_id, label, full_name, phone, address_line1, city, province, zip, is_default, created_at)
                                    VALUES (?,?,?,?,?,?,?,?,1,?)");
        $statement->execute(array(
            $new_cust_id,
            'Home',
            strip_tags($form_data['cust_name']),
            strip_tags($form_data['cust_phone']),
            strip_tags($form_data['cust_address']),
            strip_tags($form_data['cust_city']),
            strip_tags($form_data['cust_state']),
            strip_tags($form_data['cust_zip']),
            date('Y-m-d H:i:s')
        ));

        // Clear session data
        unset($_SESSION['registration_data']);
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_attempts']);

        $success_message = "Your registration has been completed successfully! You can now log in. Your address was saved as your default shipping address.";
    }
}

$resend_wait = isset($_SESSION['otp_time']) ? max(0, OTP_RESEND_SECONDS - (time() - $_SESSION['otp_time'])) : 0;
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
                                        <small class="form-text text-muted"><i class="fa fa-lock"></i> We only use your email to verify your account. No promotional or spam emails.</small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for=""><?php echo "Phone Number"; ?> *</label>
                                        <input type="text" class="form-control" name="cust_phone" value="<?php if(isset($_POST['cust_phone'])){echo $_POST['cust_phone'];} ?>">
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
                                     <div class="col-md-12 form-group">
                                        <label for=""><?php echo "Address Line"; ?> * <small class="text-muted">(House/Unit No., Street, Barangay)</small></label>
                                        <textarea name="cust_address" class="form-control" cols="30" rows="10" style="height:70px;"><?php if(isset($_POST['cust_address'])){echo $_POST['cust_address'];} ?></textarea>
                                        <small class="form-text text-muted">This becomes your default shipping address. You can add more addresses and pin your location on the map later in My Addresses.</small>
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
                    <?php if($error_message != '' && isset($show_otp_modal)): ?>
                        <div class="alert alert-warning"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="otp">Enter OTP sent to your email</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                            <small class="text-muted">The code expires in <?php echo OTP_TTL_SECONDS / 60; ?> minutes. Check your Spam folder if you don't see it.</small>
                        </div>
                        <button type="submit" name="verify_otp" class="btn btn-primary">Verify & Register</button>
                    </form>
                    <form action="" method="post" style="margin-top:10px;">
                        <button type="submit" name="resend_otp" class="btn btn-link" id="resendBtn" style="padding-left:0;">Resend code</button>
                        <small class="text-muted" id="resendTimer"></small>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once('footer.php'); ?>

    <script>
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

            // Show OTP modal if needed
            <?php if(isset($show_otp_modal) && $show_otp_modal): ?>
            $('#otpModal').modal('show');
            <?php endif; ?>

            // "Resend code" unlocks after the cooldown
            var resendWait = <?php echo (int)$resend_wait; ?>;
            function tickResend() {
                if (resendWait > 0) {
                    $('#resendBtn').prop('disabled', true);
                    $('#resendTimer').text('You can request a new code in ' + resendWait + 's');
                    resendWait--;
                    setTimeout(tickResend, 1000);
                } else {
                    $('#resendBtn').prop('disabled', false);
                    $('#resendTimer').text('');
                }
            }
            tickResend();

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
