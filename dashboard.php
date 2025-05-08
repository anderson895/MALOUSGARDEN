<?php require_once('header.php'); ?>

<?php
// Ensure the customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ' . BASE_URL . 'logout.php');
    exit;
}

// Check if the account is inactive (status = 0)
$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_id = ? AND cust_status = ?");
$statement->execute([$_SESSION['customer']['cust_id'], 0]);

if ($statement->rowCount()) {
    header('Location: ' . BASE_URL . 'logout.php');
    exit;
}
?>

<div class="page">
    <div class="container">
        <div class="row">
            <!-- Sidebar and Welcome Message -->
            <div class="col-md-12">
                <h1 class="text-center mt-4 mb-4">
                    <?php echo "Welcome to Malou's Garden"; ?>
                </h1>
                <?php require_once('customer-sidebar.php'); ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-12">
                <div class="user-content p-4 bg-light rounded shadow-sm">
                    <p class="text-muted text-center">Select an option from the sidebar to manage your profile, orders, or settings.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
