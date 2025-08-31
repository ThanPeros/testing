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

// Create benefits table if it doesn't exist
$sql_benefits = "CREATE TABLE IF NOT EXISTS benefits_usage (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(30) NOT NULL,
    benefit_type ENUM('Consultation', 'Hospitalization', 'Reimbursement') NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    date_used DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_benefits) === FALSE) {
    echo "Error creating benefits table: " . $conn->error;
}

// Handle form submissions
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_benefit'])) {
        $employee_id = $_POST['employee_id'];
        $benefit_type = $_POST['benefit_type'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $date_used = $_POST['date_used'];

        $stmt = $conn->prepare("INSERT INTO benefits_usage (employee_id, benefit_type, description, amount, date_used) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $employee_id, $benefit_type, $description, $amount, $date_used);

        if ($stmt->execute()) {
            $message = "Benefit usage recorded successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['update_status'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE benefits_usage SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            $message = "Status updated successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch data
$employees = $conn->query("SELECT id, first_name, last_name, department, position FROM employees ORDER BY last_name, first_name");
$benefits = $conn->query("SELECT b.*, e.first_name, e.last_name, e.department FROM benefits_usage b LEFT JOIN employees e ON b.employee_id = e.id ORDER BY b.date_used DESC");

// Calculate totals
$total_consultation = $conn->query("SELECT SUM(amount) as total FROM benefits_usage WHERE benefit_type = 'Consultation' AND status = 'Approved'")->fetch_assoc()['total'];
$total_hospitalization = $conn->query("SELECT SUM(amount) as total FROM benefits_usage WHERE benefit_type = 'Hospitalization' AND status = 'Approved'")->fetch_assoc()['total'];
$total_reimbursement = $conn->query("SELECT SUM(amount) as total FROM benefits_usage WHERE benefit_type = 'Reimbursement' AND status = 'Approved'")->fetch_assoc()['total'];
$overall_total = $total_consultation + $total_hospitalization + $total_reimbursement;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benefits Tracking & Utilization</title>
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

        .status-pending {
            color: var(--warning);
            font-weight: 500;
        }

        .status-approved {
            color: var(--success);
            font-weight: 500;
        }

        .status-rejected {
            color: var(--danger);
            font-weight: 500;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
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

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            th,
            td {
                padding: 8px 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Benefits Tracking & Utilization</h1>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Consultation Costs</h3>
                <div class="value">₱<?php echo number_format($total_consultation, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Hospitalization Costs</h3>
                <div class="value">₱<?php echo number_format($total_hospitalization, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Reimbursement Costs</h3>
                <div class="value">₱<?php echo number_format($total_reimbursement, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Benefits Cost</h3>
                <div class="value">₱<?php echo number_format($overall_total, 2); ?></div>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="openTab('employees')">Employees</div>
            <div class="tab" onclick="openTab('benefits')">Benefits Usage</div>
            <div class="tab" onclick="openTab('record_usage')">Record Benefit Usage</div>
        </div>

        <div id="employees" class="tab-content active">
            <div class="card">
                <h2 class="card-title">Employee List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                <td><?php echo $row['department']; ?></td>
                                <td><?php echo $row['position']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="benefits" class="tab-content">
            <div class="card">
                <h2 class="card-title">Benefits Usage History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Benefit Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date Used</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $benefits->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['employee_id'] . ')'; ?></td>
                                <td><?php echo $row['department']; ?></td>
                                <td><?php echo $row['benefit_type']; ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['date_used']; ?></td>
                                <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Approved" <?php echo $row['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Rejected" <?php echo $row['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="record_usage" class="tab-content">
            <div class="card">
                <h2 class="card-title">Record Benefit Usage</h2>
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label for="employee_id">Employee ID</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees_list = $conn->query("SELECT id, first_name, last_name FROM employees ORDER BY last_name, first_name");
                                while ($employee = $employees_list->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['id'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="benefit_type">Benefit Type</label>
                            <select id="benefit_type" name="benefit_type" required>
                                <option value="Consultation">Consultation</option>
                                <option value="Hospitalization">Hospitalization</option>
                                <option value="Reimbursement">Reimbursement</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount (₱)</label>
                            <input type="number" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="date_used">Date Used</label>
                            <input type="date" id="date_used" name="date_used" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_benefit">Record Benefit Usage</button>
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
            document.getElementById('date_used').value = today;
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>