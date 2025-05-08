<?php
require_once('header.php');

if(!isset($_REQUEST['id'])) {
    header('location: user.php');
    exit;
} else {
    $statement = $pdo->prepare("SELECT * FROM tbl_user WHERE id=?");
    $statement->execute(array($_REQUEST['id']));
    $total = $statement->rowCount();
    if($total == 0) {
        header('location: user.php');
        exit;
    }
}

$statement = $pdo->prepare("DELETE FROM tbl_user WHERE id=?");
$statement->execute(array($_REQUEST['id']));

$_SESSION['success'] = 'User has been deleted successfully!';
header('location: user.php');
?>