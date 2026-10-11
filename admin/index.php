<?php require_once('header.php'); ?>

    <section class="content-header">
        <h1>Dashboard</h1>
    </section>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_category");
$statement->execute();
$total_top_category = $statement->rowCount();

$statement = $pdo->prepare("SELECT * FROM tbl_product");
$statement->execute();
$total_product = $statement->rowCount();

$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_status='1'");
$statement->execute();
$total_customers = $statement->rowCount();


$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_status=?");
$statement->execute(array('Completed'));
$total_order_completed = $statement->rowCount();

$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE shipping_status=?");
$statement->execute(array('Completed'));
$total_shipping_completed = $statement->rowCount();

$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_status=?");
$statement->execute(array('Pending'));
$total_order_pending = $statement->rowCount();

// Paid (down payment or full) but not yet delivered
$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_status IN ('Partially Paid','Completed') AND shipping_status<>?");
$statement->execute(array('Completed'));
$total_order_complete_shipping_pending = $statement->rowCount();

// Sales = confirmed payments received, including down payments
$statement = $pdo->prepare("SELECT SUM(paid_amount) AS paid_amount FROM tbl_payment WHERE payment_status IN ('Partially Paid','Completed')");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $total_revenue = $row['paid_amount'];
}

// Customer payments waiting for the admin's confirmation
$statement = $pdo->prepare("SELECT COUNT(*) FROM tbl_payment_history WHERE status='Pending'");
$statement->execute();
$total_payments_to_verify = $statement->fetchColumn();

// Balances still to be collected from partially paid orders
$statement = $pdo->prepare("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM tbl_payment WHERE payment_status='Partially Paid'");
$statement->execute();
$total_outstanding = $statement->fetchColumn();

// Get monthly income data for the chart
$monthly_income = array_fill(0, 12, 0); // Initialize array with 0 for all 12 months
$statement = $pdo->prepare("
    SELECT 
        MONTH(payment_date) as month,
        SUM(paid_amount) as total
    FROM
        tbl_payment
    WHERE
        payment_status IN ('Partially Paid','Completed')
        AND YEAR(payment_date) = YEAR(CURRENT_DATE)
    GROUP BY 
        MONTH(payment_date)
");
$statement->execute();
$results = $statement->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    // Month is 1-indexed in the database, but we need 0-indexed for our array
    $month_index = $row['month'] - 1;
    $monthly_income[$month_index] = (float)$row['total'];
}

