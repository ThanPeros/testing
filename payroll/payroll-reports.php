<?php
include '../sidebar.php';
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$report_data = [];
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_report'])) {
        $report_type = $_POST['report_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Validate dates
        if ($start_date > $end_date) {
            $error_message = "Error: Start date cannot be after end date.";
        } else {
            // Generate the requested report
            switch ($report_type) {
                case 'payroll_register':
                    $report_data = generatePayrollRegister($conn, $start_date, $end_date);
                    break;
                case 'sss_report':
                    $report_data = generateSSSReport($conn, $start_date, $end_date);
                    break;
                case 'philhealth_report':
                    $report_data = generatePhilHealthReport($conn, $start_date, $end_date);
                    break;
                case 'pagibig_report':
                    $report_data = generatePagIBIGReport($conn, $start_date, $end_date);
                    break;
                case 'tax_report':
                    $report_data = generateTaxReport($conn, $start_date, $end_date);
                    break;
                case 'departmental_report':
                    $report_data = generateDepartmentalReport($conn, $start_date, $end_date);
                    break;
                case 'employee_earnings':
                    $employee_id = $_POST['employee_id'];
                    $report_data = generateEmployeeEarningsReport($conn, $start_date, $end_date, $employee_id);
                    break;
                default:
                    $report_data = [];
            }

            if (empty($report_data)) {
                $success_message = "No records found for the selected criteria.";
            }
        }
    }
}

