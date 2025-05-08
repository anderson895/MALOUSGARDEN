<?php require_once('header.php'); ?>

<?php
if(!isset($_REQUEST['id'])) {
    header('location: logout.php');
    exit;
} else {
    $statement = $pdo->prepare("SELECT * FROM tbl_user WHERE id=?");
    $statement->execute(array($_REQUEST['id']));
    $total = $statement->rowCount();
    if($total == 0) {
        header('location: logout.php');
        exit;
    }
}
?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_user WHERE id=?");
$statement->execute(array($_REQUEST['id']));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $full_name = $row['full_name'];
    $email = $row['email'];
    $phone = $row['phone'];
    $role = $row['role'];
    $status = $row['status'];
}
?>

<section class="content-header">
    <div class="content-header-left">
        <h1>Edit User</h1>
    </div>
    <div class="content-header-right">
        <a href="user.php" class="btn btn-primary btn-sm">View All</a>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-body">
                    <form action="user-edit-action.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>">
                        <div class="form-group">
                            <label for="full_name">Full Name <span>*</span></label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo $full_name; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span>*</span></label>
                            <input type="email" class="form-control" name="email" value="<?php echo $email; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo $phone; ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password (Leave blank if not changing)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="form-group">
                            <label for="role">Role <span>*</span></label>
                            <select class="form-control" name="role" required>
                                <option value="user" <?php if($role == 'user') {echo 'selected';} ?>>User</option>
                                <option value="admin" <?php if($role == 'admin') {echo 'selected';} ?>>Admin</option>
                                <option value="super_admin" <?php if($role == 'super_admin') {echo 'selected';} ?>>Super Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status <span>*</span></label>
                            <select class="form-control" name="status" required>
                                <option value="1" <?php if($status == 1) {echo 'selected';} ?>>Active</option>
                                <option value="0" <?php if($status == 0) {echo 'selected';} ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" name="form1">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once('footer.php'); ?>