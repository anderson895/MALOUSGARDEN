<?php
require_once('header.php');

if(isset($_POST['form1'])) {
    $valid = 1;

    if(empty($_POST['full_name'])) {
        $valid = 0;
        $error_message .= "Full Name can not be empty<br>";
    }

    if(empty($_POST['email'])) {
        $valid = 0;
        $error_message .= "Email can not be empty<br>";
    } else {
        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $valid = 0;
            $error_message .= "Email must be valid<br>";
        } else {
            $statement = $pdo->prepare("SELECT * FROM tbl_user WHERE email=?");
            $statement->execute(array($_POST['email']));
            $total = $statement->rowCount();
            if($total) {
                $valid = 0;
                $error_message .= "Email already exists<br>";
            }
        }
    }

    if(empty($_POST['password'])) {
        $valid = 0;
        $error_message .= "Password can not be empty<br>";
    }

    if($valid == 1) {
        $password = strip_tags($_POST['password']);
        // Remove the password hashing line
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $statement = $pdo->prepare("INSERT INTO tbl_user (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $statement->execute(array(
            strip_tags($_POST['full_name']),
            strip_tags($_POST['email']),
            strip_tags($_POST['phone']),
            $password, // Use the plain password instead of $hashed_password
            strip_tags($_POST['role']),
            strip_tags($_POST['status'])
        ));

        $success_message = "User is added successfully!";
        $_SESSION['success'] = $success_message;
        header("location: user.php");
    }
}
?>

<?php
if(isset($error_message)) {
    echo '<div class="alert alert-danger">'.$error_message.'</div>';
    unset($error_message);
}
if(isset($success_message)) {
    echo '<div class="alert alert-success">'.$success_message.'</div>';
    unset($success_message);
}
?>

<?php require_once('footer.php'); ?>