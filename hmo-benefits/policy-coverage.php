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

// Create policy management tables if they don't exist
$sql_policies = "CREATE TABLE IF NOT EXISTS hmo_policies (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(100) NOT NULL,
    provider VARCHAR(100) NOT NULL,
    description TEXT,
    coverage_details TEXT,
    annual_limit DECIMAL(12,2) NOT NULL,
    consultation_limit INT(6) NOT NULL,
    hospitalization_limit INT(6) NOT NULL,
    reimbursement_limit DECIMAL(10,2) NOT NULL,
    monthly_premium DECIMAL(8,2) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    effective_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sql_employee_coverage = "CREATE TABLE IF NOT EXISTS employee_coverage (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(6) NOT NULL,
    policy_id INT(6) NOT NULL,
    enrollment_date DATE NOT NULL,
    coverage_status ENUM('Active', 'Suspended', 'Terminated') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (policy_id) REFERENCES hmo_policies(id)
)";

if ($conn->query($sql_policies) === FALSE || $conn->query($sql_employee_coverage) === FALSE) {
    echo "Error creating tables: " . $conn->error;
}

// Handle form submissions
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_policy'])) {
        $policy_name = $_POST['policy_name'];
        $provider = $_POST['provider'];
        $description = $_POST['description'];
        $coverage_details = $_POST['coverage_details'];
        $annual_limit = $_POST['annual_limit'];
        $consultation_limit = $_POST['consultation_limit'];
        $hospitalization_limit = $_POST['hospitalization_limit'];
        $reimbursement_limit = $_POST['reimbursement_limit'];
        $monthly_premium = $_POST['monthly_premium'];
        $effective_date = $_POST['effective_date'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("INSERT INTO hmo_policies (policy_name, provider, description, coverage_details, annual_limit, consultation_limit, hospitalization_limit, reimbursement_limit, monthly_premium, effective_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdiiddss", $policy_name, $provider, $description, $coverage_details, $annual_limit, $consultation_limit, $hospitalization_limit, $reimbursement_limit, $monthly_premium, $effective_date, $status);

        if ($stmt->execute()) {
            $message = "Policy added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['assign_coverage'])) {
        $employee_id = $_POST['employee_id'];
        $policy_id = $_POST['policy_id'];
        $enrollment_date = $_POST['enrollment_date'];
        $coverage_status = $_POST['coverage_status'];
        $notes = $_POST['notes'];

        // Check if employee already has coverage
        $check_stmt = $conn->prepare("SELECT id FROM employee_coverage WHERE employee_id = ? AND coverage_status = 'Active'");
        $check_stmt->bind_param("i", $employee_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Error: Employee already has active coverage. Please terminate existing coverage first.";
        } else {
            $stmt = $conn->prepare("INSERT INTO employee_coverage (employee_id, policy_id, enrollment_date, coverage_status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $employee_id, $policy_id, $enrollment_date, $coverage_status, $notes);

            if ($stmt->execute()) {
                $message = "Coverage assigned successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }

    if (isset($_POST['update_coverage_status'])) {
        $coverage_id = $_POST['coverage_id'];
        $coverage_status = $_POST['coverage_status'];

        $stmt = $conn->prepare("UPDATE employee_coverage SET coverage_status = ? WHERE id = ?");
        $stmt->bind_param("si", $coverage_status, $coverage_id);

        if ($stmt->execute()) {
            $message = "Coverage status updated successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch data
$policies = $conn->query("SELECT * FROM hmo_policies ORDER BY policy_name");
$employee_coverage = $conn->query("SELECT ec.*, e.first_name, e.last_name, e.department, p.policy_name, p.provider 
                                  FROM employee_coverage ec 
                                  JOIN employees e ON ec.employee_id = e.id 
                                  JOIN hmo_policies p ON ec.policy_id = p.id 
                                  ORDER BY ec.enrollment_date DESC");
$employees = $conn->query("SELECT id, first_name, last_name, department FROM employees ORDER BY last_name, first_name");

// Calculate coverage statistics
$active_coverage = $conn->query("SELECT COUNT(*) as count FROM employee_coverage WHERE coverage_status = 'Active'")->fetch_assoc()['count'];
$suspended_coverage = $conn->query("SELECT COUNT(*) as count FROM employee_coverage WHERE coverage_status = 'Suspended'")->fetch_assoc()['count'];
$terminated_coverage = $conn->query("SELECT COUNT(*) as count FROM employee_coverage WHERE coverage_status = 'Terminated'")->fetch_assoc()['count'];
$total_coverage = $active_coverage + $suspended_coverage + $terminated_coverage;

// Calculate monthly premium total
$monthly_premium_total = $conn->query("SELECT SUM(p.monthly_premium) as total 
                                      FROM employee_coverage ec 
                                      JOIN hmo_policies p ON ec.policy_id = p.id 
                                      WHERE ec.coverage_status = 'Active'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy & Coverage Management</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #34495e;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            text-align: center;
            font-size: 2.2rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            font-size: 1rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--secondary);
        }

        .stat-card .value.total {
            color: var(--primary);
        }

        .stat-card .value.active {
            color: var(--success);
        }

        .stat-card .value.suspended {
            color: var(--warning);
        }

        .stat-card .value.terminated {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }

        button:hover {
            background: var(--primary);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-warning {
            background: var(--warning);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light);
            font-weight: 600;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-active {
            color: var(--success);
            font-weight: 500;
        }

        .status-suspended {
            color: var(--warning);
            font-weight: 500;
        }

        .status-terminated {
            color: var(--danger);
            font-weight: 500;
        }

        .status-inactive {
            color: var(--danger);
            font-weight: 500;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }

        .tab.active {
            background: var(--secondary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .policy-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .policy-details h4 {
            margin-bottom: 10px;
            color: var(--primary);
        }

        .policy-details ul {
            padding-left: 20px;
        }

        .policy-details li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            th,
            td {
                padding: 8px 10px;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                margin-bottom: 5px;
                border-radius: 5px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Policy & Coverage Management</h1>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Active Coverage</h3>
                <div class="value active"><?php echo $active_coverage; ?></div>
            </div>
            <div class="stat-card">
                <h3>Suspended Coverage</h3>
                <div class="value suspended"><?php echo $suspended_coverage; ?></div>
            </div>
            <div class="stat-card">
                <h3>Terminated Coverage</h3>
                <div class="value terminated"><?php echo $terminated_coverage; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Coverage</h3>
                <div class="value total"><?php echo $total_coverage; ?></div>
            </div>
            <div class="stat-card">
                <h3>Monthly Premium Total</h3>
                <div class="value">₱<?php echo number_format($monthly_premium_total, 2); ?></div>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="openTab('policies')">HMO Policies</div>
            <div class="tab" onclick="openTab('coverage')">Employee Coverage</div>
            <div class="tab" onclick="openTab('add_policy')">Add New Policy</div>
            <div class="tab" onclick="openTab('assign_coverage')">Assign Coverage</div>
        </div>

        <div id="policies" class="tab-content active">
            <div class="card">
                <h2 class="card-title">HMO Policies</h2>
                <?php if ($policies->num_rows > 0): ?>
                    <?php while ($policy = $policies->fetch_assoc()): ?>
                        <div class="policy-details">
                            <h4><?php echo $policy['policy_name']; ?> by <?php echo $policy['provider']; ?>
                                <span class="status-<?php echo strtolower($policy['status']); ?>" style="float: right;">
                                    <?php echo $policy['status']; ?>
                                </span>
                            </h4>
                            <p><strong>Description:</strong> <?php echo $policy['description']; ?></p>
                            <p><strong>Coverage Details:</strong> <?php echo $policy['coverage_details']; ?></p>
                            <div class="grid">
                                <div><strong>Annual Limit:</strong> ₱<?php echo number_format($policy['annual_limit'], 2); ?></div>
                                <div><strong>Consultation Limit:</strong> <?php echo $policy['consultation_limit']; ?> visits</div>
                                <div><strong>Hospitalization Limit:</strong> <?php echo $policy['hospitalization_limit']; ?> days</div>
                                <div><strong>Reimbursement Limit:</strong> ₱<?php echo number_format($policy['reimbursement_limit'], 2); ?></div>
                                <div><strong>Monthly Premium:</strong> ₱<?php echo number_format($policy['monthly_premium'], 2); ?></div>
                                <div><strong>Effective Date:</strong> <?php echo $policy['effective_date']; ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No policies found. Add a new policy to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="coverage" class="tab-content">
            <div class="card">
                <h2 class="card-title">Employee Coverage</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Policy</th>
                            <th>Provider</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employee_coverage->num_rows > 0): ?>
                            <?php while ($coverage = $employee_coverage->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $coverage['first_name'] . ' ' . $coverage['last_name']; ?></td>
                                    <td><?php echo $coverage['department']; ?></td>
                                    <td><?php echo $coverage['policy_name']; ?></td>
                                    <td><?php echo $coverage['provider']; ?></td>
                                    <td><?php echo $coverage['enrollment_date']; ?></td>
                                    <td class="status-<?php echo strtolower($coverage['coverage_status']); ?>"><?php echo $coverage['coverage_status']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="coverage_id" value="<?php echo $coverage['id']; ?>">
                                            <select name="coverage_status" onchange="this.form.submit()">
                                                <option value="Active" <?php echo $coverage['coverage_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="Suspended" <?php echo $coverage['coverage_status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                <option value="Terminated" <?php echo $coverage['coverage_status'] == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                                            </select>
                                            <input type="hidden" name="update_coverage_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No coverage records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="add_policy" class="tab-content">
            <div class="card">
                <h2 class="card-title">Add New Policy</h2>
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label for="policy_name">Policy Name</label>
                            <input type="text" id="policy_name" name="policy_name" required>
                        </div>
                        <div class="form-group">
                            <label for="provider">Provider</label>
                            <input type="text" id="provider" name="provider" required>
                        </div>
                        <div class="form-group">
                            <label for="annual_limit">Annual Limit (₱)</label>
                            <input type="number" id="annual_limit" name="annual_limit" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="consultation_limit">Consultation Limit (visits)</label>
                            <input type="number" id="consultation_limit" name="consultation_limit" required>
                        </div>
                        <div class="form-group">
                            <label for="hospitalization_limit">Hospitalization Limit (days)</label>
                            <input type="number" id="hospitalization_limit" name="hospitalization_limit" required>
                        </div>
                        <div class="form-group">
                            <label for="reimbursement_limit">Reimbursement Limit (₱)</label>
                            <input type="number" id="reimbursement_limit" name="reimbursement_limit" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="monthly_premium">Monthly Premium (₱)</label>
                            <input type="number" id="monthly_premium" name="monthly_premium" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="coverage_details">Coverage Details</label>
                        <textarea id="coverage_details" name="coverage_details" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_policy">Add Policy</button>
                </form>
            </div>
        </div>

        <div id="assign_coverage" class="tab-content">
            <div class="card">
                <h2 class="card-title">Assign Coverage to Employee</h2>
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['department'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="policy_id">Policy</label>
                            <select id="policy_id" name="policy_id" required>
                                <option value="">Select Policy</option>
                                <?php
                                $policies_list = $conn->query("SELECT id, policy_name, provider FROM hmo_policies WHERE status = 'Active' ORDER BY policy_name");
                                while ($policy = $policies_list->fetch_assoc()): ?>
                                    <option value="<?php echo $policy['id']; ?>">
                                        <?php echo $policy['policy_name'] . ' - ' . $policy['provider']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="enrollment_date">Enrollment Date</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" required>
                        </div>
                        <div class="form-group">
                            <label for="coverage_status">Coverage Status</label>
                            <select id="coverage_status" name="coverage_status" required>
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" name="assign_coverage">Assign Coverage</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab content
            var tabContent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContent.length; i++) {
                tabContent[i].classList.remove("active");
            }

            // Remove active class from all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }

            // Show the specific tab content and set tab as active
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }

        // Set today's date as default for date fields
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('effective_date').value = today;
            document.getElementById('enrollment_date').value = today;
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>