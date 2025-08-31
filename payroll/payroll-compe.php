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

// Create tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS employees (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        hire_date DATE NOT NULL,
        department VARCHAR(100),
        position VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS salary_structures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade VARCHAR(10) NOT NULL UNIQUE,
        level_name VARCHAR(100) NOT NULL,
        min_salary DECIMAL(12, 2) NOT NULL,
        max_salary DECIMAL(12, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS salary_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        salary_grade VARCHAR(10) NOT NULL,
        base_salary DECIMAL(12, 2) NOT NULL,
        effective_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS salary_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        adjustment_type ENUM('COLA', 'Reclassification', 'Promotion', 'Merit Increase', 'Other') NOT NULL,
        adjustment_amount DECIMAL(12, 2) NOT NULL,
        description TEXT,
        effective_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS deduction_rules (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rule_name VARCHAR(100) NOT NULL,
        deduction_type ENUM('fixed', 'percentage') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        applies_to_all BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS allowances (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(6) UNSIGNED NOT NULL,
        transport DECIMAL(10,2) DEFAULT 0,
        meal DECIMAL(10,2) DEFAULT 0,
        housing DECIMAL(10,2) DEFAULT 0,
        communication DECIMAL(10,2) DEFAULT 0,
        other_benefits DECIMAL(10,2) DEFAULT 0,
        benefits_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS time_attendance (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(6) UNSIGNED NOT NULL,
        overtime_hours DECIMAL(4,2) DEFAULT 0,
        undertime_hours DECIMAL(4,2) DEFAULT 0,
        leaves INT(3) DEFAULT 0,
        absences INT(3) DEFAULT 0,
        record_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS loans_deductions (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(6) UNSIGNED NOT NULL,
        sss_loans DECIMAL(10,2) DEFAULT 0,
        company_loans DECIMAL(10,2) DEFAULT 0,
        other_deductions DECIMAL(10,2) DEFAULT 0,
        schedule_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error;
    }
}

// Insert sample data if tables are empty
$check_employees = $conn->query("SELECT COUNT(*) as count FROM employees");
$row = $check_employees->fetch_assoc();
if ($row['count'] == 0) {
    // Insert sample employees
    $sample_employees = [
        "INSERT INTO employees (first_name, last_name, email, phone, hire_date, department, position) VALUES 
        ('John', 'Doe', 'john.doe@example.com', '123-456-7890', '2022-01-15', 'IT', 'Software Developer')",
        "INSERT INTO employees (first_name, last_name, email, phone, hire_date, department, position) VALUES 
        ('Jane', 'Smith', 'jane.smith@example.com', '123-456-7891', '2021-03-20', 'HR', 'HR Manager')",
        "INSERT INTO employees (first_name, last_name, email, phone, hire_date, department, position) VALUES 
        ('Robert', 'Johnson', 'robert.j@example.com', '123-456-7892', '2020-05-10', 'Finance', 'Financial Analyst')"
    ];

    foreach ($sample_employees as $sql) {
        $conn->query($sql);
    }

    // Insert sample salary structures
    $sample_structures = [
        "INSERT INTO salary_structures (grade, level_name, min_salary, max_salary) VALUES 
        ('G1', 'Entry Level', 25000, 35000)",
        "INSERT INTO salary_structures (grade, level_name, min_salary, max_salary) VALUES 
        ('G2', 'Intermediate', 35000, 50000)",
        "INSERT INTO salary_structures (grade, level_name, min_salary, max_salary) VALUES 
        ('G3', 'Senior Level', 50000, 75000)"
    ];

    foreach ($sample_structures as $sql) {
        $conn->query($sql);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['compensation_submit'])) {
        $employee_id = $_POST['employee_id'];
        $salary_grade = $_POST['salary_grade'];
        $base_salary = $_POST['base_salary'];
        $effective_date = $_POST['effective_date'];

        $stmt = $conn->prepare("INSERT INTO salary_assignments (employee_id, salary_grade, base_salary, effective_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $employee_id, $salary_grade, $base_salary, $effective_date);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Salary assignment saved successfully!</div>";
        } else {
            echo "<div class='error-msg'>Error saving salary assignment: " . $conn->error . "</div>";
        }
    }

    if (isset($_POST['adjustment_submit'])) {
        $employee_id = $_POST['employee_id'];
        $adjustment_type = $_POST['adjustment_type'];
        $adjustment_amount = $_POST['adjustment_amount'];
        $description = $_POST['description'];
        $effective_date = $_POST['effective_date'];

        $stmt = $conn->prepare("INSERT INTO salary_adjustments (employee_id, adjustment_type, adjustment_amount, description, effective_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $employee_id, $adjustment_type, $adjustment_amount, $description, $effective_date);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Salary adjustment saved successfully!</div>";
        } else {
            echo "<div class='error-msg'>Error saving salary adjustment: " . $conn->error . "</div>";
        }
    }

    if (isset($_POST['allowance_submit'])) {
        $employee_id = $_POST['employee_id'];
        $transport = $_POST['transport'];
        $meal = $_POST['meal'];
        $housing = $_POST['housing'];
        $communication = $_POST['communication'];
        $other_benefits = $_POST['other_benefits'];
        $benefits_description = $_POST['benefits_description'];

        $stmt = $conn->prepare("INSERT INTO allowances (employee_id, transport, meal, housing, communication, other_benefits, benefits_description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iddddds", $employee_id, $transport, $meal, $housing, $communication, $other_benefits, $benefits_description);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Allowances saved successfully!</div>";
        } else {
            echo "<div class='error-msg'>Error saving allowances: " . $conn->error . "</div>";
        }
    }

    if (isset($_POST['attendance_submit'])) {
        $employee_id = $_POST['employee_id'];
        $overtime_hours = $_POST['overtime_hours'];
        $undertime_hours = $_POST['undertime_hours'];
        $leaves = $_POST['leaves'];
        $absences = $_POST['absences'];
        $record_date = $_POST['record_date'];

        $stmt = $conn->prepare("INSERT INTO time_attendance (employee_id, overtime_hours, undertime_hours, leaves, absences, record_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iddiis", $employee_id, $overtime_hours, $undertime_hours, $leaves, $absences, $record_date);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Time & attendance data saved successfully!</div>";
        } else {
            echo "<div class='error-msg'>Error saving time & attendance data: " . $conn->error . "</div>";
        }
    }

    if (isset($_POST['loans_submit'])) {
        $employee_id = $_POST['employee_id'];
        $sss_loans = $_POST['sss_loans'];
        $company_loans = $_POST['company_loans'];
        $other_deductions = $_POST['other_deductions'];
        $schedule_date = $_POST['schedule_date'];

        $stmt = $conn->prepare("INSERT INTO loans_deductions (employee_id, sss_loans, company_loans, other_deductions, schedule_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iddds", $employee_id, $sss_loans, $company_loans, $other_deductions, $schedule_date);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Loans & deductions data saved successfully!</div>";
        } else {
            echo "<div class='error-msg'>Error saving loans & deductions data: " . $conn->error . "</div>";
        }
    }
}

