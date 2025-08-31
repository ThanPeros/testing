<?php
include '../sidebar.php';
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'systems');

// Connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$pay_period_id = isset($_POST['pay_period_id']) ? intval($_POST['pay_period_id']) : 0;
$payslip_data = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee_id && $pay_period_id) {
    $payslip_data = getPayslipData($conn, $employee_id, $pay_period_id);
    if (!$payslip_data) {
        $error = "No payslip data found for the selected employee and pay period.";
    }
}

// Function to get payslip data
function getPayslipData($conn, $employee_id, $pay_period_id)
{
    // Get employee details
    $employee_sql = "SELECT e.id, e.first_name, e.last_name, e.position, e.department 
                     FROM employees e WHERE e.id = ?";
    $stmt = $conn->prepare($employee_sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee_result = $stmt->get_result();

    if ($employee_result->num_rows === 0) {
        return false;
    }

    $data = $employee_result->fetch_assoc();

    // Get pay period details
    $period_sql = "SELECT period_name, start_date, end_date FROM pay_periods WHERE id = ?";
    $stmt = $conn->prepare($period_sql);
    $stmt->bind_param("i", $pay_period_id);
    $stmt->execute();
    $period_result = $stmt->get_result();

    if ($period_result->num_rows === 0) {
        return false;
    }

    $period_data = $period_result->fetch_assoc();
    $data['pay_period'] = $period_data['period_name'];
    $data['start_date'] = $period_data['start_date'];
    $data['end_date'] = $period_data['end_date'];

    // Get earnings calculations
    $earnings_sql = "SELECT base_pay, overtime_pay, holiday_pay, night_differential, 
                     incentives, bonuses, allowances, gross_pay 
                     FROM earnings_calculations 
                     WHERE employee_id = ? AND pay_period_id = ?";
    $stmt = $conn->prepare($earnings_sql);
    $stmt->bind_param("ii", $employee_id, $pay_period_id);
    $stmt->execute();
    $earnings_result = $stmt->get_result();

    if ($earnings_result->num_rows === 0) {
        return false;
    }

    $earnings_data = $earnings_result->fetch_assoc();
    $data = array_merge($data, $earnings_data);

    // Get deductions - Updated to work with your employee_deduction table structure
    $deductions_sql = "SELECT ed.amount, ed.description, ed.deduction_date 
                       FROM employee_deductions ed
                       WHERE ed.employee_id = ?";
    $stmt = $conn->prepare($deductions_sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $deductions_result = $stmt->get_result();

    $deductions = [];
    $total_deductions = 0;

    while ($row = $deductions_result->fetch_assoc()) {
        $deductions[] = $row;
        $total_deductions += $row['amount'];
    }

    $data['deductions'] = $deductions;
    $data['total_deductions'] = $total_deductions;
    $data['net_pay'] = $data['gross_pay'] - $total_deductions;

    return $data;
}

// Function to generate PDF (simplified version)
function generatePDF($payslip_data)
{
    // In a real implementation, you would use a library like TCPDF or Dompdf
    // This is a simplified version that just returns a downloadable link

    $filename = "payslip_{$payslip_data['first_name']}_{$payslip_data['last_name']}_{$payslip_data['pay_period']}.pdf";

    // For demonstration purposes, we'll just return the filename
    // In a real implementation, you would generate the actual PDF file
    return $filename;
}

// Get employees and pay periods for dropdowns
$employees_sql = "SELECT id, first_name, last_name FROM employees ORDER BY first_name, last_name";
$employees_result = $conn->query($employees_sql);

$periods_sql = "SELECT id, period_name, start_date, end_date FROM pay_periods ORDER BY start_date DESC";
$periods_result = $conn->query($periods_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payslip Management</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        h1 {
            font-size: 2.5rem;
        }

        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select,
        button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .payslip-container {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .payslip-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .payslip-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .earnings,
        .deductions {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .net-pay {
            font-weight: bold;
            font-size: 24px;
            color: #2c3e50;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .error {
            background-color: #ffdddd;
            color: #d63031;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media print {

            .form-container,
            .actions {
                display: none;
            }

            .payslip-container {
                box-shadow: none;
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .payslip-details {
                grid-template-columns: 1fr;
            }
        }

        .currency-symbol {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Employee Payslip Management</h1>
        </header>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="employee_id">Select Employee:</label>
                    <select name="employee_id" id="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php while ($row = $employees_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php echo $employee_id == $row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pay_period_id">Select Pay Period:</label>
                    <select name="pay_period_id" id="pay_period_id" required>
                        <option value="">-- Select Pay Period --</option>
                        <?php
                        if ($periods_result->num_rows > 0) {
                            $periods_result->data_seek(0); // Reset pointer
                            while ($row = $periods_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo $pay_period_id == $row['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['period_name'] . ' (' . $row['start_date'] . ' to ' . $row['end_date'] . ')'); ?>
                                </option>
                        <?php endwhile;
                        } ?>
                    </select>
                </div>
                <button type="submit">View Payslip</button>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($payslip_data): ?>
            <div class="payslip-container">
                <div class="payslip-header">
                    <h2>PAYSLIP</h2>
                    <p><?php echo $payslip_data['pay_period']; ?> (<?php echo $payslip_data['start_date']; ?> to <?php echo $payslip_data['end_date']; ?>)</p>
                </div>

                <div class="payslip-details">
                    <div>
                        <h3>Employee Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($payslip_data['first_name'] . ' ' . $payslip_data['last_name']); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($payslip_data['position']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($payslip_data['department']); ?></p>
                    </div>
                    <div>
                        <h3>Pay Details</h3>
                        <p><strong>Pay Period:</strong> <?php echo $payslip_data['pay_period']; ?></p>
                        <p><strong>Payment Date:</strong> <?php echo date('Y-m-d'); ?></p>
                        <p><strong>Employee ID:</strong> <?php echo $payslip_data['id']; ?></p>
                    </div>
                </div>

                <div class="earnings">
                    <h3>Earnings</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Base Pay</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['base_pay'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Overtime Pay</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['overtime_pay'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Holiday Pay</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['holiday_pay'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Night Differential</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['night_differential'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Incentives</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['incentives'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Bonuses</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['bonuses'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Allowances</td>
                                <td><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['allowances'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="deductions">
                    <h3>Deductions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payslip_data['deductions'] as $deduction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($deduction['description']); ?></td>
                                    <td><?php echo htmlspecialchars($deduction['deduction_date']); ?></td>
                                    <td><span class="currency-symbol">₱</span><?php echo number_format($deduction['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary">
                    <div class="summary-item">
                        <span>Gross Pay:</span>
                        <span><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['gross_pay'], 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Total Deductions:</span>
                        <span><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['total_deductions'], 2); ?></span>
                    </div>
                    <div class="summary-item net-pay">
                        <span>Net Pay:</span>
                        <span><span class="currency-symbol">₱</span><?php echo number_format($payslip_data['net_pay'], 2); ?></span>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" onclick="window.print()">Print Payslip</button>
                    <button class="btn btn-success" onclick="generatePDF(<?php echo $employee_id; ?>, <?php echo $pay_period_id; ?>)">Download PDF</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function generatePDF(employeeId, payPeriodId) {
            // In a real implementation, this would call a server-side script to generate the PDF
            alert('PDF generation would be implemented here. This would download the payslip for Employee ID: ' + employeeId + ' and Pay Period ID: ' + payPeriodId);

            // Example of how this would work with a real implementation:
            // window.location.href = 'generate_pdf.php?employee_id=' + employeeId + '&pay_period_id=' + payPeriodId;
        }
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();
?>