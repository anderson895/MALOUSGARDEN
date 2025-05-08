<?php
ob_start();
session_start();
include("inc/config.php");
include("inc/functions.php");
include('header.php');

if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$error_message = '';
$success_message = '';

// Get current user details
$query = $pdo->prepare("SELECT * FROM tbl_user WHERE id = ?");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($full_name)) {
        $error_message = 'Full name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            // Check if email is already used by another user
            $check_query = $pdo->prepare("SELECT id FROM tbl_user WHERE email = ? AND id != ?");
            $check_query->execute([$email, $user_id]);

            if ($check_query->rowCount() > 0) {
                $error_message = 'Email is already in use by another account.';
            } else {
                // Update user details
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = $pdo->prepare("UPDATE tbl_user SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $update_query->execute([$full_name, $email, $phone, $hashed_password, $user_id]);
                } else {
                    $update_query = $pdo->prepare("UPDATE tbl_user SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $update_query->execute([$full_name, $email, $phone, $user_id]);
                }

                // Update session
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;

                $success_message = 'Profile updated successfully!';

                // Refresh user data
                $query = $pdo->prepare("SELECT * FROM tbl_user WHERE id = ?");
                $query->execute([$user_id]);
                $user = $query->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .account-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .account-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .account-header h2 {
            color: #343a40;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #495057;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 6px;
        }
        .btn-secondary {
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 6px;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .input-icon input {
            padding-left: 40px;
        }
        .password-strength {
            height: 5px;
            margin-top: 8px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: width 0.3s, background 0.3s;
        }
    </style>
</head>
<body>
    <div class="account-container">
        <div class="account-header">
            <h2><i class="fas fa-user-cog me-2"></i>Manage Your Account</h2>
            <p class="text-muted">Update your personal information and password</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">New Password (optional)</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Leave blank to keep current password">
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="password-strength-bar"></div>
                </div>
                <small class="text-muted">Minimum 8 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm new password">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Profile
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('password-strength-bar');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength += 1;
                // Contains both lower and uppercase
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
                // Contains numbers
                if (password.match(/\d/)) strength += 1;
                // Contains special chars
                if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
                
                // Update strength bar
                const width = strength * 25;
                let color = '#dc3545'; // red
                
                if (strength >= 3) color = '#28a745'; // green
                else if (strength >= 2) color = '#ffc107'; // yellow
                
                strengthBar.style.width = width + '%';
                strengthBar.style.backgroundColor = color;
            });
            
            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password && password.length < 8) {
                    alert('Password must be at least 8 characters long');
                    e.preventDefault();
                    return false;
                }
                
                if (password && password !== confirmPassword) {
                    alert('Passwords do not match');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>