// Fetch data for display
$employees = $conn->query("SELECT * FROM employees ORDER BY first_name, last_name");
$salary_structures = $conn->query("SELECT * FROM salary_structures ORDER BY grade");
$salary_assignments = $conn->query("SELECT sa.*, e.first_name, e.last_name FROM salary_assignments sa JOIN employees e ON sa.employee_id = e.id ORDER BY sa.created_at DESC LIMIT 5");
$adjustments = $conn->query("SELECT sa.*, e.first_name, e.last_name FROM salary_adjustments sa JOIN employees e ON sa.employee_id = e.id ORDER BY sa.created_at DESC LIMIT 5");
$allowances = $conn->query("SELECT a.*, e.first_name, e.last_name FROM allowances a JOIN employees e ON a.id = e.id ORDER BY a.created_at DESC LIMIT 5");
$attendance_data = $conn->query("SELECT ta.*, e.first_name, e.last_name FROM time_attendance ta JOIN employees e ON ta.employee_id = e.id ORDER BY ta.created_at DESC LIMIT 5");
$loans_data = $conn->query("SELECT ld.*, e.first_name, e.last_name FROM loans_deductions ld JOIN employees e ON ld.employee_id = e.id ORDER BY ld.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Integration System</title>
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
            padding-bottom: 20px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 15px 0;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .description {
            font-size: 1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaeaea;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        button {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.3s;
            margin-top: 5px;
        }

        button:hover {
            background: #4a6491;
        }

        .recent-data {
            margin-top: 20px;
        }

        .recent-data h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 0.8rem;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-size: 0.85rem;
        }

        .success-msg {
            background-color: #d4edda;
            color: #155724;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            overflow-x: auto;
            white-space: nowrap;
        }

        .tab-button {
            padding: 10px 20px;
            background: #f1f1f1;
            border: none;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            min-width: 120px;
            text-align: center;
        }

        .tab-button.active {
            background: #2c3e50;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .compact-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .full-width {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-button {
                margin-bottom: 5px;
                border-radius: 4px;
                min-width: auto;
            }

            .compact-form {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            h1 {
                font-size: 1.5rem;
            }
        }

        .form-section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }

        .form-section-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a6491;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Payroll Integration System</h1>
            <p class="description">Integrated system for managing employee compensation, time & attendance, and loans/deductions.</p>
        </div>
    </header>

    <div class="container">
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('compensation')">Compensation</button>
            <button class="tab-button" onclick="openTab('attendance')">Time & Attendance</button>
            <button class="tab-button" onclick="openTab('loans')">Loans & Deductions</button>
            <button class="tab-button" onclick="openTab('reports')">Reports</button>
        </div>

        <div id="compensation" class="tab-content active">
            <div class="dashboard">
                <div class="card">
                    <h2>Salary Assignment</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="salary_grade">Salary Grade</label>
                            <select id="salary_grade" name="salary_grade" required>
                                <option value="">Select Grade</option>
                                <?php while ($grade = $salary_structures->fetch_assoc()): ?>
                                    <option value="<?php echo $grade['grade']; ?>">
                                        <?php echo $grade['grade'] . ' - ' . $grade['level_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="base_salary">Base Salary (₱)</label>
                            <input type="number" id="base_salary" name="base_salary" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" required>
                        </div>
                        <div class="form-group full-width">
                            <button type="submit" name="compensation_submit">Assign Salary</button>
                        </div>
                    </form>

                    <div class="recent-data">
                        <h3>Recent Salary Assignments</h3>
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>Grade</th>
                                <th>Base Salary</th>
                                <th>Effective Date</th>
                            </tr>
                            <?php if ($salary_assignments && $salary_assignments->num_rows > 0): ?>
                                <?php while ($row = $salary_assignments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td><?php echo $row['salary_grade']; ?></td>
                                        <td>₱<?php echo number_format($row['base_salary'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['effective_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No salary assignments found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h2>Salary Adjustments</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id_adj">Employee</label>
                            <select id="employee_id_adj" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees->data_seek(0); // Reset pointer
                                while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adjustment_type">Adjustment Type</label>
                            <select id="adjustment_type" name="adjustment_type" required>
                                <option value="">Select Type</option>
                                <option value="COLA">Cost of Living Adjustment (COLA)</option>
                                <option value="Reclassification">Reclassification</option>
                                <option value="Promotion">Promotion</option>
                                <option value="Merit Increase">Merit Increase</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="adjustment_amount">Amount (₱)</label>
                            <input type="number" id="adjustment_amount" name="adjustment_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="effective_date_adj">Effective Date</label>
                            <input type="date" id="effective_date_adj" name="effective_date" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <button type="submit" name="adjustment_submit">Apply Adjustment</button>
                        </div>
                    </form>

                    <div class="recent-data">
                        <h3>Recent Salary Adjustments</h3>
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Effective Date</th>
                            </tr>
                            <?php if ($adjustments && $adjustments->num_rows > 0): ?>
                                <?php while ($row = $adjustments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td><?php echo $row['adjustment_type']; ?></td>
                                        <td>₱<?php echo number_format($row['adjustment_amount'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['effective_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No salary adjustments found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h2>Allowances & Benefits</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id_all">Employee</label>
                            <select id="employee_id_all" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees->data_seek(0); // Reset pointer
                                while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-section full-width">
                            <div class="form-section-title">Allowance Amounts (₱)</div>
                            <div class="form-group">
                                <label for="transport">Transport</label>
                                <input type="number" id="transport" name="transport" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label for="meal">Meal</label>
                                <input type="number" id="meal" name="meal" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label for="housing">Housing</label>
                                <input type="number" id="housing" name="housing" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label for="communication">Communication</label>
                                <input type="number" id="communication" name="communication" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label for="other_benefits">Other Benefits</label>
                                <input type="number" id="other_benefits" name="other_benefits" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="benefits_description">Benefits Description</label>
                            <textarea id="benefits_description" name="benefits_description" rows="2"></textarea>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="allowance_submit">Save Allowances</button>
                        </div>
                    </form>

                    <div class="recent-data">
                        <h3>Recent Allowances</h3>
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>Transport</th>
                                <th>Meal</th>
                                <th>Housing</th>
                                <th>Date</th>
                            </tr>
                            <?php if ($allowances && $allowances->num_rows > 0): ?>
                                <?php while ($row = $allowances->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td>₱<?php echo number_format($row['transport'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['meal'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['housing'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No allowances data found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="attendance" class="tab-content">
            <div class="dashboard">
                <div class="card">
                    <h2>Time & Attendance</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id_ta">Employee</label>
                            <select id="employee_id_ta" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees->data_seek(0); // Reset pointer
                                while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="overtime_hours">Overtime Hours</label>
                            <input type="number" id="overtime_hours" name="overtime_hours" step="0.1" value="0">
                        </div>
                        <div class="form-group">
                            <label for="undertime_hours">Undertime Hours</label>
                            <input type="number" id="undertime_hours" name="undertime_hours" step="0.1" value="0">
                        </div>
                        <div class="form-group">
                            <label for="leaves">Leaves (days)</label>
                            <input type="number" id="leaves" name="leaves" value="0">
                        </div>
                        <div class="form-group">
                            <label for="absences">Absences (days)</label>
                            <input type="number" id="absences" name="absences" value="0">
                        </div>
                        <div class="form-group">
                            <label for="record_date">Record Date</label>
                            <input type="date" id="record_date" name="record_date" required>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="attendance_submit">Submit Attendance Data</button>
                        </div>
                    </form>

                    <div class="recent-data">
                        <h3>Recent Attendance Entries</h3>
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>Overtime</th>
                                <th>Undertime</th>
                                <th>Leaves</th>
                                <th>Date</th>
                            </tr>
                            <?php if ($attendance_data && $attendance_data->num_rows > 0): ?>
                                <?php while ($row = $attendance_data->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td><?php echo $row['overtime_hours']; ?> hrs</td>
                                        <td><?php echo $row['undertime_hours']; ?> hrs</td>
                                        <td><?php echo $row['leaves']; ?> days</td>
                                        <td><?php echo date('M j, Y', strtotime($row['record_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No attendance data found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="loans" class="tab-content">
            <div class="dashboard">
                <div class="card">
                    <h2>Loans & Deductions</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id_loans">Employee</label>
                            <select id="employee_id_loans" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees->data_seek(0); // Reset pointer
                                while ($employee = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="sss_loans">SSS Loans (₱)</label>
                            <input type="number" id="sss_loans" name="sss_loans" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="company_loans">Company Loans (₱)</label>
                            <input type="number" id="company_loans" name="company_loans" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="other_deductions">Other Deductions (₱)</label>
                            <input type="number" id="other_deductions" name="other_deductions" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="schedule_date">Schedule Date</label>
                            <input type="date" id="schedule_date" name="schedule_date" required>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="loans_submit">Submit Loans Data</button>
                        </div>
                    </form>

                    <div class="recent-data">
                        <h3>Recent Loans Entries</h3>
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>SSS Loans</th>
                                <th>Company Loans</th>
                                <th>Other Deductions</th>
                                <th>Date</th>
                            </tr>
                            <?php if ($loans_data && $loans_data->num_rows > 0): ?>
                                <?php while ($row = $loans_data->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td>₱<?php echo number_format($row['sss_loans'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['company_loans'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['other_deductions'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['schedule_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No loans data found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="reports" class="tab-content">
            <div class="card">
                <h2>Employee Compensation Reports</h2>
                <div class="recent-data">
                    <h3>All Employees</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Hire Date</th>
                        </tr>
                        <?php
                        $employees->data_seek(0); // Reset pointer
                        if ($employees && $employees->num_rows > 0): ?>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $employee['id']; ?></td>
                                    <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                    <td><?php echo $employee['department']; ?></td>
                                    <td><?php echo $employee['position']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($employee['hire_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No employee data found</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>Payroll Integration System &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script>
        // Set default date values to today
        document.getElementById('effective_date').valueAsDate = new Date();
        document.getElementById('effective_date_adj').valueAsDate = new Date();
        document.getElementById('record_date').valueAsDate = new Date();
        document.getElementById('schedule_date').valueAsDate = new Date();

        // Tab functionality
        function openTab(tabName) {
            var i, tabContent, tabButtons;

            // Hide all tab content
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].classList.remove("active");
            }

            // Remove active class from all buttons
            tabButtons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            // Show the specific tab content and add active class to the button
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }

        // Simple form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let valid = true;
                this.querySelectorAll('input[required], select[required]').forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = 'red';
                    } else {
                        input.style.borderColor = '#ddd';
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>