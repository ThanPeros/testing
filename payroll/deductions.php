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

// Create deductions table if it doesn't exist with proper foreign key constraint
$createDeductionsTable = "CREATE TABLE IF NOT EXISTS deductions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(11) NOT NULL,
    sss DECIMAL(10,2) DEFAULT 0,
    philhealth DECIMAL(10,2) DEFAULT 0,
    pagibig DECIMAL(10,2) DEFAULT 0,
    withholding_tax DECIMAL(10,2) DEFAULT 0,
    company_loans DECIMAL(10,2) DEFAULT 0,
    salary_advances DECIMAL(10,2) DEFAULT 0,
    absences INT(3) DEFAULT 0,
    lateness INT(3) DEFAULT 0,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";

if ($conn->query($createDeductionsTable) === FALSE) {
    die("Error creating deductions table: " . $conn->error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_deductions'])) {
        // Update deductions
        $employee_id = $_POST['employee_id'];

        // First, verify the employee exists
        $verify_stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
        $verify_stmt->bind_param("i", $employee_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows === 0) {
            $error = "Error: Employee with ID $employee_id does not exist!";
        } else {
            $sss = $_POST['sss'];
            $philhealth = $_POST['philhealth'];
            $pagibig = $_POST['pagibig'];
            $withholding_tax = $_POST['withholding_tax'];
            $company_loans = $_POST['company_loans'];
            $salary_advances = $_POST['salary_advances'];
            $absences = $_POST['absences'];
            $lateness = $_POST['lateness'];

            // Check if record exists
            $check_stmt = $conn->prepare("SELECT id FROM deductions WHERE employee_id = ?");
            $check_stmt->bind_param("i", $employee_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE deductions SET sss=?, philhealth=?, pagibig=?, withholding_tax=?, company_loans=?, salary_advances=?, absences=?, lateness=? WHERE employee_id=?");
                $stmt->bind_param("ddddddiii", $sss, $philhealth, $pagibig, $withholding_tax, $company_loans, $salary_advances, $absences, $lateness, $employee_id);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO deductions (employee_id, sss, philhealth, pagibig, withholding_tax, company_loans, salary_advances, absences, lateness) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iddddddii", $employee_id, $sss, $philhealth, $pagibig, $withholding_tax, $company_loans, $salary_advances, $absences, $lateness);
            }

            if ($stmt->execute()) {
                $message = "Deductions updated successfully!";
            } else {
                $error = "Error updating deductions: " . $conn->error;
            }
            $stmt->close();
            $check_stmt->close();
        }
        $verify_stmt->close();
    }
}

// Fetch all employees with their deductions
$employees_query = "
    SELECT e.*, d.*, sa.base_salary, ss.grade as salary_grade
    FROM employees e 
    LEFT JOIN deductions d ON e.id = d.employee_id 
    LEFT JOIN salary_assignments sa ON e.id = sa.employee_id
    LEFT JOIN salary_structures ss ON sa.salary_grade = ss.grade
    ORDER BY e.last_name, e.first_name
";
$employees_result = $conn->query($employees_query);

