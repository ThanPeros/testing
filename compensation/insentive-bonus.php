<?php
include '../sidebar.php';
// Database configuration
$host = 'localhost';
$dbname = 'systems';
$username = 'root';
$password = '';

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if incentives table exists and has all required columns
try {
    $tableCheck = $pdo->query("DESCRIBE incentives");
    $columns = $tableCheck->fetchAll(PDO::FETCH_COLUMN);

    // Check for missing columns and add them if needed
    $requiredColumns = ['id', 'employee_id', 'incentive_type', 'amount', 'date_given', 'description', 'created_at'];

    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            // Add missing columns based on the table structure from your image
            switch ($column) {
                case 'date_given':
                    $pdo->exec("ALTER TABLE incentives ADD COLUMN date_given DATE NOT NULL AFTER amount");
                    break;
                case 'description':
                    $pdo->exec("ALTER TABLE incentives ADD COLUMN description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER date_given");
                    break;
                case 'created_at':
                    $pdo->exec("ALTER TABLE incentives ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER description");
                    break;
                    // Add other columns as needed
            }
        }
    }
} catch (PDOException $e) {
    // Table might not exist, so create it with all required columns
    $createTableQuery = "
    CREATE TABLE incentives (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(11) NOT NULL,
        incentive_type VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        date_given DATE NOT NULL,
        description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTableQuery);
}

// Create employees table if it doesn't exist with first_name and last_name
$createEmployeesTableQuery = "
CREATE TABLE IF NOT EXISTS employees (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100)
)";

try {
    $pdo->exec($createEmployeesTableQuery);

    // Check if we need to migrate from name to first_name/last_name
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'name'");
    $nameColumnExists = $stmt->fetch();

    if ($nameColumnExists) {
        // Migrate data from name to first_name and last_name
        $pdo->exec("UPDATE employees SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 2) WHERE name IS NOT NULL AND name != ''");

        // Drop the name column
        $pdo->exec("ALTER TABLE employees DROP COLUMN name");
    }

    // Insert sample employees if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $insertSampleEmployees = "
        INSERT INTO employees (first_name, last_name, position, department) VALUES 
        ('Juan', 'Dela Cruz', 'Sales Associate', 'Sales'),
        ('Maria', 'Santos', 'Manager', 'Operations'),
        ('Pedro', 'Reyes', 'Developer', 'IT'),
        ('Ana', 'Lopez', 'HR Specialist', 'Human Resources'),
        ('Michael', 'Johnson', 'Accountant', 'Finance')
        ";
        $pdo->exec($insertSampleEmployees);
    }
} catch (PDOException $e) {
    die("Error creating employees table: " . $e->getMessage());
}

// Initialize variables
$success = "";
$incentives = [];
$employees = [];
$reportData = [];
$summaryStats = [];

// Handle form submissions FIRST before any other processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_incentive'])) {
        $employee_id = $_POST['employee_id'];
        $incentive_type = $_POST['incentive_type'];
        $amount = $_POST['amount'];
        $date_given = $_POST['date_given'] ?? date('Y-m-d'); // Default to today if not provided
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO incentives (employee_id, incentive_type, amount, date_given, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$employee_id, $incentive_type, $amount, $date_given, $description])) {
            $success = "Incentive added successfully!";
        } else {
            $success = "Error adding incentive: " . implode(" ", $stmt->errorInfo());
        }
    } elseif (isset($_POST['update_incentive'])) {
        $id = $_POST['id'];
        $employee_id = $_POST['employee_id'];
        $incentive_type = $_POST['incentive_type'];
        $amount = $_POST['amount'];
        $date_given = $_POST['date_given'] ?? date('Y-m-d'); // Default to today if not provided
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("UPDATE incentives SET employee_id=?, incentive_type=?, amount=?, date_given=?, description=? WHERE id=?");
        if ($stmt->execute([$employee_id, $incentive_type, $amount, $date_given, $description, $id])) {
            $success = "Incentive updated successfully!";
        } else {
            $success = "Error updating incentive: " . implode(" ", $stmt->errorInfo());
        }
    } elseif (isset($_POST['delete_incentive'])) {
        $id = $_POST['id'];

        $stmt = $pdo->prepare("DELETE FROM incentives WHERE id=?");
        if ($stmt->execute([$id])) {
            $success = "Incentive deleted successfully!";
        } else {
            $success = "Error deleting incentive: " . implode(" ", $stmt->errorInfo());
        }
    }
}

