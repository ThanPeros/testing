<?php
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

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_employee'])) {
        // Add new employee
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $position = $_POST['position'];
        $department = $_POST['department'];
        $salary = $_POST['salary'];

        $sql = "INSERT INTO employees (first_name, last_name, position, department, current_salary) 
                VALUES ('$first_name', '$last_name', '$position', '$department', $salary)";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Employee added successfully!";
        } else {
            $error_message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    if (isset($_POST['add_deduction'])) {
        // Add new deduction rule
        $rule_name = $_POST['rule_name'];
        $rule_type = $_POST['rule_type'];
        $rule_value = $_POST['rule_value'];

        $sql = "INSERT INTO deduction_rules (rule_name, rule_type, rule_value) 
                VALUES ('$rule_name', '$rule_type', $rule_value)";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Deduction rule added successfully!";
        } else {
            $error_message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    if (isset($_POST['assign_deduction'])) {
        // Assign deduction to employee
        $employee_id = $_POST['employee_id'];
        $rule_id = $_POST['rule_id'];
        $amount = $_POST['amount'];
        $deduction_date = $_POST['deduction_date'];
        $description = $_POST['description'];

        $sql = "INSERT INTO employee_deductions (employee_id, rule_id, amount, deduction_date, description) 
                VALUES ($employee_id, $rule_id, $amount, '$deduction_date', '$description')";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Deduction assigned successfully!";
        } else {
            $error_message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    if (isset($_POST['calculate_budget'])) {
        // Calculate budget
        $budget_year = $_POST['budget_year'];
        $department = $_POST['department'];
        $total_budget = $_POST['total_budget'];

        // This would be more complex in a real system
        $sql = "INSERT INTO budget_allocation (year, department, total_budget) 
                VALUES ($budget_year, '$department', $total_budget)
                ON DUPLICATE KEY UPDATE total_budget = $total_budget";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Budget calculated successfully!";
        } else {
            $error_message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch data for display
$employees = $conn->query("SELECT * FROM employees");
$deduction_rules = $conn->query("SELECT * FROM deduction_rules");
$employee_deductions = $conn->query("
    SELECT ed.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name, dr.rule_name 
    FROM employee_deductions ed 
    JOIN employees e ON ed.employee_id = e.id 
    JOIN deduction_rules dr ON ed.rule_id = dr.id
");
$budgets = $conn->query("SELECT * FROM budget_allocation");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation Planning & Budgeting System</title>
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
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
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

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .name-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        button {
            background: #4a6491;
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
            background: #2c3e50;
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
            background-color: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f7fa;
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

        .stat {
            font-size: 2rem;
            font-weight: bold;
            color: #4a6491;
            text-align: center;
            margin: 10px 0;
        }

        .stat-label {
            text-align: center;
            color: #666;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .name-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Compensation Planning & Budgeting</h1>
            <p class="subtitle">HR and Finance Collaboration System</p>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="dashboard">
            <div class="card">
                <h2>Employee Management</h2>
                <form method="POST">
                    <div class="form-group name-group">
                        <div>
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div>
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" required>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="HR">Human Resources</option>
                            <option value="Finance">Finance</option>
                            <option value="IT">Information Technology</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Operations">Operations</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="salary">Current Salary ($)</label>
                        <input type="number" id="salary" name="salary" step="0.01" required>
                    </div>
                    <button type="submit" name="add_employee">Add Employee</button>
                </form>
            </div>

            <div class="card">
                <h2>Deduction Rules</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="rule_name">Rule Name</label>
                        <input type="text" id="rule_name" name="rule_name" required>
                    </div>
                    <div class="form-group">
                        <label for="rule_type">Rule Type</label>
                        <select id="rule_type" name="rule_type" required>
                            <option value="">Select Type</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rule_value">Value</label>
                        <input type="number" id="rule_value" name="rule_value" step="0.01" required>
                    </div>
                    <button type="submit" name="add_deduction">Add Deduction Rule</button>
                </form>
            </div>

            <div class="card">
                <h2>Assign Deductions</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select id="employee_id" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php while ($row = $employees->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rule_id">Deduction Rule</label>
                        <select id="rule_id" name="rule_id" required>
                            <option value="">Select Rule</option>
                            <?php
                            $deduction_rules->data_seek(0); // Reset pointer
                            while ($row = $deduction_rules->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['rule_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="deduction_date">Date</label>
                        <input type="date" id="deduction_date" name="deduction_date" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <button type="submit" name="assign_deduction">Assign Deduction</button>
                </form>
            </div>

            <div class="card">
                <h2>Budget Planning</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="budget_year">Year</label>
                        <input type="number" id="budget_year" name="budget_year" min="2023" max="2030" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="HR">Human Resources</option>
                            <option value="Finance">Finance</option>
                            <option value="IT">Information Technology</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Operations">Operations</option>
                            <option value="All">All Departments</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="total_budget">Total Budget ($)</label>
                        <input type="number" id="total_budget" name="total_budget" step="0.01" required>
                    </div>
                    <button type="submit" name="calculate_budget">Calculate Budget</button>
                </form>

                <div style="margin-top: 20px;">
                    <div class="stat">$2,458,320</div>
                    <div class="stat-label">Total Annual Budget</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Employee List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $employees->data_seek(0); // Reset pointer
                    while ($row = $employees->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td>$<?php echo number_format($row['current_salary'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Employee Deductions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Deduction Rule</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $employee_deductions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['employee_name']; ?></td>
                            <td><?php echo $row['rule_name']; ?></td>
                            <td>$<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo $row['deduction_date']; ?></td>
                            <td><?php echo $row['description']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Simple JavaScript for enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    let valid = true;
                    const inputs = this.querySelectorAll('input, select, textarea');

                    inputs.forEach(input => {
                        if (input.hasAttribute('required') && !input.value) {
                            valid = false;
                            input.style.borderColor = 'red';
                        } else {
                            input.style.borderColor = '#ddd';
                        }
                    });

                    if (!valid) {
                        e.preventDefault();
                        alert('Please fill all required fields.');
                    }
                });
            });

            // Reset input styles on change
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    this.style.borderColor = '#ddd';
                });
            });
        });
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();
?>