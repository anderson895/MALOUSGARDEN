<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_product_category = $row['banner_product_category'];
}
?>

<?php
if( !isset($_REQUEST['id']) || !isset($_REQUEST['type']) ) {
    header('location: index.php');
    exit;
} else {

    if( ($_REQUEST['type'] != 'top-category') ) {
        header('location: index.php');
        exit;
    } else {

       
        $statement = $pdo->prepare("SELECT * FROM tbl_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $top[] = $row['tcat_id'];
            $top1[] = $row['tcat_name'];
            $top2[] = $row['tcat_id'];
            $top3[] = $row['p_featured_photo'];

			
			
        }

        if($_REQUEST['type'] == 'top-category') {
            if(!in_array($_REQUEST['id'],$top)) {
                header('location: index.php');
                exit;
            } else {

                // Getting Title
                for ($i=0; $i < count($top); $i++) { 
                    if($top[$i] == $_REQUEST['id']) {
                        $title = $top1[$i];
						$p_featured_photo = $top3[$i];
                        break;
                    }
                }
                $arr1 = array();
                $arr2 = array();
                // Find out all tcat ids under this
                for ($i=0; $i < count($top); $i++) { 
                    if($top2[$i] == $_REQUEST['id']) {
                        $arr1[] = $top[$i];
                    }
                }
                for ($j=0; $j < count($arr1); $j++) {
                    for ($i=0; $i < count($top); $i++) { 
                        if($top2[$i] == $arr1[$j]) {
                            $arr2[] = $top[$i];
                        }
                    }   
                }
                $final_tcat_ids = $arr2;
            }   
        }

        if($_REQUEST['type'] == 'top-category') {
            if(!in_array($_REQUEST['id'],$top)) {
                header('location: index.php');
                exit;
            } else {
                // Getting Title
                for ($i=0; $i < count($top); $i++) { 
                    if($top[$i] == $_REQUEST['id']) {
                        $title = $top1[$i];
												$p_featured_photo = $top3[$i];

                        break;
                    }
                }
                $arr2 = array();        
                // Find out all tcat ids under this
                for ($i=0; $i < count($top); $i++) { 
                    if($top2[$i] == $_REQUEST['id']) {
                        $arr2[] = $top[$i];
                    }
                }
                $final_tcat_ids = $arr2;
            }
        }

        if($_REQUEST['type'] == 'top-category') {
            if(!in_array($_REQUEST['id'],$top)) {
                header('location: index.php');
                exit;
            } else {
                // Getting Title
                for ($i=0; $i < count($top); $i++) { 
                    if($top[$i] == $_REQUEST['id']) {
                        $title = $top1[$i];
												$p_featured_photo = $top3[$i];

                        break;
                    }
                }
                $final_tcat_ids = array($_REQUEST['id']);
            }
        }
        
    }   
}
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $p_featured_photo; ?>)">

    <div class="lolol">
        <h1> <?php echo $title; ?></h1>
    </div>
</div>

<div class="page bg-product">
    <div class="container">
        <div class="row">
          <div class="col-sm-1">
               
            </div>
            <div class="col-sm-10">
                
                <div class="product product-cat">

                    <div class="row">
                        <?php
                        // Checking if any product is available or not
                        $prod_count = 0;
                        $statement = $pdo->prepare("SELECT * FROM tbl_product");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                            $prod_table_tcat_ids[] = $row['tcat_id'];
                        }

                        for($ii=0;$ii<count($final_tcat_ids);$ii++):
                            if(in_array($final_tcat_ids[$ii],$prod_table_tcat_ids)) {
                                $prod_count++;
                            }
                        endfor;

                        if($prod_count==0) {
                            echo '<div class="pl_15">'."No Product Found".'</div>';
                        } else {
                            for($ii=0;$ii<count($final_tcat_ids);$ii++) {
                                $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE tcat_id=? AND p_is_active=?");
                                $statement->execute(array($final_tcat_ids[$ii],1));
                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($result as $row) {
                                    ?>
                                    <div class="col-md-4 item item-product-cat">
                                        <div class="inner">
                                            <div class="thumb">
                                                <div class="photo" style="background-image:url(assets/uploads/<?php echo $row['p_featured_photo']; ?>);"></div>
                                                <div class="overlay"></div>
                                            </div>
                                            <div class="text">
                                                <h3><a href="product.php?id=<?php echo $row['p_id']; ?>"><?php echo $row['p_name']; ?></a></h3>
                                                <h3>
    <a href="product.php?id=<?php echo $row['p_id']; ?>" style="color:
        <?php 
            if ($row['p_qty'] == 0) {
                echo 'red';
            } elseif ($row['p_qty'] <= 5) {
                echo 'orange';
            } else {
                echo 'green';
            }
        ?>;">
        <?php 
            if ($row['p_qty'] == 0) {
                echo 'Out of Stock';
            } elseif ($row['p_qty'] <= 5) {
                echo 'Low Stock (' . $row['p_qty'] . ' pcs)';
            } else {
                echo 'In Stock (' . $row['p_qty'] . ' pcs)';
            }
        ?>
    </a>
</h3>

                                                <h4>
                                                    <?php echo "₱"; ?><?php echo $row['p_current_price']; ?> 
                                                    <?php if($row['p_old_price'] != ''): ?>
                                                    <del>
                                                        <?php echo "₱"; ?><?php echo $row['p_old_price']; ?>
                                                    </del>
                                                    <?php endif; ?>
                                                </h4>
                                                <div class="rating">
                                                    <?php
                                                    $t_rating = 0;
                                                    $statement1 = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=?");
                                                    $statement1->execute(array($row['p_id']));
                                                    $tot_rating = $statement1->rowCount();
                                                    if($tot_rating == 0) {
                                                        $avg_rating = 0;
                                                    } else {
                                                        $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                                                        foreach ($result1 as $row1) {
                                                            $t_rating = $t_rating + $row1['rating'];
                                                        }
                                                        $avg_rating = $t_rating / $tot_rating;
                                                    }
                                                    ?>
                                                    <?php
                                                    if($avg_rating == 0) {
                                                        echo '';
                                                    }
                                                    elseif($avg_rating == 1.5) {
                                                        echo '
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star-half-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                        ';
                                                    } 
                                                    elseif($avg_rating == 2.5) {
                                                        echo '
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star-half-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                        ';
                                                    }
                                                    elseif($avg_rating == 3.5) {
                                                        echo '
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star-half-o"></i>
                                                            <i class="fa fa-star-o"></i>
                                                        ';
                                                    }
                                                    elseif($avg_rating == 4.5) {
                                                        echo '
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star-half-o"></i>
                                                        ';
                                                    }
                                                    else {
                                                        for($i=1;$i<=5;$i++) {
                                                            ?>
                                                            <?php if($i>$avg_rating): ?>
                                                                <i class="fa fa-star-o"></i>
                                                            <?php else: ?>
                                                                <i class="fa fa-star"></i>
                                                            <?php endif; ?>
                                                            <?php
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <?php if($row['p_qty'] == 0): ?>
                                                    <div class="out-of-stock">
                                                        <div class="inner">
                                                            Out Of Stock
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <p><a href="product.php?id=<?php echo $row['p_id']; ?>"> <?php echo "VIEW PRODUCT"; ?></a></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>