// Fetch all incentives with employee names
try {
    $incentives = $pdo->query("
        SELECT i.*, e.first_name, e.last_name 
        FROM incentives i 
        LEFT JOIN employees e ON i.employee_id = e.id
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $success = "Error fetching incentives: " . $e->getMessage();
}

// Fetch employees for dropdown
try {
    $employees = $pdo->query("SELECT id, first_name, last_name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $success = "Error fetching employees: " . $e->getMessage();
}

// Generate reports based on request
$reportType = $_GET['report_type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$employeeId = $_GET['employee_id'] ?? '';

if (!empty($reportType)) {
    try {
        $query = "
            SELECT i.*, e.first_name, e.last_name, e.department 
            FROM incentives i 
            LEFT JOIN employees e ON i.employee_id = e.id 
            WHERE 1=1
        ";

        $params = [];

        // Add date filter if provided
        if (!empty($startDate) && !empty($endDate)) {
            $query .= " AND i.date_given BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Add employee filter if provided
        if (!empty($employeeId)) {
            $query .= " AND i.employee_id = ?";
            $params[] = $employeeId;
        }

        // Order by appropriate field based on report type
        switch ($reportType) {
            case 'summary':
                $query .= " ORDER BY i.incentive_type, i.date_given";
                break;
            case 'employee':
                $query .= " ORDER BY e.last_name, e.first_name, i.date_given";
                break;
            case 'department':
                $query .= " ORDER BY e.department, e.last_name, e.first_name, i.date_given";
                break;
            case 'date':
            default:
                $query .= " ORDER BY i.date_given DESC, e.last_name, e.first_name";
                break;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $success = "Error generating report: " . $e->getMessage();
    }
}

// Calculate summary statistics
if (!empty($reportData)) {
    $totalAmount = 0;
    $typeTotals = [];
    $deptTotals = [];
    $employeeTotals = [];

    foreach ($reportData as $row) {
        $totalAmount += $row['amount'];
        $employeeName = $row['first_name'] . ' ' . $row['last_name'];

        // Sum by type
        if (!isset($typeTotals[$row['incentive_type']])) {
            $typeTotals[$row['incentive_type']] = 0;
        }
        $typeTotals[$row['incentive_type']] += $row['amount'];

        // Sum by department
        $dept = $row['department'] ?? 'Unknown';
        if (!isset($deptTotals[$dept])) {
            $deptTotals[$dept] = 0;
        }
        $deptTotals[$dept] += $row['amount'];

        // Sum by employee
        if (!isset($employeeTotals[$employeeName])) {
            $employeeTotals[$employeeName] = 0;
        }
        $employeeTotals[$employeeName] += $row['amount'];
    }

    $summaryStats = [
        'total_amount' => $totalAmount,
        'type_totals' => $typeTotals,
        'dept_totals' => $deptTotals,
        'employee_totals' => $employeeTotals,
        'record_count' => count($reportData)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incentives & Bonus Management</title>
    <style>
        /* Your CSS remains the same */
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

        .h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50
        }

        .header {
            background: #f5f7fa;
            padding: 20px 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background: #2575fc;
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
            background: #1a67e8;
        }

        .btn-delete {
            background: #ff4757;
        }

        .btn-delete:hover {
            background: #ff2e43;
        }

        .btn-edit {
            background: #2ed573;
        }

        .btn-edit:hover {
            background: #25b55f;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            background-color: #f1f7ff;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            background: #f1f1f1;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }

        .tab.active {
            background: #2575fc;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .report-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2575fc;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .chart-container {
            height: 300px;
            margin: 20px 0;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .export-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .card {
                padding: 15px;
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
                border-radius: 4px;
            }

            .actions {
                flex-direction: column;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Incentives & Bonus Management</h1>
            <p style="text-align: center;">Manage performance bonuses, 13th month pay, attendance incentives, and commissions</p>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert <?php echo strpos($success, 'Error') === false ? 'alert-success' : 'alert-error'; ?>"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab(event, 'add')">Add Incentive</div>
            <div class="tab" onclick="switchTab(event, 'view')">View Incentives</div>
            <div class="tab" onclick="switchTab(event, 'report')">Reports</div>
        </div>

        <div id="addTab" class="tab-content active">
            <div class="card">
                <h2>Add New Incentive</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select id="employee_id" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="incentive_type">Incentive Type</label>
                        <select id="incentive_type" name="incentive_type" required>
                            <option value="">Select Type</option>
                            <option value="Performance Bonus">Performance Bonus</option>
                            <option value="13th Month Pay">13th Month Pay</option>
                            <option value="Attendance Incentive">Attendance Incentive</option>
                            <option value="Commission">Commission</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (₱)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="date_given">Date Given</label>
                        <input type="date" id="date_given" name="date_given" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Optional description of the incentive"></textarea>
                    </div>

                    <button type="submit" name="add_incentive">Add Incentive</button>
                </form>
            </div>
        </div>

        <div id="viewTab" class="tab-content">
            <div class="card">
                <h2>Incentives List</h2>
                <?php if (count($incentives) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Date Given</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incentives as $incentive): ?>
                                <tr>
                                    <td><?php echo $incentive['first_name'] . ' ' . $incentive['last_name'] . ' (ID: ' . $incentive['employee_id'] . ')'; ?></td>
                                    <td><?php echo $incentive['incentive_type']; ?></td>
                                    <td>₱<?php echo number_format($incentive['amount'], 2); ?></td>
                                    <td><?php echo $incentive['date_given']; ?></td>
                                    <td><?php echo $incentive['description']; ?></td>
                                    <td><?php echo $incentive['created_at']; ?></td>
                                    <td class="actions">
                                        <button class="btn-edit" onclick="editIncentive(<?php echo $incentive['id']; ?>)">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $incentive['id']; ?>">
                                            <button type="submit" name="delete_incentive" class="btn-delete" onclick="return confirm('Are you sure you want to delete this incentive?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No incentives found. Add some using the form above.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="reportTab" class="tab-content">
            <div class="card">
                <h2>Incentives Reports</h2>

                <div class="report-filters">
                    <form method="GET" id="reportForm">
                        <input type="hidden" name="report_type" id="report_type" value="<?php echo $reportType; ?>">

                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="report_period">Report Type</label>
                                <select id="report_period" name="report_type" onchange="updateReportType(this.value)">
                                    <option value="">Select Report Type</option>
                                    <option value="summary" <?php echo $reportType == 'summary' ? 'selected' : ''; ?>>Summary by Type</option>
                                    <option value="employee" <?php echo $reportType == 'employee' ? 'selected' : ''; ?>>By Employee</option>
                                    <option value="department" <?php echo $reportType == 'department' ? 'selected' : ''; ?>>By Department</option>
                                    <option value="date" <?php echo $reportType == 'date' ? 'selected' : ''; ?>>By Date Range</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>

                            <div class="filter-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="employee_id_filter">Employee</label>
                                <select id="employee_id_filter" name="employee_id">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>" <?php echo $employeeId == $employee['id'] ? 'selected' : ''; ?>>
                                            <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group" style="display: flex; align-items: flex-end;">
                                <button type="submit">Generate Report</button>
                                <button type="button" onclick="clearFilters()" style="background: #6c757d;">Clear Filters</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (!empty($reportData)): ?>
                    <div class="export-buttons">
                        <button onclick="exportToCSV()">Export to CSV</button>
                        <button onclick="window.print()">Print Report</button>
                    </div>

                    <h3>Report Summary</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Total Incentives</div>
                            <div class="stat-value"><?php echo $summaryStats['record_count']; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total Amount</div>
                            <div class="stat-value">₱<?php echo number_format($summaryStats['total_amount'], 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Average per Incentive</div>
                            <div class="stat-value">₱<?php echo number_format($summaryStats['total_amount'] / max(1, $summaryStats['record_count']), 2); ?></div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h4>Distribution by Type</h4>
                        <p>Pie chart visualization would appear here</p>
                        <div style="width: 100%; max-width: 400px; height: 200px; background: linear-gradient(135deg, #6a11cb33 0%, #2575fc33 100%); 
                                    display: flex; justify-content: center; align-items: center; border-radius: 5px;">
                            <p>Visualization of incentives by type</p>
                        </div>
                    </div>

                    <h3>Detailed Report</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><?php echo $row['date_given']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['department'] ?? 'N/A'; ?></td>
                                    <td><?php echo $row['incentive_type']; ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3>Summary by Type</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Incentive Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaryStats['type_totals'] as $type => $amount):
                                $count = 0;
                                foreach ($reportData as $row) {
                                    if ($row['incentive_type'] == $type) $count++;
                                }
                                $percentage = ($amount / $summaryStats['total_amount']) * 100;
                            ?>
                                <tr>
                                    <td><?php echo $type; ?></td>
                                    <td><?php echo $count; ?></td>
                                    <td>₱<?php echo number_format($amount, 2); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif (!empty($reportType)): ?>
                    <p>No data found for the selected filters.</p>
                <?php else: ?>
                    <p>Select report type and filters to generate a report.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal (hidden by default) -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:90%; max-width:600px; margin:50px auto; padding:20px; border-radius:8px;">
            <h2>Edit Incentive</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_employee_id">Employee</label>
                    <select id="edit_employee_id" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_incentive_type">Incentive Type</label>
                    <select id="edit_incentive_type" name="incentive_type" required>
                        <option value="Performance Bonus">Performance Bonus</option>
                        <option value="13th Month Pay">13th Month Pay</option>
                        <option value="Attendance Incentive">Attendance Incentive</option>
                        <option value="Commission">Commission</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_amount">Amount (₱)</label>
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_date_given">Date Given</label>
                    <input type="date" id="edit_date_given" name="date_given" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" name="update_incentive">Update Incentive</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(event, tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');

            // If switching to report tab and we have filters, generate report
            if (tabName === 'report' && document.getElementById('report_type').value) {
                generateReport();
            }
        }

        function editIncentive(id) {
            // In a real application, you would fetch the data via AJAX
            // For this example, we'll simulate with the existing data
            const incentives = <?php echo json_encode($incentives); ?>;
            const incentive = incentives.find(inc => inc.id == id);

            if (incentive) {
                document.getElementById('edit_id').value = incentive.id;
                document.getElementById('edit_employee_id').value = incentive.employee_id;
                document.getElementById('edit_incentive_type').value = incentive.incentive_type;
                document.getElementById('edit_amount').value = incentive.amount;
                document.getElementById('edit_date_given').value = incentive.date_given;
                document.getElementById('edit_description').value = incentive.description || '';

                document.getElementById('editModal').style.display = 'block';
            }
        }

        function updateReportType(value) {
            document.getElementById('report_type').value = value;
        }

        function clearFilters() {
            document.getElementById('report_period').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('employee_id_filter').value = '';
            document.getElementById('report_type').value = '';
            document.getElementById('reportForm').submit();
        }

        function exportToCSV() {
            // This would generate a CSV file in a real implementation
            alert('CSV export functionality would be implemented here. This would download a CSV file with the current report data.');
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Set today's date as default for the date field
        document.getElementById('date_given').valueAsDate = new Date();

        // Set default date range for reports (current month)
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

        if (!document.getElementById('start_date').value) {
            document.getElementById('start_date').valueAsDate = firstDayOfMonth;
        }
        if (!document.getElementById('end_date').value) {
            document.getElementById('end_date').valueAsDate = today;
        }

        // If we're on the report tab and have a report type, show the report tab
        <?php if (!empty($reportType)): ?>
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

            document.querySelector('.tab:nth-child(3)').classList.add('active');
            document.getElementById('reportTab').classList.add('active');
        <?php endif; ?>
    </script>
</body>

</html>