// Get top selling products for chart
$statement = $pdo->prepare("
    SELECT 
        product_name, 
        SUM(quantity) AS total_sold
    FROM 
        tbl_order 
    GROUP BY 
        product_name 
    ORDER BY 
        total_sold DESC
    LIMIT 5
");
$statement->execute();
$top_products = $statement->fetchAll(PDO::FETCH_ASSOC);

$product_names = [];
$product_sales = [];

foreach ($top_products as $product) {
    $product_names[] = $product['product_name'];
    $product_sales[] = (int)$product['total_sold'];
}
?>

    <section class="content">
        <div class="row">
            <!-- Products -->
            <div class="col-lg-3 col-xs-6">
                <a href="product.php" style="text-decoration: none;">
                    <div class="small-box bg-aqua">
                        <div class="inner">
                            <h3><?php echo $total_product; ?></h3>
                            <p>Products</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-android-cart" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
            </div>

            <!-- Pending Orders -->
            <div class="col-lg-3 col-xs-6">
                <a href="order.php" style="text-decoration: none;">
                    <div class="small-box bg-olive">
                        <div class="inner">
                            <h3><?php echo $total_order_pending; ?></h3>
                            <p>Pending Orders</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-clipboard" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
            </div>

            <!-- Pending Shippings -->
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3><?php echo $total_order_complete_shipping_pending; ?></h3>
                        <p>Pending Shippings</p>
                    </div>
                    <div class="icon">
                        <i class="ionicons ion-load-a" style="font-size: 50px; color: white;"></i>
                    </div>
                </div>
            </div>


            <!-- Completed Shipping -->
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-orange">
                    <div class="inner">
                        <h3><?php echo $total_shipping_completed; ?></h3>
                        <p>Completed Shipping</p>
                    </div>
                    <div class="icon">
                        <i class="ionicons ion-checkmark-circled" style="font-size: 50px; color: white;"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Orders -->
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3><?php echo $total_order_completed; ?></h3>
                        <p>Completed Orders</p>
                    </div>
                    <div class="icon">
                        <i class="ionicons ion-checkmark-circled" style="font-size: 50px; color: white;"></i>
                    </div>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="col-lg-3 col-xs-6">
                <a href="customer.php" style="text-decoration: none;">
                    <div class="small-box bg-maroon">
                        <div class="inner">
                            <h3><?php echo $total_customers; ?></h3>
                            <p>Active Customers</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-person-stalker" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
            </div>

            <!-- Categories -->
            <div class="col-lg-3 col-xs-6">
                <a href="top-category.php" style="text-decoration: none;">
                    <div class="small-box bg-olive">
                        <div class="inner">
                            <h3><?php echo $total_top_category; ?></h3>
                            <p>Categories</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-arrow-up-b" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Revenue -->
            <div class="col-lg-3 col-xs-6">

                    <div class="small-box bg-green">
                        <div class="inner">
                            <h3>₱<?php echo number_format((float)$total_revenue, 2); ?></h3>
                            <p>Total Sales</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-android-checkbox-outline" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
            </div>

            <!-- Payments to Verify -->
            <div class="col-lg-3 col-xs-6">
                <a href="order.php" style="text-decoration: none;">
                    <div class="small-box bg-yellow">
                        <div class="inner">
                            <h3><?php echo $total_payments_to_verify; ?></h3>
                            <p>Payments to Verify</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-card" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Outstanding Balances -->
            <div class="col-lg-3 col-xs-6">
                <a href="order.php" style="text-decoration: none;">
                    <div class="small-box bg-red">
                        <div class="inner">
                            <h3>₱<?php echo number_format((float)$total_outstanding, 2); ?></h3>
                            <p>Outstanding Balances</p>
                        </div>
                        <div class="icon">
                            <i class="ionicons ion-cash" style="font-size: 50px; color: white;"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == 'superadmin'): ?>

        <!-- Charts Section -->
        <div class="row">
            <!-- Monthly Income Chart -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>
                            <span class="glyphicon glyphicon-stats"></span>
                            <span>Monthly Income (<?php echo date('Y'); ?>)</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        
                                      <!-- Month Selection Dropdown -->
                    <label for="monthSelect">Select Sales Month:</label>
                    <select id="monthSelect">
                      <option value="all">General (All Months)</option>
                      <option value="specific">Specific Month</option>
                    </select>
                    
                    <!-- Specific Month Picker (Visible only when 'Specific Month' is selected) -->
                    <div id="specificMonthDiv" style="display:none;">
                      <label for="month">Select Specific Month:</label>
                      <input type="month" id="month" name="month">
                    </div>
                    
                    <!-- Export to PDF Button -->
                    <button id="exportBtn">Export to PDF</button>
                                        
                        
                        <div id="monthlyIncomeChart"></div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products Chart -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>
                            <span class="glyphicon glyphicon-shopping-cart"></span>
                            <span>Top Selling Products</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        <div id="topProductsChart"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            
          $(document).ready(function() {
              // Show specific month input if "Specific Month" is selected
              $('#monthSelect').change(function() {
                if ($(this).val() == 'specific') {
                  $('#specificMonthDiv').show(); // Show specific month input
                } else {
                  $('#specificMonthDiv').hide(); // Hide specific month input
                }
              });
            
              // Export PDF on button click
              $('#exportBtn').click(function() {
                var selectedMonth = $('#monthSelect').val();
                var specificMonth = selectedMonth === 'specific' ? $('#month').val().slice(5, 7) : 'all'; // Extract month from YYYY-MM format
            
                // AJAX request to fetch sales data and generate PDF
                $.ajax({
                  url: 'export-income.php',  // Your PHP file to generate PDF
                  method: 'POST',
                  data: { month: specificMonth },
                  xhrFields: {
                    responseType: 'blob'  // We are expecting a PDF response
                  },
                  success: function(response) {
                    // Create a download link for the PDF
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(response);  // Create URL for the blob
                    link.download = 'monthly_income_report.pdf'; // Set the file name
                    link.click();  // Trigger the download
                  },
                  error: function(error) {
                    console.error("Error generating PDF:", error);
                  }
                });
              });
            });
            function exportToPDF(data) {
              const doc = new jsPDF();
            
              // Title
              doc.text("Monthly Income Report", 20, 20);
              
              // Loop through the sales data and add it to the PDF
              let y = 40;  // Initial position for text
              data.forEach(item => {
                doc.text(`Product: ${item.product_name} - Sales: ${item.total_sales}`, 20, y);
                y += 10; // Move down for the next line
              });
            
              // Save the PDF file
              doc.save('monthly_income_report.pdf');
            }


        </script>
        
