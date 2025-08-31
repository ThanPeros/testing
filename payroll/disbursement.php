<?php
session_start();
include '../sidebar.php';
// Database configuration
$host = 'localhost';
$dbname = 'systems'; // Replace with your actual database name
$username = 'root';    // Replace with your actual username
$password = '';    // Replace with your actual password

// Try connecting to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if we need to add a bank_account column to employees table
try {
    $pdo->query("SELECT bank_account FROM employees LIMIT 1");
} catch (PDOException $e) {
    // Column doesn't exist, so add it
    $pdo->exec("ALTER TABLE employees ADD COLUMN bank_account VARCHAR(50) NULL AFTER position");
}

// Check if we need to add a payment_method column to employees table
try {
    $pdo->query("SELECT payment_method FROM employees LIMIT 1");
} catch (PDOException $e) {
    // Column doesn't exist, so add it
    $pdo->exec("ALTER TABLE employees ADD COLUMN payment_method ENUM('bank', 'check', 'cash') DEFAULT 'bank' AFTER bank_account");
}

// Check if we need to create the disbursements table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS disbursements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        amount DECIMAL(12, 2) NOT NULL,
        payment_method ENUM('bank', 'check', 'cash') NOT NULL,
        disbursement_date DATE NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        reference VARCHAR(100),
        pay_period_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_employee'])) {
        // Update employee payment details
        $stmt = $pdo->prepare("UPDATE employees SET bank_account = ?, payment_method = ? WHERE id = ?");
        $stmt->execute([$_POST['bank_account'], $_POST['payment_method'], $_POST['employee_id']]);
        $_SESSION['message'] = "Employee details updated successfully!";
    } elseif (isset($_POST['process_payment'])) {
        // Process payment for selected employees
        if (!empty($_POST['selected_employees'])) {
            $payPeriodId = $_POST['pay_period'];
            $paymentMethod = $_POST['payment_method'];

            foreach ($_POST['selected_employees'] as $employeeId) {
                // Get employee details
                $stmt = $pdo->prepare("
                    SELECT e.*, ec.gross_pay 
                    FROM employees e 
                    JOIN earnings_calculations ec ON e.id = ec.employee_id 
                    WHERE e.id = ? AND ec.pay_period_id = ?
                ");
                $stmt->execute([$employeeId, $payPeriodId]);
                $employee = $stmt->fetch();

                if ($employee) {
                    // Insert into disbursements table
                    $stmt = $pdo->prepare("
                        INSERT INTO disbursements (employee_id, amount, payment_method, disbursement_date, status, pay_period_id) 
                        VALUES (?, ?, ?, CURDATE(), 'completed', ?)
                    ");
                    $stmt->execute([$employeeId, $employee['gross_pay'], $paymentMethod, $payPeriodId]);
                }
            }
            $_SESSION['message'] = "Payments processed successfully!";
        }
    } elseif (isset($_POST['generate_bank_file'])) {
        // Generate bank file for bank transfers
        $payPeriodId = $_POST['pay_period'];

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="bank_transfers_' . date('Ymd') . '.txt"');

        $stmt = $pdo->prepare("
            SELECT e.first_name, e.last_name, e.bank_account, ec.gross_pay 
            FROM employees e 
            JOIN earnings_calculations ec ON e.id = ec.employee_id 
            WHERE ec.pay_period_id = ? AND e.payment_method = 'bank' AND e.bank_account IS NOT NULL AND e.bank_account != ''
        ");
        $stmt->execute([$payPeriodId]);
        $employees = $stmt->fetchAll();

        echo "Bank Transfer File - Generated on " . date('Y-m-d') . "\n";
        echo "==================================================\n\n";

        $totalAmount = 0;
        foreach ($employees as $employee) {
            $account = str_pad(str_replace(' ', '', $employee['bank_account']), 20, '0', STR_PAD_LEFT);
            $amount = number_format($employee['gross_pay'], 2, '.', '');
            $totalAmount += $employee['gross_pay'];

            echo $account . "|" . str_pad($amount, 15, '0', STR_PAD_LEFT) . "|" .
                $employee['first_name'] . " " . $employee['last_name'] . "\n";
        }

        echo "\n==================================================\n";
        echo "Total Amount: " . number_format($totalAmount, 2) . "\n";
        echo "Total Transactions: " . count($employees) . "\n";
        exit();
    }
}

// Fetch data for display
$employees = $pdo->query("
    SELECT e.*, ec.gross_pay, pp.period_name 
    FROM employees e 
    JOIN earnings_calculations ec ON e.id = ec.employee_id 
    JOIN pay_periods pp ON ec.pay_period_id = pp.id 
    WHERE pp.is_current = 1 AND e.status = 'approved'
")->fetchAll();

$payPeriods = $pdo->query("SELECT * FROM pay_periods ORDER BY start_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 20px 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            text-align: center;
            font-size: 2.5rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        select,
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        button:hover {
            background: #2980b9;
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
            background-color: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f7fa;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            background: #e9ecef;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            cursor: pointer;
        }

        .tab.active {
            background: #3498db;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-method {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #3498db;
        }

        .payment-method.selected {
            border-color: #3498db;
            background-color: #e8f4fd;
        }

        .payment-method h3 {
            margin-bottom: 10px;
        }

        .summary-box {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            flex: 1;
            margin: 0 10px;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 50%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .payment-methods {
                flex-direction: column;
            }

            .summary-box {
                flex-direction: column;
            }

            .summary-item {
                margin: 10px 0;
            }

            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Disbursement System</h1>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?php echo $_SESSION['message'];
                                    unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('process')">Process Payments</div>
            <div class="tab" onclick="switchTab('history')">Payment History</div>
            <div class="tab" onclick="switchTab('reports')">Reports</div>
            <div class="tab" onclick="switchTab('employees')">Employee Settings</div>
        </div>

        <div id="process" class="tab-content active">
            <div class="card">
                <h2>Select Pay Period</h2>
                <form method="POST" id="paymentForm">
                    <div class="form-group">
                        <label for="pay_period">Pay Period:</label>
                        <select name="pay_period" id="pay_period" required>
                            <?php foreach ($payPeriods as $period): ?>
                                <option value="<?php echo $period['id']; ?>" <?php echo $period['is_current'] ? 'selected' : ''; ?>>
                                    <?php echo $period['period_name'] . ' (' . $period['start_date'] . ' to ' . $period['end_date'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h2>Select Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-method" onclick="selectPaymentMethod('bank')">
                            <h3>Bank Transfer</h3>
                            <p>Direct deposit to employee bank accounts</p>
                            <input type="radio" name="payment_method" value="bank" required style="display: none;">
                        </div>
                        <div class="payment-method" onclick="selectPaymentMethod('check')">
                            <h3>Check Printing</h3>
                            <p>Generate checks for manual distribution</p>
                            <input type="radio" name="payment_method" value="check" required style="display: none;">
                        </div>
                        <div class="payment-method" onclick="selectPaymentMethod('cash')">
                            <h3>Cash Payout</h3>
                            <p>Cash payments for selected employees</p>
                            <input type="radio" name="payment_method" value="cash" required style="display: none;">
                        </div>
                    </div>

                    <h2>Select Employees</h2>
                    <div class="form-group">
                        <button type="button" onclick="selectAllEmployees()">Select All</button>
                        <button type="button" onclick="deselectAllEmployees()">Deselect All</button>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Gross Pay</th>
                                <th>Payment Method</th>
                                <th>Bank Account</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_employees[]" value="<?php echo $employee['id']; ?>" class="employee-checkbox"></td>
                                    <td><?php echo $employee['id']; ?></td>
                                    <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                    <td><?php echo $employee['department']; ?></td>
                                    <td><?php echo $employee['position']; ?></td>
                                    <td><?php echo number_format($employee['gross_pay'], 2); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($employee['payment_method'])) {
                                            echo ucfirst($employee['payment_method']);
                                        } else {
                                            echo "Not set";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($employee['bank_account']) ? $employee['bank_account'] : 'Not set'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" name="process_payment">Process Payment</button>
                        <button type="submit" name="generate_bank_file" style="background: #27ae60;">Generate Bank File</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="history" class="tab-content">
            <div class="card">
                <h2>Payment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Pay Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $history = $pdo->query("
                                SELECT d.*, e.first_name, e.last_name, pp.period_name 
                                FROM disbursements d 
                                JOIN employees e ON d.employee_id = e.id 
                                JOIN pay_periods pp ON d.pay_period_id = pp.id 
                                ORDER BY d.disbursement_date DESC 
                                LIMIT 50
                            ")->fetchAll();

                            foreach ($history as $record): ?>
                                <tr>
                                    <td><?php echo $record['disbursement_date']; ?></td>
                                    <td><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                    <td><?php echo number_format($record['amount'], 2); ?></td>
                                    <td><?php echo ucfirst($record['payment_method']); ?></td>
                                    <td><?php echo ucfirst($record['status']); ?></td>
                                    <td><?php echo $record['period_name']; ?></td>
                                </tr>
                        <?php endforeach;
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='6'>No payment history found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="reports" class="tab-content">
            <div class="card">
                <h2>Disbursement Reports</h2>

                <div class="summary-box">
                    <div class="summary-item">
                        <div class="summary-label">Total Paid This Month</div>
                        <div class="summary-value">
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT SUM(amount) as total 
                                    FROM disbursements 
                                    WHERE MONTH(disbursement_date) = MONTH(CURDATE()) 
                                    AND YEAR(disbursement_date) = YEAR(CURDATE())
                                    AND status = 'completed'
                                ");
                                $stmt->execute();
                                $total = $stmt->fetch()['total'];
                                echo number_format($total, 2);
                            } catch (PDOException $e) {
                                echo "0.00";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Employees Paid</div>
                        <div class="summary-value">
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(DISTINCT employee_id) as count 
                                    FROM disbursements 
                                    WHERE MONTH(disbursement_date) = MONTH(CURDATE()) 
                                    AND YEAR(disbursement_date) = YEAR(CURDATE())
                                    AND status = 'completed'
                                ");
                                $stmt->execute();
                                $count = $stmt->fetch()['count'];
                                echo $count;
                            } catch (PDOException $e) {
                                echo "0";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Most Common Method</div>
                        <div class="summary-value">
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT payment_method, COUNT(*) as count 
                                    FROM disbursements 
                                    WHERE MONTH(disbursement_date) = MONTH(CURDATE()) 
                                    AND YEAR(disbursement_date) = YEAR(CURDATE())
                                    AND status = 'completed'
                                    GROUP BY payment_method 
                                    ORDER BY count DESC 
                                    LIMIT 1
                                ");
                                $stmt->execute();
                                $method = $stmt->fetch();
                                echo $method ? ucfirst($method['payment_method']) : 'N/A';
                            } catch (PDOException $e) {
                                echo "N/A";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <h3>Payment Methods Distribution</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
                                FROM disbursements 
                                WHERE MONTH(disbursement_date) = MONTH(CURDATE()) 
                                AND YEAR(disbursement_date) = YEAR(CURDATE())
                                AND status = 'completed'
                                GROUP BY payment_method
                            ");
                            $stmt->execute();
                            $methods = $stmt->fetchAll();

                            $stmt = $pdo->prepare("
                                SELECT SUM(amount) as grand_total 
                                FROM disbursements 
                                WHERE MONTH(disbursement_date) = MONTH(CURDATE()) 
                                AND YEAR(disbursement_date) = YEAR(CURDATE())
                                AND status = 'completed'
                            ");
                            $stmt->execute();
                            $grandTotal = $stmt->fetch()['grand_total'];

                            foreach ($methods as $method):
                                $percentage = $grandTotal > 0 ? ($method['total'] / $grandTotal) * 100 : 0;
                        ?>
                                <tr>
                                    <td><?php echo ucfirst($method['payment_method']); ?></td>
                                    <td><?php echo $method['count']; ?></td>
                                    <td><?php echo number_format($method['total'], 2); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                        <?php endforeach;
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='4'>No data available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="employees" class="tab-content">
            <div class="card">
                <h2>Employee Payment Settings</h2>
                <p>Update employee payment methods and bank account information.</p>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Payment Method</th>
                            <th>Bank Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $allEmployees = $pdo->query("SELECT * FROM employees WHERE status = 'approved'")->fetchAll();
                        foreach ($allEmployees as $employee): ?>
                            <tr>
                                <td><?php echo $employee['id']; ?></td>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                <td><?php echo $employee['department']; ?></td>
                                <td><?php echo $employee['position']; ?></td>
                                <td><?php echo !empty($employee['payment_method']) ? ucfirst($employee['payment_method']) : 'Not set'; ?></td>
                                <td><?php echo !empty($employee['bank_account']) ? $employee['bank_account'] : 'Not set'; ?></td>
                                <td>
                                    <button onclick="openEditModal(<?php echo $employee['id']; ?>, '<?php echo $employee['payment_method']; ?>', '<?php echo $employee['bank_account']; ?>')">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Employee Payment Details</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="employee_id" id="editEmployeeId">
                <div class="form-group">
                    <label for="editPaymentMethod">Payment Method:</label>
                    <select name="payment_method" id="editPaymentMethod" required>
                        <option value="bank">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editBankAccount">Bank Account Number (if applicable):</label>
                    <input type="text" name="bank_account" id="editBankAccount" placeholder="Enter bank account number">
                </div>
                <button type="submit" name="update_employee">Update Details</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        function selectPaymentMethod(method) {
            // Update UI
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Update radio button
            document.querySelector(`input[value="${method}"]`).checked = true;
        }

        function toggleSelectAll(checkbox) {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            employeeCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }

        function selectAllEmployees() {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            employeeCheckboxes.forEach(cb => {
                cb.checked = true;
            });
            document.getElementById('selectAll').checked = true;
        }

        function deselectAllEmployees() {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            employeeCheckboxes.forEach(cb => {
                cb.checked = false;
            });
            document.getElementById('selectAll').checked = false;
        }

        function openEditModal(id, method, bankAccount) {
            document.getElementById('editEmployeeId').value = id;
            document.getElementById('editPaymentMethod').value = method || 'bank';
            document.getElementById('editBankAccount').value = bankAccount || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Initialize payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            // Select bank transfer by default
            selectPaymentMethod('bank');
        });
    </script>
</body>

</html>