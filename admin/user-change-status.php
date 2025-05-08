<?php
require_once('header.php');

if(isset($_GET['id'])) {
    // Kunin ang current status
    $statement = $pdo->prepare("SELECT status FROM tbl_user WHERE id=?");
    $statement->execute(array($_GET['id']));
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $current_status = $row['status'];
    }
    
    // I-toggle ang status
    $new_status = ($current_status == 1) ? 0 : 1;
    
    // I-update ang database
    $statement = $pdo->prepare("UPDATE tbl_user SET status=? WHERE id=?");
    $statement->execute(array($new_status, $_GET['id']));
    
    $_SESSION['success'] = 'User status successfully changed';
    header('location: user.php');
    exit;
}

header('location: user.php');
exit;
?>