// Calculate basic salary based on salary structure or default
function getBasicSalary($employee)
{
    // If base_salary is available from salary_assignments, use it
    if (!empty($employee['base_salary'])) {
        return $employee['base_salary'];
    }

    // Otherwise, use the default salary mapping
    $salaries = [
        'Manager' => 50000,
        'Supervisor' => 35000,
        'Developer' => 40000,
        'Designer' => 30000,
        'Accountant' => 35000,
        'HR' => 32000,
        'Staff' => 25000
    ];

    return isset($salaries[$employee['position']]) ? $salaries[$employee['position']] : 25000;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Deductions & Statutory Compliance</title>
    <style>
        /* Your existing CSS remains the same */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #34495e;
            --success: #2ecc71;
            --warning: #f39c12;
        }

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
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            margin-bottom: 1rem;
        }

        .card {
            background: white;
            border-radius: 5px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
        }

        button:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: var(--accent);
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #27ae60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        th,
        td {
            padding: 0.75rem;
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

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
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

        .tabs {
            display: flex;
            margin-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }

        .tab.active {
            background: white;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .summary-card {
            display: flex;
            justify-content: space-between;
            background: var(--light);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .summary-item {
            text-align: center;
            margin: 0.5rem;
            min-width: 150px;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }

            .tab {
                margin-bottom: 5px;
                border-radius: 5px;
                border: 1px solid #ddd;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .summary-card {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Employee Deductions & Statutory Compliance</h1>
            <p>Manage employee deductions and statutory compliance (Philippines)</p>
        </header>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('employees')">Employees</div>
            <div class="tab" onclick="switchTab('deductions')">Deductions</div>
            <div class="tab" onclick="switchTab('reports')">Reports</div>
        </div>

        <div id="employees" class="tab-content active">
            <div class="card">
                <h2>Employee List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Salary Grade</th>
                            <th>Basic Salary</th>
                            <th>SSS</th>
                            <th>PhilHealth</th>
                            <th>Pag-IBIG</th>
                            <th>Total Deductions</th>
                            <th>Net Pay</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees_result->fetch_assoc()):
                            $basic_salary = getBasicSalary($employee);
                            $total_deductions = ($employee['sss'] ?? 0) + ($employee['philhealth'] ?? 0) + ($employee['pagibig'] ?? 0) +
                                ($employee['withholding_tax'] ?? 0) + ($employee['company_loans'] ?? 0) +
                                ($employee['salary_advances'] ?? 0) +
                                (($employee['absences'] ?? 0) * ($basic_salary / 22)) +
                                (($employee['lateness'] ?? 0) * ($basic_salary / 22 / 8));
                            $net_pay = $basic_salary - $total_deductions;
                        ?>
                            <tr>
                                <td><?php echo $employee['id']; ?></td>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                <td><?php echo $employee['position'] ?? 'N/A'; ?></td>
                                <td><?php echo $employee['department'] ?? 'N/A'; ?></td>
                                <td><?php echo $employee['salary_grade'] ?? 'N/A'; ?></td>
                                <td>₱<?php echo number_format($basic_salary, 2); ?></td>
                                <td>₱<?php echo number_format($employee['sss'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['philhealth'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['pagibig'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($total_deductions, 2); ?></td>
                                <td>₱<?php echo number_format($net_pay, 2); ?></td>
                                <td>
                                    <button onclick="editDeductions(<?php echo $employee['id']; ?>)">Edit Deductions</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="deductions" class="tab-content">
            <div class="card">
                <h2>Manage Deductions</h2>
                <form method="POST" action="">
                    <input type="hidden" name="update_deductions" value="1">
                    <div class="form-group">
                        <label for="deduction_employee">Select Employee</label>
                        <select id="deduction_employee" name="employee_id" onchange="loadEmployeeDeductions()" required>
                            <option value="">Select an employee</option>
                            <?php
                            $employees_result->data_seek(0); // Reset result pointer
                            while ($employee = $employees_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sss">SSS Deduction</label>
                        <input type="number" step="0.01" id="sss" name="sss" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="philhealth">PhilHealth Deduction</label>
                        <input type="number" step="0.01" id="philhealth" name="philhealth" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="pagibig">Pag-IBIG Deduction</label>
                        <input type="number" step="0.01" id="pagibig" name="pagibig" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="withholding_tax">Withholding Tax</label>
                        <input type="number" step="0.01" id="withholding_tax" name="withholding_tax" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="company_loans">Company Loans</label>
                        <input type="number" step="0.01" id="company_loans" name="company_loans" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="salary_advances">Salary Advances</label>
                        <input type="number" step="0.01" id="salary_advances" name="salary_advances" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="absences">Absences (days)</label>
                        <input type="number" id="absences" name="absences" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="lateness">Lateness (hours)</label>
                        <input type="number" id="lateness" name="lateness" value="0" required>
                    </div>

                    <button type="submit" class="btn-success">Update Deductions</button>
                </form>
            </div>
        </div>

        <div id="reports" class="tab-content">
            <div class="card">
                <h2>Deductions Report</h2>

                <div class="summary-card">
                    <div class="summary-item">
                        <div class="summary-label">Total Employees</div>
                        <div class="summary-value">
                            <?php
                            $count_result = $conn->query("SELECT COUNT(*) as total FROM employees");
                            echo $count_result->fetch_assoc()['total'];
                            ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Total SSS</div>
                        <div class="summary-value">
                            ₱<?php
                                $sss_result = $conn->query("SELECT SUM(sss) as total FROM deductions");
                                echo number_format($sss_result->fetch_assoc()['total'] ?? 0, 2);
                                ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Total PhilHealth</div>
                        <div class="summary-value">
                            ₱<?php
                                $philhealth_result = $conn->query("SELECT SUM(philhealth) as total FROM deductions");
                                echo number_format($philhealth_result->fetch_assoc()['total'] ?? 0, 2);
                                ?>
                        </div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Total Pag-IBIG</div>
                        <div class="summary-value">
                            ₱<?php
                                $pagibig_result = $conn->query("SELECT SUM(pagibig) as total FROM deductions");
                                echo number_format($pagibig_result->fetch_assoc()['total'] ?? 0, 2);
                                ?>
                        </div>
                    </div>
                </div>

                <h3>Detailed Report</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Salary Grade</th>
                            <th>SSS</th>
                            <th>PhilHealth</th>
                            <th>Pag-IBIG</th>
                            <th>Withholding Tax</th>
                            <th>Company Loans</th>
                            <th>Salary Advances</th>
                            <th>Absences</th>
                            <th>Lateness</th>
                            <th>Total Deductions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $employees_result->data_seek(0); // Reset result pointer
                        while ($employee = $employees_result->fetch_assoc()):
                            $basic_salary = getBasicSalary($employee);
                            $total_deductions = ($employee['sss'] ?? 0) + ($employee['philhealth'] ?? 0) + ($employee['pagibig'] ?? 0) +
                                ($employee['withholding_tax'] ?? 0) + ($employee['company_loans'] ?? 0) +
                                ($employee['salary_advances'] ?? 0) +
                                (($employee['absences'] ?? 0) * ($basic_salary / 22)) +
                                (($employee['lateness'] ?? 0) * ($basic_salary / 22 / 8));
                        ?>
                            <tr>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                <td><?php echo $employee['position'] ?? 'N/A'; ?></td>
                                <td><?php echo $employee['salary_grade'] ?? 'N/A'; ?></td>
                                <td>₱<?php echo number_format($employee['sss'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['philhealth'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['pagibig'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['withholding_tax'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['company_loans'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($employee['salary_advances'] ?? 0, 2); ?></td>
                                <td><?php echo $employee['absences'] ?? 0; ?> days</td>
                                <td><?php echo $employee['lateness'] ?? 0; ?> hours</td>
                                <td>₱<?php echo number_format($total_deductions, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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

        function editDeductions(employeeId) {
            // Switch to deductions tab
            switchTab('deductions');

            // Select the employee
            document.getElementById('deduction_employee').value = employeeId;

            // Load employee deductions via AJAX
            loadEmployeeDeductions();
        }

        function loadEmployeeDeductions() {
            const employeeId = document.getElementById('deduction_employee').value;

            if (!employeeId) return;

            // Create AJAX request to get deduction data
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `?ajax=get_deductions&employee_id=${employeeId}`, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const deductions = JSON.parse(this.responseText);

                        // Populate form fields
                        document.getElementById('sss').value = deductions.sss || 0;
                        document.getElementById('philhealth').value = deductions.philhealth || 0;
                        document.getElementById('pagibig').value = deductions.pagibig || 0;
                        document.getElementById('withholding_tax').value = deductions.withholding_tax || 0;
                        document.getElementById('company_loans').value = deductions.company_loans || 0;
                        document.getElementById('salary_advances').value = deductions.salary_advances || 0;
                        document.getElementById('absences').value = deductions.absences || 0;
                        document.getElementById('lateness').value = deductions.lateness || 0;
                    } catch (e) {
                        console.error('Error parsing deductions data', e);
                    }
                }
            };
            xhr.send();
        }

        // Handle AJAX request for deductions data
        <?php
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_deductions' && isset($_GET['employee_id'])) {
            $employee_id = intval($_GET['employee_id']);
            $deductions_query = $conn->prepare("SELECT * FROM deductions WHERE employee_id = ?");
            $deductions_query->bind_param("i", $employee_id);
            $deductions_query->execute();
            $deductions_result = $deductions_query->get_result();

            if ($deductions_result->num_rows > 0) {
                $deductions = $deductions_result->fetch_assoc();
                echo "document.addEventListener('DOMContentLoaded', function() {";
                echo "document.getElementById('sss').value = " . ($deductions['sss'] ?? 0) . ";";
                echo "document.getElementById('philhealth').value = " . ($deductions['philhealth'] ?? 0) . ";";
                echo "document.getElementById('pagibig').value = " . ($deductions['pagibig'] ?? 0) . ";";
                echo "document.getElementById('withholding_tax').value = " . ($deductions['withholding_tax'] ?? 0) . ";";
                echo "document.getElementById('company_loans').value = " . ($deductions['company_loans'] ?? 0) . ";";
                echo "document.getElementById('salary_advances').value = " . ($deductions['salary_advances'] ?? 0) . ";";
                echo "document.getElementById('absences').value = " . ($deductions['absences'] ?? 0) . ";";
                echo "document.getElementById('lateness').value = " . ($deductions['lateness'] ?? 0) . ";";
                echo "});";
            }
            exit();
        }
        ?>
    </script>
</body>

</html>
<?php
$conn->close();
?>