<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("inc/config.php");
include("inc/functions.php");
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

if(!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

// Get current user's profile picture if available
$profile_pic = isset($_SESSION['user']['profile_pic']) && !empty($_SESSION['user']['profile_pic']) ?
    'img/users/'.$_SESSION['user']['profile_pic'] : 'img/formal.jpg';

// Get user role
$user_role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Malou's Management</title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/ionicons.min.css">
    <link rel="stylesheet" href="css/datepicker3.css">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/select2.min.css">
    <link rel="stylesheet" href="css/dataTables.bootstrap.css">
    <link rel="stylesheet" href="css/jquery.fancybox.css">
    <link rel="stylesheet" href="css/AdminLTE.min.css">
    <link rel="stylesheet" href="css/_all-skins.min.css">
    <link rel="stylesheet" href="css/on-off-switch.css"/>
    <link rel="stylesheet" href="css/summernote.css">
    <link rel="stylesheet" href="style.css">

</head>

<body class="hold-transition fixed skin-blue sidebar-mini">

<div class="wrapper">

    <header class="main-header">

        <a href="index.php" class="logo">
            <img src="img/1.png" alt="Malou's Management Logo" class="logo-img">
        </a>

        <nav class="navbar navbar-static-top">

            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
            </a>

            <span style="float:left;line-height:50px;color:#fff;padding-left:15px;font-size:18px;">Malou's Management</span>

            <!-- Top Bar ... User Information .. Login/Log out Area -->
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <!-- Profile Image and User Name -->
<!--                            <img src="--><?php //echo $profile_pic; ?><!--" alt="User Image" class="user-image">-->
                            <span class="hidden-xs"><?php echo htmlspecialchars($_SESSION['user']['full_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="manage-account.php" class="btn btn-default btn-flat">Manage Account</a>
                                </div>
                                <div class="pull-right">
                                    <a href="logout.php" class="btn btn-default btn-flat">Sign out</a>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

        </nav>
    </header>

    <?php $cur_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1); ?>
    <!-- Side Bar to Manage Shop Activities -->
    <aside class="main-sidebar">
        <section class="sidebar">

            <ul class="sidebar-menu">

                <li class="treeview <?php if($cur_page == 'index.php') {echo 'active';} ?>">
                    <a href="index.php">
                        <i class="fa fa-home"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li class="treeview <?php if( ($cur_page == 'product.php') || ($cur_page == 'product-add.php') || ($cur_page == 'product-edit.php') ) {echo 'active';} ?>">
                    <a href="pos.php">
                        <i class="fa fa-cart-plus"></i> <span>POS</span>
                    </a>
                </li>

                <li class="treeview <?php if( ($cur_page == 'order.php') ) {echo 'active';} ?>">
                    <a href="order.php">
                        <i class="fa fa-sort"></i> <span>Order Management</span>
                    </a>
                </li>
                <li class="treeview <?php if( ($cur_page == 'product.php') || ($cur_page == 'product-add.php') || ($cur_page == 'product-edit.php') ) {echo 'active';} ?>">
                    <a href="product.php">
                        <i class="fa fa-cart-plus"></i> <span>Product Management</span>
                    </a>
                </li>
                <?php if($user_role == 'superadmin'): ?>
                    <!-- Menu items visible only to superadmin -->
                    <li class="treeview <?php if(($cur_page == 'top-category.php') || ($cur_page == 'top-category-add.php') || ($cur_page == 'top-category-edit.php')) {echo 'active';} ?>">
                        <a href="top-category.php">
                            <i class="fa fa-sliders"></i> <span>Category Management</span>
                        </a>
                    </li>



                    <li class="treeview <?php if( ($cur_page == 'feedback.php') ) {echo 'active';} ?>">
                        <a href="feedback.php">
                            <i class="fa fa-commenting"></i> <span>View feedbacks</span>
                        </a>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'customer.php') || ($cur_page == 'customer-add.php') || ($cur_page == 'customer-edit.php') ) {echo 'active';} ?>">
                        <a href="customer.php">
                            <i class="fa fa-users"></i> <span>Registered Customer</span>
                        </a>
                    </li>

                    <li class="treeview <?php if( ($cur_page == 'user.php') || ($cur_page == 'customer-add.php') || ($cur_page == 'customer-edit.php') ) {echo 'active';} ?>">
                        <a href="user.php">
                            <i class="fa fa-users"></i> <span>User List</span>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </section>
    </aside>

    <div class="content-wrapper">