<?php
// Include the config.php file for database connection
include_once("inc/config.php");  // Make sure this path is correct
require('fpdf186/fpdf.php');  // Include the FPDF library

// Admin only
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('Not allowed');
}

$month = isset($_POST['month']) ? $_POST['month'] : 'all';

if (isset($pdo)) {
    try {
        // Create the PDF document
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Set the title dynamically based on the selected month
        if ($month === 'all') {
            $pdf->Cell(200, 10, 'Monthly Income Report for ALL Months', 0, 1, 'C');
        } else {
            // Format the month number into a month name
            $monthName = date('F', mktime(0, 0, 0, $month, 10));
            $pdf->Cell(200, 10, "Monthly Income Report for $monthName", 0, 1, 'C');
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(100, 10, 'Month', 1);
        $pdf->Cell(100, 10, 'Total Sales (PHP)', 1);
        $pdf->Ln();

        // Fetch data based on the selected month
        if ($month === 'all') {
            // Fetch all months
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
                ORDER BY MONTH(payment_date)
            ");
            $statement->execute();
        } else {
            // Fetch data for the specific month only
            $statement = $pdo->prepare("
                SELECT 
                    MONTH(payment_date) as month,
                    SUM(paid_amount) as total
                FROM 
                    tbl_payment
                WHERE 
                    payment_status IN ('Partially Paid','Completed')
                    AND MONTH(payment_date) = ?
                    AND YEAR(payment_date) = YEAR(CURRENT_DATE)
                GROUP BY 
                    MONTH(payment_date)
            ");
            $statement->execute([$month]);
        }

        // Get the results from the query
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $pdf->SetFont('Arial', '', 12);
        foreach ($results as $row) {
            $pdf->Cell(100, 10, date('F', mktime(0, 0, 0, $row['month'], 10)), 1);
            $pdf->Cell(100, 10, 'PHP ' . number_format($row['total'], 2), 1);
            $pdf->Ln();
        }

        // Output the PDF as a blob for download
        $pdf->Output('I', 'monthly_income_report.pdf');
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Database connection error!";
}
?>
