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

// Create table if it doesn't exist - UPDATED to match your structure
$sql = "CREATE TABLE IF NOT EXISTS allowances (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(50) NOT NULL,
    transport DECIMAL(10,2) DEFAULT 0,
    meal DECIMAL(10,2) DEFAULT 0,
    housing DECIMAL(10,2) DEFAULT 0,
    communication DECIMAL(10,2) DEFAULT 0,
    other_benefits DECIMAL(10,2) DEFAULT 0,
    benefits_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Create employees table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS employees (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating employees table: " . $conn->error);
}

// Check if employees exist but don't insert sample data
$result = $conn->query("SELECT COUNT(*) as count FROM employees");
$row = $result->fetch_assoc();

// Fetch all employees for the dropdown
$employees_result = $conn->query("SELECT * FROM employees ORDER BY first_name, last_name");
$employees = [];
if ($employees_result->num_rows > 0) {
    while ($row = $employees_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $transport = $_POST['transport'];
    $meal = $_POST['meal'];
    $housing = $_POST['housing'];
    $communication = $_POST['communication'];
    $other_benefits = $_POST['other_benefits'];
    $benefits_description = $_POST['benefits_description'];

    // Get employee details
    $stmt = $conn->prepare("SELECT first_name, last_name, position, department FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name, $position, $department);
    $stmt->fetch();
    $stmt->close();

    $employee_name = $first_name . ' ' . $last_name;

    // Insert into allowances table - UPDATED to match your table structure
    $stmt = $conn->prepare("INSERT INTO allowances (employee_name, transport, meal, housing, communication, other_benefits, benefits_description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddddds", $employee_name, $transport, $meal, $housing, $communication, $other_benefits, $benefits_description);

    if ($stmt->execute()) {
        $message = "Allowances added successfully for $employee_name!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all records
$result = $conn->query("SELECT * FROM allowances ORDER BY created_at DESC");
$records = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Allowances & Benefits</title>
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

        .allowances-container {
            padding: 15px 20px;
            margin: 0 auto;
            max-width: 1400px;
            width: 100%;
        }

        .page-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #182848;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
            width: 100%;
            text-align: center;
        }

        .tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            width: 100%;
            justify-content: center;
        }

        .tab {
            padding: 8px 20px;
            cursor: pointer;
            background: #e9ecef;
            border: 1px solid #ccc;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            font-weight: 500;
            white-space: nowrap;
        }

        .tab.active {
            background: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }

        .tab-content {
            display: none;
            width: 100%;
        }

        .tab-content.active {
            display: block;
        }

        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
        }

        .form-section,
        .records-section {
            flex: 1;
            min-width: 300px;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .wide-section {
            flex: 100%;
            max-width: 100%;
        }

        .section-title {
            font-size: 1.4rem;
            margin-bottom: 15px;
            padding-bottom: 6px;
            border-bottom: 1px solid #4b6cb7;
            color: #182848;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #182848;
            font-size: 0.9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
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
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
            margin: 0 auto;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message {
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
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
            margin-top: 15px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            color: #182848;
            font-weight: 600;
            position: sticky;
            top: 0;
            font-size: 0.9rem;
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
            padding: 12px;
            border-radius: 4px;
            margin-top: 8px;
            border-left: 3px solid #4b6cb7;
            font-size: 0.85rem;
        }

        .compact-table td,
        .compact-table th {
            padding: 8px 10px;
        }

        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .summary-card {
            flex: 1;
            min-width: 150px;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card h3 {
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: #182848;
        }

        .summary-card p {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4b6cb7;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-column {
            flex: 1;
            min-width: 200px;
        }

        @media (max-width: 1200px) {
            .allowances-container {
                max-width: 95%;
            }
        }

        @media (max-width: 1024px) {
            .allowances-container {
                max-width: 100%;
                padding: 15px;
            }

            .content {
                flex-direction: column;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 768px) {

            .form-section,
            .records-section {
                min-width: 100%;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 8px 10px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .summary-card {
                min-width: 120px;
            }

            .summary-card h3 {
                font-size: 0.9rem;
            }

            .summary-card p {
                font-size: 1.1rem;
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                justify-content: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="allowances-container">
        <h1 class="page-title">Employee Allowances & Benefits</h1>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('add-tab')">Add Allowances</div>
            <div class="tab" onclick="switchTab('view-tab')">View Records</div>
            <div class="tab" onclick="switchTab('summary-tab')">Summary</div>
        </div>

        <div id="add-tab" class="tab-content active">
            <div class="form-section wide-section">
                <h2 class="section-title">Add Allowances & Benefits</h2>

                <?php if ($message): ?>
                    <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="employee_id">Select Employee</label>
                        <select id="employee_id" name="employee_id" required onchange="showEmployeeInfo()">
                            <option value="">-- Select an Employee --</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>"
                                    data-firstname="<?php echo htmlspecialchars($employee['first_name']); ?>"
                                    data-lastname="<?php echo htmlspecialchars($employee['last_name']); ?>"
                                    data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                    data-department="<?php echo htmlspecialchars($employee['department']); ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="employee-info" class="employee-info">
                            <p><strong>Name:</strong> <span id="selected-name"></span></p>
                            <p><strong>Position:</strong> <span id="selected-position"></span></p>
                            <p><strong>Department:</strong> <span id="selected-department"></span></p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="transport">Transport Allowance (₱)</label>
                                <input type="number" id="transport" name="transport" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="form-group">
                                <label for="meal">Meal Allowance (₱)</label>
                                <input type="number" id="meal" name="meal" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="form-group">
                                <label for="housing">Housing Allowance (₱)</label>
                                <input type="number" id="housing" name="housing" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="communication">Communication Allowance (₱)</label>
                                <input type="number" id="communication" name="communication" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="form-column">
                            <div class="form-group">
                                <label for="other_benefits">Other Benefits (₱)</label>
                                <input type="number" id="other_benefits" name="other_benefits" step="0.01" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="form-column">
                            <!-- Empty column for spacing -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="benefits_description">Benefits Description</label>
                        <textarea id="benefits_description" name="benefits_description" rows="2"></textarea>
                    </div>

                    <button type="submit">Save Allowances</button>
                </form>
            </div>
        </div>

        <div id="view-tab" class="tab-content">
            <div class="records-section wide-section">
                <h2 class="section-title">Allowances Records</h2>

                <?php if (count($records) > 0): ?>
                    <div class="table-container">
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Transport</th>
                                    <th>Meal</th>
                                    <th>Housing</th>
                                    <th>Comm</th>
                                    <th>Other</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grand_total = 0;
                                foreach ($records as $record):
                                    $total = $record['transport'] + $record['meal'] + $record['housing'] +
                                        $record['communication'] + $record['other_benefits'];
                                    $grand_total += $total;
                                    $date = date('m/d/Y', strtotime($record['created_at']));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                        <td>₱<?php echo number_format($record['transport'], 2); ?></td>
                                        <td>₱<?php echo number_format($record['meal'], 2); ?></td>
                                        <td>₱<?php echo number_format($record['housing'], 2); ?></td>
                                        <td>₱<?php echo number_format($record['communication'], 2); ?></td>
                                        <td>₱<?php echo number_format($record['other_benefits'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                                        <td><?php echo $date; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="6"><strong>Grand Total</strong></td>
                                    <td colspan="2"><strong>₱<?php echo number_format($grand_total, 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No allowances records found. Please add some records.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="summary-tab" class="tab-content">
            <div class="records-section wide-section">
                <h2 class="section-title">Allowances Summary</h2>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Employees</h3>
                        <p><?php echo count($employees); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Records</h3>
                        <p><?php echo count($records); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Amount</h3>
                        <p>₱
                            <?php
                            $grand_total = 0;
                            foreach ($records as $record) {
                                $total = $record['transport'] + $record['meal'] + $record['housing'] +
                                    $record['communication'] + $record['other_benefits'];
                                $grand_total += $total;
                            }
                            echo number_format($grand_total, 2);
                            ?>
                        </p>
                    </div>
                </div>

                <?php if (count($records) > 0): ?>
                    <div class="table-container">
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Total Allowances</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Group records by employee
                                $employee_totals = [];
                                $employee_last_date = [];

                                foreach ($records as $record) {
                                    $employee_name = $record['employee_name'];
                                    $total = $record['transport'] + $record['meal'] + $record['housing'] +
                                        $record['communication'] + $record['other_benefits'];

                                    if (!isset($employee_totals[$employee_name])) {
                                        $employee_totals[$employee_name] = 0;
                                    }
                                    $employee_totals[$employee_name] += $total;

                                    // Keep track of the latest date
                                    if (
                                        !isset($employee_last_date[$employee_name]) ||
                                        strtotime($record['created_at']) > strtotime($employee_last_date[$employee_name])
                                    ) {
                                        $employee_last_date[$employee_name] = $record['created_at'];
                                    }
                                }

                                // Sort employees by total allowances (descending)
                                arsort($employee_totals);

                                foreach ($employee_totals as $employee => $total):
                                    $date = date('m/d/Y', strtotime($employee_last_date[$employee]));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee); ?></td>
                                        <td><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                                        <td><?php echo $date; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No allowances records found. Please add some records.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript to enhance user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Clear form if success message is shown
            const successMessage = document.querySelector('.message.success');
            if (successMessage) {
                document.querySelector('form').reset();
                document.getElementById('employee-info').style.display = 'none';
            }

            // Add input validation for numbers
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            });
        });

        function showEmployeeInfo() {
            const select = document.getElementById('employee_id');
            const infoDiv = document.getElementById('employee-info');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.value) {
                document.getElementById('selected-name').textContent =
                    selectedOption.getAttribute('data-firstname') + ' ' +
                    selectedOption.getAttribute('data-lastname');
                document.getElementById('selected-position').textContent =
                    selectedOption.getAttribute('data-position') || 'Not specified';
                document.getElementById('selected-department').textContent =
                    selectedOption.getAttribute('data-department') || 'Not specified';
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }

        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId).classList.add('active');

            // Update tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Activate the clicked tab (find the tab button that corresponds to this tab)
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.getAttribute('onclick').includes(tabId)) {
                    tab.classList.add('active');
                }
            });
        }
    </script>
</body>

</html>