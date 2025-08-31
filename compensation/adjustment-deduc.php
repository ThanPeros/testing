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

// Initialize message variable
$message = '';

// Create salary_adjustments table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS salary_adjustments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(6) UNSIGNED NOT NULL,
    adjustment_type ENUM('promotion', 'merit', 'retro_pay', 'correction', 'deduction') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("Error creating salary_adjustments table: " . $conn->error);
}

// Create deduction_rules table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS deduction_rules (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    deduction_type ENUM('fixed', 'percentage') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    applies_to_all BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating deduction_rules table: " . $conn->error);
}

// Create employee_deductions table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS employee_deductions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(6) UNSIGNED NOT NULL,
    rule_id INT(6) UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    deduction_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (rule_id) REFERENCES deduction_rules(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("Error creating employee_deductions table: " . $conn->error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle salary adjustment form submission
    if (isset($_POST['add_adjustment'])) {
        $employee_id = $_POST['employee_id'];
        $adjustment_type = $_POST['adjustment_type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $effective_date = $_POST['effective_date'];

        $stmt = $conn->prepare("INSERT INTO salary_adjustments (employee_id, adjustment_type, amount, description, effective_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $employee_id, $adjustment_type, $amount, $description, $effective_date);

        if ($stmt->execute()) {
            $message = "Salary adjustment added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Handle deduction rule form submission
    if (isset($_POST['add_deduction_rule'])) {
        $rule_name = $_POST['rule_name'];
        $deduction_type = $_POST['deduction_type'];
        $amount = $_POST['rule_amount'];
        $description = $_POST['rule_description'];
        $applies_to_all = isset($_POST['applies_to_all']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO deduction_rules (rule_name, deduction_type, amount, description, applies_to_all) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $rule_name, $deduction_type, $amount, $description, $applies_to_all);

        if ($stmt->execute()) {
            $message = "Deduction rule added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Handle employee deduction form submission
    if (isset($_POST['add_employee_deduction'])) {
        $employee_id = $_POST['deduction_employee_id'];
        $rule_id = $_POST['rule_id'];
        $amount = $_POST['deduction_amount'];
        $deduction_date = $_POST['deduction_date'];
        $description = $_POST['deduction_description'];

        $stmt = $conn->prepare("INSERT INTO employee_deductions (employee_id, rule_id, amount, deduction_date, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $employee_id, $rule_id, $amount, $deduction_date, $description);

        if ($stmt->execute()) {
            $message = "Employee deduction added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all employees
$employees_result = $conn->query("SELECT * FROM employees ORDER BY first_name, last_name");
$employees = [];
if ($employees_result->num_rows > 0) {
    while ($row = $employees_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Fetch all salary adjustments
$adjustments_result = $conn->query("
    SELECT sa.*, e.first_name, e.last_name 
    FROM salary_adjustments sa 
    JOIN employees e ON sa.employee_id = e.id 
    ORDER BY sa.effective_date DESC
");
$adjustments = [];
if ($adjustments_result->num_rows > 0) {
    while ($row = $adjustments_result->fetch_assoc()) {
        $adjustments[] = $row;
    }
}

// Fetch all deduction rules
$rules_result = $conn->query("SELECT * FROM deduction_rules WHERE is_active = TRUE ORDER BY rule_name");
$deduction_rules = [];
if ($rules_result->num_rows > 0) {
    while ($row = $rules_result->fetch_assoc()) {
        $deduction_rules[] = $row;
    }
}

// Fetch all employee deductions
$deductions_result = $conn->query("
    SELECT ed.*, e.first_name, e.last_name, dr.rule_name 
    FROM employee_deductions ed 
    JOIN employees e ON ed.employee_id = e.id 
    JOIN deduction_rules dr ON ed.rule_id = dr.id 
    ORDER BY ed.deduction_date DESC
");
$employee_deductions = [];
if ($deductions_result->num_rows > 0) {
    while ($row = $deductions_result->fetch_assoc()) {
        $employee_deductions[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Adjustments & Deductions</title>
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
            padding: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-section {
            flex: 1;
            min-width: 300px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4b6cb7;
            color: #182848;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #182848;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #4b6cb7;
            outline: none;
        }

        button {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .message {
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            color: #182848;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }

        .employee-info {
            display: none;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 4px solid #4b6cb7;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }

        .tab {
            padding: 15px 20px;
            cursor: pointer;
            background: #f8f9fa;
            transition: background 0.3s;
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .tab.active {
            background: #4b6cb7;
            color: white;
            font-weight: 600;
        }

        .tab:hover:not(.active) {
            background: #e9ecef;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            header {
                padding: 15px;
                margin-bottom: 20px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .content {
                gap: 20px;
                margin-bottom: 20px;
            }

            .form-section {
                padding: 15px;
                width: 100%;
            }

            .section-title {
                font-size: 1.3rem;
            }

            input,
            select,
            textarea {
                padding: 10px;
                font-size: 14px;
            }

            button {
                padding: 10px 15px;
                font-size: 14px;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 14px;
                min-width: 100%;
            }

            th,
            td {
                padding: 8px 10px;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                width: 100%;
                border-bottom: 1px solid #ddd;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem;
            }

            .form-section {
                padding: 12px;
            }

            .section-title {
                font-size: 1.2rem;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 6px 8px;
            }

            /* Make tables more responsive on very small screens */
            .table-container {
                padding: 10px;
            }

            /* Stack form elements for better mobile experience */
            .form-group {
                margin-bottom: 15px;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                color: black;
            }

            header {
                background: white;
                color: black;
                box-shadow: none;
            }

            .form-section,
            .table-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            button {
                display: none;
            }

            .tabs {
                display: none;
            }

            .tab-content {
                display: block !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Salary Adjustments & Deductions</h1>
            <p class="subtitle">Manage salary adjustments, promotions, and deductions for employees</p>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('adjustments')">Salary Adjustments</div>
            <div class="tab" onclick="switchTab('deduction-rules')">Deduction Rules</div>
            <div class="tab" onclick="switchTab('employee-deductions')">Employee Deductions</div>
        </div>

        <!-- Salary Adjustments Tab -->
        <div id="adjustments" class="tab-content active">
            <div class="content">
                <div class="form-section">
                    <h2 class="section-title">Add Salary Adjustment</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="add_adjustment" value="1">

                        <div class="form-group">
                            <label for="employee_id">Select Employee</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">-- Select an Employee --</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="adjustment_type">Adjustment Type</label>
                            <select id="adjustment_type" name="adjustment_type" required>
                                <option value="promotion">Promotion</option>
                                <option value="merit">Merit Increase</option>
                                <option value="retro_pay">Retro Pay</option>
                                <option value="correction">Correction</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount (₱)</label>
                            <input type="number" id="amount" name="amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>

                        <button type="submit">Save Adjustment</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Salary Adjustments History</h2>
                    <?php if (count($adjustments) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Effective Date</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adjustments as $adjustment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($adjustment['first_name'] . ' ' . $adjustment['last_name']); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $adjustment['adjustment_type'])); ?></td>
                                            <td>₱<?php echo number_format($adjustment['amount'], 2); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($adjustment['effective_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($adjustment['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No salary adjustments found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Deduction Rules Tab -->
        <div id="deduction-rules" class="tab-content">
            <div class="content">
                <div class="form-section">
                    <h2 class="section-title">Add Deduction Rule</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="add_deduction_rule" value="1">

                        <div class="form-group">
                            <label for="rule_name">Rule Name</label>
                            <input type="text" id="rule_name" name="rule_name" required>
                        </div>

                        <div class="form-group">
                            <label for="deduction_type">Deduction Type</label>
                            <select id="deduction_type" name="deduction_type" required>
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="rule_amount">Amount</label>
                            <input type="number" id="rule_amount" name="rule_amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="rule_description">Description</label>
                            <textarea id="rule_description" name="rule_description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="applies_to_all" value="1"> Applies to all employees
                            </label>
                        </div>

                        <button type="submit">Save Rule</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Deduction Rules</h2>
                    <?php if (count($deduction_rules) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Rule Name</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Applies to All</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deduction_rules as $rule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                                            <td><?php echo ucfirst($rule['deduction_type']); ?></td>
                                            <td>
                                                <?php
                                                if ($rule['deduction_type'] === 'percentage') {
                                                    echo number_format($rule['amount'], 2) . '%';
                                                } else {
                                                    echo '₱' . number_format($rule['amount'], 2);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $rule['applies_to_all'] ? 'Yes' : 'No'; ?></td>
                                            <td><?php echo htmlspecialchars($rule['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No deduction rules found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Employee Deductions Tab -->
        <div id="employee-deductions" class="tab-content">
            <div class="content">
                <div class="form-section">
                    <h2 class="section-title">Add Employee Deduction</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="add_employee_deduction" value="1">

                        <div class="form-group">
                            <label for="deduction_employee_id">Select Employee</label>
                            <select id="deduction_employee_id" name="deduction_employee_id" required>
                                <option value="">-- Select an Employee --</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="rule_id">Deduction Rule</label>
                            <select id="rule_id" name="rule_id" required>
                                <option value="">-- Select a Rule --</option>
                                <?php foreach ($deduction_rules as $rule): ?>
                                    <option value="<?php echo $rule['id']; ?>">
                                        <?php echo htmlspecialchars($rule['rule_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deduction_amount">Amount (₱)</label>
                            <input type="number" id="deduction_amount" name="deduction_amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="deduction_date">Deduction Date</label>
                            <input type="date" id="deduction_date" name="deduction_date" required>
                        </div>

                        <div class="form-group">
                            <label for="deduction_description">Description</label>
                            <textarea id="deduction_description" name="deduction_description" rows="3"></textarea>
                        </div>

                        <button type="submit">Save Deduction</button>
                    </form>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Employee Deductions History</h2>
                    <?php if (count($employee_deductions) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Rule</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employee_deductions as $deduction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($deduction['first_name'] . ' ' . $deduction['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($deduction['rule_name']); ?></td>
                                            <td>₱<?php echo number_format($deduction['amount'], 2); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($deduction['deduction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($deduction['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No employee deductions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).classList.add('active');

            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Find and activate the clicked tab
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.textContent.toLowerCase().includes(tabId.replace('-', ' '))) {
                    tab.classList.add('active');
                }
            });
        }

        // Set today's date as default for date fields
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('effective_date').value = today;
            document.getElementById('deduction_date').value = today;

            // Add input validation for numbers
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            });

            // Handle responsive menu for small screens
            if (window.innerWidth <= 768) {
                // Make tabs vertical on mobile
                const tabs = document.querySelector('.tabs');
                if (tabs) {
                    tabs.style.flexDirection = 'column';
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const tabs = document.querySelector('.tabs');
            if (window.innerWidth <= 768) {
                tabs.style.flexDirection = 'column';
            } else {
                tabs.style.flexDirection = 'row';
            }
        });
    </script>
</body>

</html>