<?php endif; ?>
        <div class="row">
            <!-- Highest Selling Products -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>
                            <span class="glyphicon glyphicon-th"></span>
                            <span>Highest Selling Products</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-condensed">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Total Sold</th>

                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Query to get highest selling products
                            $statement = $pdo->prepare("SELECT product_name, SUM(quantity) AS total_sold, COUNT(DISTINCT product_id)
                               FROM tbl_order 
                               GROUP BY product_name 
                               ORDER BY total_sold DESC");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($result as $product) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                                echo "<td>" . (int)$product['total_sold'] . "</td>";

                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Latest Sales -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>
                            <span class="glyphicon glyphicon-th"></span>
                            <span>Latest Sales</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-condensed">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Product Name</th>

                                <th>Total Sale</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Query to get latest sales
                            $statement = $pdo->prepare("SELECT * FROM tbl_order ORDER BY id DESC LIMIT 5");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                            $count = 1;

                            foreach ($result as $sale) {
                                echo "<tr>";
                                echo "<td class='text-center'>" . $count++ . "</td>";
                                echo "<td>" . htmlspecialchars($sale['product_name']) . "</td>";

                                echo "<td>₱" . number_format((float)$sale['unit_price'] * $sale['quantity'], 2) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recently Added Products -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>
                            <span class="glyphicon glyphicon-th"></span>
                            <span>Recently Added Products</span>
                        </strong>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-condensed">
                            <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Query to get the recently added products with their categories
                            $statement = $pdo->prepare("
            SELECT p.p_name AS product_name, 
                  c.tcat_name AS category, 
                  p.p_current_price AS unit_price
            FROM tbl_product p
            JOIN tbl_category c ON p.tcat_id = c.tcat_id
            ORDER BY p.p_id DESC
            LIMIT 5
            ");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                            // Display the results in the table
                            foreach ($result as $product) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($product['category']) . "</td>";
                                echo "<td>₱" . number_format((float)$product['unit_price'], 2) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        // Chart for Monthly Income
        document.addEventListener('DOMContentLoaded', function() {
            var monthlyIncomeOptions = {
                series: [{
                    name: 'Monthly Income',
                    data: <?php echo json_encode($monthly_income); ?>
                }],
                chart: {
                    height: 350,
                    type: 'bar',
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return '₱' + val.toFixed(2);
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    position: 'bottom',
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    title: {
                        text: 'Income (₱)'
                    }
                },
                colors: ['#008FFB'],
                title: {
                    text: 'Monthly Income - <?php echo date('Y'); ?>',
                    align: 'center',
                    style: {
                        fontSize: '18px'
                    }
                }
            };

            var monthlyIncomeChart = new ApexCharts(document.querySelector("#monthlyIncomeChart"), monthlyIncomeOptions);
            monthlyIncomeChart.render();

            // Chart for Top Selling Products
            var topProductsOptions = {
                series: [{
                    name: 'Units Sold',
                    data: <?php echo json_encode($product_sales); ?>
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        dataLabels: {
                            position: 'bottom'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val;
                    },
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                xaxis: {
                    categories: <?php echo json_encode($product_names); ?>,
                },
                colors: ['#00E396'],
                title: {
                    text: 'Top Selling Products',
                    align: 'center',
                    style: {
                        fontSize: '18px'
                    }
                }
            };

            var topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), topProductsOptions);
            topProductsChart.render();
        });
    </script>

<?php require_once('footer.php'); ?>