// Report generation functions
function generatePayrollRegister($conn, $start_date, $end_date)
{
    $sql = "SELECT e.id, CONCAT(e.first_name, ' ', e.last_name) as employee_name, 
                   p.gross_pay, p.sss_contribution, p.philhealth_contribution, 
                   p.pagibig_contribution, p.withholding_tax, p.other_deductions, p.net_pay,
                   p.pay_period_start, p.pay_period_end, e.department
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ORDER BY e.department, e.last_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateSSSReport($conn, $start_date, $end_date)
{
    $sql = "SELECT e.id, e.sss_number, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   p.sss_contribution, p.pay_period_start, p.pay_period_end, e.department
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ORDER BY e.department, e.sss_number";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generatePhilHealthReport($conn, $start_date, $end_date)
{
    $sql = "SELECT e.id, e.philhealth_number, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   p.philhealth_contribution, p.pay_period_start, p.pay_period_end, e.department
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ORDER BY e.department, e.philhealth_number";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generatePagIBIGReport($conn, $start_date, $end_date)
{
    $sql = "SELECT e.id, e.pagibig_number, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   p.pagibig_contribution, p.pay_period_start, p.pay_period_end, e.department
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ORDER BY e.department, e.pagibig_number";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateTaxReport($conn, $start_date, $end_date)
{
    $sql = "SELECT e.id, e.tin_number, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   p.withholding_tax, p.gross_pay, p.pay_period_start, p.pay_period_end, e.department
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ORDER BY e.department, e.tin_number";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateDepartmentalReport($conn, $start_date, $end_date)
{
    $sql = "SELECT e.department, COUNT(DISTINCT e.id) as employee_count, 
                   SUM(p.gross_pay) as total_gross, 
                   SUM(p.sss_contribution) as total_sss, 
                   SUM(p.philhealth_contribution) as total_philhealth,
                   SUM(p.pagibig_contribution) as total_pagibig,
                   SUM(p.withholding_tax) as total_tax,
                   SUM(p.other_deductions) as total_other_deductions,
                   SUM(p.net_pay) as total_net
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            GROUP BY e.department
            ORDER BY e.department";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateEmployeeEarningsReport($conn, $start_date, $end_date, $employee_id)
{
    $sql = "SELECT e.id, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   p.gross_pay, p.sss_contribution, p.philhealth_contribution, 
                   p.pagibig_contribution, p.withholding_tax, p.other_deductions, p.net_pay,
                   p.pay_period_start, p.pay_period_end, e.department, p.payment_date
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ? AND e.id = ?
            ORDER BY p.pay_period_start";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $start_date, $end_date, $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get employee list for dropdown
function getEmployees($conn)
{
    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees ORDER BY last_name";
    $result = $conn->query($sql);
    $employees = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    return $employees;
}

$employees = getEmployees($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Reports System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        header p {
            text-align: center;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-color);
        }

        select,
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            background: var(--secondary-color);
        }

        .report-container {
            margin-top: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .total-row {
            font-weight: bold;
            background-color: var(--light-color);
        }

        .export-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-export {
            background: var(--success-color);
        }

        .btn-export:hover {
            background: #219653;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #ef5350;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #66bb6a;
        }

        .filter-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-options>div {
            flex: 1;
            min-width: 250px;
        }

        .employee-select {
            display: <?php echo (isset($_POST['report_type']) && $_POST['report_type'] == 'employee_earnings') ? 'block' : 'none'; ?>;
        }

        .summary-cards {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .summary-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card h3 {
            font-size: 1rem;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .summary-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 10px;
            }

            header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 15px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }

            .filter-options {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-file-invoice-dollar"></i> Payroll Reports System</h1>
            <p>Generate comprehensive payroll reports for HR, Finance, and Compliance</p>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message) && empty($report_data)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title"><i class="fas fa-cog"></i> Report Parameters</h2>
            <form method="POST" action="">
                <div class="filter-options">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select name="report_type" id="report_type" required onchange="toggleEmployeeSelect()">
                            <option value="">Select Report Type</option>
                            <option value="payroll_register" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'payroll_register') echo 'selected'; ?>>Payroll Register (Gross, Deductions, Net)</option>
                            <option value="sss_report" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'sss_report') echo 'selected'; ?>>SSS Contribution Report</option>
                            <option value="philhealth_report" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'philhealth_report') echo 'selected'; ?>>PhilHealth Contribution Report</option>
                            <option value="pagibig_report" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'pagibig_report') echo 'selected'; ?>>Pag-IBIG Contribution Report</option>
                            <option value="tax_report" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'tax_report') echo 'selected'; ?>>Tax Withholding Report</option>
                            <option value="departmental_report" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'departmental_report') echo 'selected'; ?>>Departmental Salary Cost Report</option>
                            <option value="employee_earnings" <?php if (isset($_POST['report_type']) && $_POST['report_type'] == 'employee_earnings') echo 'selected'; ?>>Employee Earnings Report</option>
                        </select>
                    </div>

                    <div class="form-group employee-select" id="employeeSelect">
                        <label for="employee_id">Select Employee</label>
                        <select name="employee_id" id="employee_id">
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" <?php if (isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['id']) echo 'selected'; ?>>
                                    <?php echo $employee['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t'); ?>" required>
                    </div>
                </div>

                <button type="submit" name="generate_report"><i class="fas fa-chart-bar"></i> Generate Report</button>
            </form>
        </div>

        <?php if (isset($report_data) && !empty($report_data)): ?>
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-file-alt"></i>
                    <?php
                    $report_titles = [
                        'payroll_register' => 'Payroll Register',
                        'sss_report' => 'SSS Contribution Report',
                        'philhealth_report' => 'PhilHealth Contribution Report',
                        'pagibig_report' => 'Pag-IBIG Contribution Report',
                        'tax_report' => 'Tax Withholding Report',
                        'departmental_report' => 'Departmental Salary Cost Report',
                        'employee_earnings' => 'Employee Earnings Report'
                    ];
                    echo $report_titles[$report_type] . ' (' . $start_date . ' to ' . $end_date . ')';
                    ?>
                </h2>

                <?php if ($report_type == 'departmental_report'): ?>
                    <div class="summary-cards">
                        <div class="summary-card">
                            <h3>Total Departments</h3>
                            <div class="value"><?php echo count($report_data); ?></div>
                        </div>
                        <div class="summary-card">
                            <h3>Total Gross Pay</h3>
                            <div class="value">₱<?php
                                                $total_gross = 0;
                                                foreach ($report_data as $row) {
                                                    $total_gross += $row['total_gross'];
                                                }
                                                echo number_format($total_gross, 2);
                                                ?></div>
                        </div>
                        <div class="summary-card">
                            <h3>Total Net Pay</h3>
                            <div class="value">₱<?php
                                                $total_net = 0;
                                                foreach ($report_data as $row) {
                                                    $total_net += $row['total_net'];
                                                }
                                                echo number_format($total_net, 2);
                                                ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="report-container">
                    <table>
                        <thead>
                            <tr>
                                <?php
                                // Display table headers based on report type
                                if (!empty($report_data)) {
                                    foreach (array_keys($report_data[0]) as $column) {
                                        echo '<th>' . ucwords(str_replace('_', ' ', $column)) . '</th>';
                                    }

                                    // Add total row if applicable
                                    if ($report_type == 'departmental_report') {
                                        echo '<th>Actions</th>';
                                    }
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totals = [];

                            foreach ($report_data as $row) {
                                echo '<tr>';
                                foreach ($row as $key => $value) {
                                    // Format numeric values
                                    if (is_numeric($value) && in_array($key, [
                                        'gross_pay',
                                        'sss_contribution',
                                        'philhealth_contribution',
                                        'pagibig_contribution',
                                        'withholding_tax',
                                        'other_deductions',
                                        'net_pay',
                                        'total_gross',
                                        'total_sss',
                                        'total_philhealth',
                                        'total_pagibig',
                                        'total_tax',
                                        'total_other_deductions',
                                        'total_net',
                                        'employee_count'
                                    ])) {
                                        if (in_array($key, ['employee_count'])) {
                                            echo '<td>' . number_format($value) . '</td>';
                                        } else {
                                            echo '<td>₱' . number_format($value, 2) . '</td>';
                                        }

                                        // Calculate totals
                                        if (!isset($totals[$key])) $totals[$key] = 0;
                                        $totals[$key] += $value;
                                    } else {
                                        echo '<td>' . htmlspecialchars($value) . '</td>';
                                    }
                                }

                                // Add view details button for departmental report
                                if ($report_type == 'departmental_report') {
                                    echo '<td><button onclick="viewDepartmentDetails(\'' . $row['department'] . '\', \'' . $start_date . '\', \'' . $end_date . '\')"><i class="fas fa-eye"></i> Details</button></td>';
                                }

                                echo '</tr>';
                            }

                            // Display totals row if applicable
                            if (!empty($totals) && $report_type != 'departmental_report') {
                                echo '<tr class="total-row">';
                                echo '<td colspan="' . (count($report_data[0]) - count($totals)) . '">TOTAL</td>';

                                foreach ($report_data[0] as $key => $value) {
                                    if (isset($totals[$key])) {
                                        if (in_array($key, ['employee_count'])) {
                                            echo '<td>' . number_format($totals[$key]) . '</td>';
                                        } else {
                                            echo '<td>₱' . number_format($totals[$key], 2) . '</td>';
                                        }
                                    }
                                }

                                if ($report_type == 'departmental_report') {
                                    echo '<td></td>';
                                }

                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="export-buttons">
                    <button class="btn-export" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Export to CSV</button>
                    <button class="btn-export" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Export to Excel</button>
                    <button class="btn-export" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Set default date range to current month
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

            document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];

            // Check if we need to show employee select
            toggleEmployeeSelect();
        });

        function toggleEmployeeSelect() {
            const reportType = document.getElementById('report_type').value;
            const employeeSelect = document.getElementById('employeeSelect');

            if (reportType === 'employee_earnings') {
                employeeSelect.style.display = 'block';
                document.getElementById('employee_id').setAttribute('required', 'required');
            } else {
                employeeSelect.style.display = 'none';
                document.getElementById('employee_id').removeAttribute('required');
            }
        }

        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll('table tr');

            for (let i = 0; i < rows.length; i++) {
                let row = [],
                    cols = rows[i].querySelectorAll('td, th');

                for (let j = 0; j < cols.length; j++) {
                    // Remove currency symbol and commas for proper CSV formatting
                    let text = cols[j].innerText.replace(/[₱,]/g, '');
                    row.push('"' + text + '"');
                }

                csv.push(row.join(','));
            }

            // Download CSV file
            let csvString = csv.join('\n');
            let a = document.createElement('a');
            a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString);
            a.download = 'payroll_report_<?php echo isset($report_type) ? $report_type : ''; ?>_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
        }

        function exportToExcel() {
            let table = document.querySelector('table');
            let html = table.outerHTML;
            let blob = new Blob([html], {
                type: 'application/vnd.ms-excel'
            });
            let a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'payroll_report_<?php echo isset($report_type) ? $report_type : ''; ?>_<?php echo date('Y-m-d'); ?>.xls';
            a.click();
        }

        function viewDepartmentDetails(department, startDate, endDate) {
            // In a real implementation, this would redirect to a department details page
            // or fetch data via AJAX
            alert('Viewing details for ' + department + ' department from ' + startDate + ' to ' + endDate);
        }
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();
?>