<?php
require_once('header.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: feedback.php');
    exit;
}

$id = (int)$_GET['id'];

$statement = $pdo->prepare("SELECT * FROM tbl_feedback WHERE id = ?");
$statement->execute([$id]);

if ($statement->rowCount() == 0) {
    header('Location: feedback.php');
    exit;
}

$statement = $pdo->prepare("DELETE FROM tbl_feedback WHERE id = ?");
$statement->execute([$id]);

header('Location: feedback.php?message=deleted');
exit;
?>
