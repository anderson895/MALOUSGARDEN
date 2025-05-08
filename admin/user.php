<?php require_once('header.php'); ?>

<section class="content-header">
    <div class="content-header-left">
        <h1>View Users</h1>
    </div>
    <div class="content-header-right">
        <a href="user-add.php" class="btn btn-primary btn-sm">Add New User</a>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <?php
            if(isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
                unset($_SESSION['success']);
            }
            if(isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                unset($_SESSION['error']);
            }
            ?>
            <div class="box box-info">
                <div class="box-body table-responsive">
                    <table id="example1" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th width="10">#</th>
                                <th width="180">Full Name</th>
                                <th width="150">Email Address</th>
                                <th width="120">Phone</th>
                                <th width="100">Role</th>
                                <th width="100">Status</th>
                                <th width="120">Change Status</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i=0;
                            $statement = $pdo->prepare("SELECT * FROM tbl_user ORDER BY id DESC");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($result as $row) {
                                $i++;
                                // Default status kung hindi set
                                $status = isset($row['status']) ? $row['status'] : 1;
                                ?>
                                <tr class="<?php echo ($status == 1) ? 'bg-g' : 'bg-r'; ?>">
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td>
                                        <?php 
                                        if($row['role'] == 'super_admin') {
                                            echo 'Super Admin';
                                        } elseif($row['role'] == 'admin') {
                                            echo 'Admin';
                                        } else {
                                            echo 'User';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="label label-<?php echo ($status == 1) ? 'success' : 'danger'; ?>">
                                            <?php echo ($status == 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="user-change-status.php?id=<?php echo $row['id']; ?>" class="btn btn-<?php echo ($status == 1) ? 'danger' : 'success'; ?> btn-xs">
                                            <?php echo ($status == 1) ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="user-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-xs" style="margin-bottom:4px;">Edit</a>
                                        <a href="#" class="btn btn-danger btn-xs" data-href="user-delete.php?id=<?php echo $row['id']; ?>" data-toggle="modal" data-target="#confirm-delete">Delete</a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Delete Confirmation</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>