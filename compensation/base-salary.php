<?php
include '../sidebar.php';
// Database connection
$host = 'localhost';
$dbname = 'systems';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Create necessary tables if they don't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS salary_structures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade VARCHAR(10) NOT NULL UNIQUE,
        level_name VARCHAR(100) NOT NULL,
        min_salary DECIMAL(12, 2) NOT NULL,
        max_salary DECIMAL(12, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS salary_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        salary_grade VARCHAR(10) NOT NULL,
        base_salary DECIMAL(12, 2) NOT NULL,
        effective_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (salary_grade) REFERENCES salary_structures(grade) ON DELETE CASCADE
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS salary_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        adjustment_type ENUM('COLA', 'Reclassification', 'Promotion', 'Merit Increase', 'Other') NOT NULL,
        adjustment_amount DECIMAL(12, 2) NOT NULL,
        description TEXT,
        effective_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )
");

// Insert sample salary structure if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM salary_structures");
if ($stmt->fetchColumn() == 0) {
    $sampleGrades = [
        ['SG-1', 'Entry Level', 12000, 18000],
        ['SG-2', 'Junior Staff', 15000, 22000],
        ['SG-3', 'Staff', 18000, 26000],
        ['SG-4', 'Senior Staff', 22000, 32000],
        ['SG-5', 'Supervisor', 28000, 40000],
        ['SG-6', 'Assistant Manager', 35000, 50000],
        ['SG-7', 'Manager', 45000, 65000],
        ['SG-8', 'Senior Manager', 60000, 85000],
        ['SG-9', 'Director', 80000, 120000],
        ['SG-10', 'Executive', 100000, 150000]
    ];

    $stmt = $pdo->prepare("INSERT INTO salary_structures (grade, level_name, min_salary, max_salary) VALUES (?, ?, ?, ?)");
    foreach ($sampleGrades as $grade) {
        $stmt->execute($grade);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_salary'])) {
        $employee_id = $_POST['employee_id'];
        $salary_grade = $_POST['salary_grade'];
        $base_salary = $_POST['base_salary'];
        $effective_date = $_POST['effective_date'];

        // Validate if base salary is within the grade range
        $stmt = $pdo->prepare("SELECT min_salary, max_salary FROM salary_structures WHERE grade = ?");
        $stmt->execute([$salary_grade]);
        $salary_range = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($base_salary < $salary_range['min_salary'] || $base_salary > $salary_range['max_salary']) {
            $error = "Base salary must be between ₱" . number_format($salary_range['min_salary'], 2) .
                " and ₱" . number_format($salary_range['max_salary'], 2) . " for this grade.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO salary_assignments (employee_id, salary_grade, base_salary, effective_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$employee_id, $salary_grade, $base_salary, $effective_date]);
            $success = "Salary assigned successfully!";
        }
    } elseif (isset($_POST['add_adjustment'])) {
        $employee_id = $_POST['employee_id'];
        $adjustment_type = $_POST['adjustment_type'];
        $adjustment_amount = $_POST['adjustment_amount'];
        $description = $_POST['description'];
        $effective_date = $_POST['effective_date'];

        $stmt = $pdo->prepare("INSERT INTO salary_adjustments (employee_id, adjustment_type, adjustment_amount, description, effective_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $adjustment_type, $adjustment_amount, $description, $effective_date]);
        $success = "Adjustment added successfully!";
    } elseif (isset($_POST['add_grade'])) {
        $grade = $_POST['grade'];
        $level_name = $_POST['level_name'];
        $min_salary = $_POST['min_salary'];
        $max_salary = $_POST['max_salary'];

        if ($min_salary >= $max_salary) {
            $error = "Minimum salary must be less than maximum salary.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO salary_structures (grade, level_name, min_salary, max_salary) VALUES (?, ?, ?, ?)");
                $stmt->execute([$grade, $level_name, $min_salary, $max_salary]);
                $success = "Salary grade added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding salary grade: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_grade'])) {
        $grade_id = $_POST['grade_id'];
        $grade = $_POST['grade'];
        $level_name = $_POST['level_name'];
        $min_salary = $_POST['min_salary'];
        $max_salary = $_POST['max_salary'];

        if ($min_salary >= $max_salary) {
            $error = "Minimum salary must be less than maximum salary.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE salary_structures SET grade = ?, level_name = ?, min_salary = ?, max_salary = ? WHERE id = ?");
                $stmt->execute([$grade, $level_name, $min_salary, $max_salary, $grade_id]);
                $success = "Salary grade updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating salary grade: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_grade'])) {
        $grade_id = $_POST['grade_id'];

        try {
            // Check if grade is being used
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM salary_assignments WHERE salary_grade = (SELECT grade FROM salary_structures WHERE id = ?)");
            $stmt->execute([$grade_id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Cannot delete this salary grade as it is assigned to employees.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM salary_structures WHERE id = ?");
                $stmt->execute([$grade_id]);
                $success = "Salary grade deleted successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error deleting salary grade: " . $e->getMessage();
        }
    }
}

// Fetch employees
$stmt = $pdo->query("SELECT id, first_name, last_name, position, department FROM employees ORDER BY last_name, first_name");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch salary structures
$stmt = $pdo->query("SELECT * FROM salary_structures ORDER BY grade");
$salary_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch salary assignments
$stmt = $pdo->query("
    SELECT sa.*, e.first_name, e.last_name, ss.level_name, ss.min_salary, ss.max_salary
    FROM salary_assignments sa 
    JOIN employees e ON sa.employee_id = e.id 
    JOIN salary_structures ss ON sa.salary_grade = ss.grade
    ORDER BY sa.effective_date DESC
");
$salary_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch salary adjustments
$stmt = $pdo->query("
    SELECT adj.*, e.first_name, e.last_name 
    FROM salary_adjustments adj 
    JOIN employees e ON adj.employee_id = e.id 
    ORDER BY adj.effective_date DESC
");
$salary_adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define adjustment types
$adjustment_types = [
    'COLA' => 'Cost of Living Adjustment (COLA)',
    'Reclassification' => 'Position Reclassification',
    'Promotion' => 'Promotion',
    'Merit Increase' => 'Merit Increase',
    'Other' => 'Other Adjustment'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Grade Assignment System</title>
    <style>
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
            max-width: 1400px;
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
        select,
        textarea {
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

        .btn-delete {
            background: var(--accent);
        }

        .btn-delete:hover {
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
            margin-top: 1rem;
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
            font-weight: 500;
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

        .flex-row {
            display: flex;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .flex-row {
                flex-direction: column;
            }
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
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
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

        .currency {
            font-weight: bold;
            color: #2c3e50;
        }

        .positive-adj {
            color: var(--success);
        }

        .negative-adj {
            color: var(--accent);
        }

        .section-title {
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .salary-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .salary-validation {
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .valid-salary {
            color: var(--success);
        }

        .invalid-salary {
            color: var(--accent);
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
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Salary Grade Assignment System</h1>
            <p>Assign salary grades and manage salary adjustments for employees</p>
        </header>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('assign')">Assign Salary Grade</div>
            <div class="tab" onclick="switchTab('adjustments')">Salary Adjustments</div>
            <div class="tab" onclick="switchTab('records')">Salary Records</div>
            <div class="tab" onclick="switchTab('structure')">Salary Structure</div>
        </div>

        <div id="assign" class="tab-content active">
            <div class="flex-row">
                <div class="card" style="flex: 1;">
                    <h2 class="section-title">Assign Salary Grade</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>">
                                        <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                        (<?= htmlspecialchars($employee['position']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="salary_grade">Salary Grade</label>
                            <select id="salary_grade" name="salary_grade" required onchange="updateSalaryRange()">
                                <option value="">Select Salary Grade</option>
                                <?php foreach ($salary_structures as $grade): ?>
                                    <option value="<?= $grade['grade'] ?>" data-min="<?= $grade['min_salary'] ?>" data-max="<?= $grade['max_salary'] ?>">
                                        <?= htmlspecialchars($grade['grade'] . ' - ' . $grade['level_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="salary_range_info" class="salary-info" style="display: none;">
                                Salary Range: ₱<span id="min_salary"></span> to ₱<span id="max_salary"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="base_salary">Base Salary (<span class="currency">₱</span>)</label>
                            <input type="number" id="base_salary" name="base_salary" step="100" required onkeyup="validateSalary()">
                            <div id="salary_validation" class="salary-validation"></div>
                        </div>

                        <div class="form-group">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <button type="submit" name="assign_salary" class="btn-success">Assign Salary Grade</button>
                    </form>
                </div>

                <div class="card" style="flex: 1;">
                    <h2 class="section-title">Recent Salary Assignments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Salary Grade</th>
                                <th>Level</th>
                                <th>Base Salary</th>
                                <th>Effective Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($salary_assignments, 0, 5) as $assignment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['salary_grade']) ?></td>
                                    <td><?= htmlspecialchars($assignment['level_name']) ?></td>
                                    <td>₱<?= number_format($assignment['base_salary'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($assignment['effective_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="adjustments" class="tab-content">
            <div class="flex-row">
                <div class="card" style="flex: 1;">
                    <h2 class="section-title">Add Salary Adjustment</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="adj_employee_id">Employee</label>
                            <select id="adj_employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>">
                                        <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                        (<?= htmlspecialchars($employee['position']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="adjustment_type">Adjustment Type</label>
                            <select id="adjustment_type" name="adjustment_type" required>
                                <option value="">Select Adjustment Type</option>
                                <?php foreach ($adjustment_types as $type => $description): ?>
                                    <option value="<?= $type ?>"><?= htmlspecialchars($description) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="adjustment_amount">Adjustment Amount (<span class="currency">₱</span>)</label>
                            <input type="number" id="adjustment_amount" name="adjustment_amount" step="100" required>
                            <small>Use negative values for deductions</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="adj_effective_date">Effective Date</label>
                            <input type="date" id="adj_effective_date" name="effective_date" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <button type="submit" name="add_adjustment" class="btn-success">Add Adjustment</button>
                    </form>
                </div>

                <div class="card" style="flex: 1;">
                    <h2 class="section-title">Recent Adjustments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Effective Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($salary_adjustments, 0, 5) as $adjustment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($adjustment['first_name'] . ' ' . $adjustment['last_name']) ?></td>
                                    <td><?= htmlspecialchars($adjustment_types[$adjustment['adjustment_type']]) ?></td>
                                    <td class="<?= $adjustment['adjustment_amount'] >= 0 ? 'positive-adj' : 'negative-adj' ?>">
                                        ₱<?= number_format($adjustment['adjustment_amount'], 2) ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($adjustment['effective_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="records" class="tab-content">
            <div class="card">
                <h2 class="section-title">Salary Assignment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Salary Grade</th>
                            <th>Level</th>
                            <th>Base Salary</th>
                            <th>Salary Range</th>
                            <th>Effective Date</th>
                            <th>Assigned On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salary_assignments as $assignment): ?>
                            <tr>
                                <td><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></td>
                                <td><?= htmlspecialchars($assignment['salary_grade']) ?></td>
                                <td><?= htmlspecialchars($assignment['level_name']) ?></td>
                                <td>₱<?= number_format($assignment['base_salary'], 2) ?></td>
                                <td>₱<?= number_format($assignment['min_salary'], 2) ?> - ₱<?= number_format($assignment['max_salary'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($assignment['effective_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($assignment['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="section-title">Salary Adjustment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Effective Date</th>
                            <th>Applied On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salary_adjustments as $adjustment): ?>
                            <tr>
                                <td><?= htmlspecialchars($adjustment['first_name'] . ' ' . $adjustment['last_name']) ?></td>
                                <td><?= htmlspecialchars($adjustment_types[$adjustment['adjustment_type']]) ?></td>
                                <td class="<?= $adjustment['adjustment_amount'] >= 0 ? 'positive-adj' : 'negative-adj' ?>">
                                    ₱<?= number_format($adjustment['adjustment_amount'], 2) ?>
                                </td>
                                <td><?= htmlspecialchars($adjustment['description']) ?></td>
                                <td><?= date('M j, Y', strtotime($adjustment['effective_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($adjustment['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="structure" class="tab-content">
        <div class="flex-row">
            <div class="card" style="flex: 1;">
                <h2 class="section-title">Add New Salary Grade</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="grade">Grade Code</label>
                        <input type="text" id="grade" name="grade" required placeholder="e.g., SG-11">
                    </div>

                    <div class="form-group">
                        <label for="level_name">Level Name</label>
                        <input type="text" id="level_name" name="level_name" required placeholder="e.g., Senior Executive">
                    </div>

                    <div class="form-group">
                        <label for="min_salary">Minimum Salary (<span class="currency">₱</span>)</label>
                        <input type="number" id="min_salary" name="min_salary" step="100" required>
                    </div>

                    <div class="form-group">
                        <label for="max_salary">Maximum Salary (<span class="currency">₱</span>)</label>
                        <input type="number" id="max_salary" name="max_salary" step="100" required>
                    </div>

                    <button type="submit" name="add_grade" class="btn-success">Add Salary Grade</button>
                </form>
            </div>

            <div class="card" style="flex: 1;">
                <h2 class="section-title">Salary Structure</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Level Name</th>
                            <th>Minimum Salary</th>
                            <th>Maximum Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salary_structures as $structure): ?>
                            <tr>
                                <td><?= htmlspecialchars($structure['grade']) ?></td>
                                <td><?= htmlspecialchars($structure['level_name']) ?></td>
                                <td>₱<?= number_format($structure['min_salary'], 2) ?></td>
                                <td>₱<?= number_format($structure['max_salary'], 2) ?></td>
                                <td>
                                    <button onclick="editGrade(<?= $structure['id'] ?>, '<?= $structure['grade'] ?>', '<?= $structure['level_name'] ?>', <?= $structure['min_salary'] ?>, <?= $structure['max_salary'] ?>)">Edit</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="grade_id" value="<?= $structure['id'] ?>">
                                        <button type="submit" name="delete_grade" class="btn-delete" onclick="return confirm('Are you sure you want to delete this salary grade?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Edit Grade Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Salary Grade</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="grade_id" id="edit_grade_id">
                <div class="form-group">
                    <label for="edit_grade">Grade Code</label>
                    <input type="text" id="edit_grade" name="grade" required>
                </div>

                <div class="form-group">
                    <label for="edit_level_name">Level Name</label>
                    <input type="text" id="edit_level_name" name="level_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_min_salary">Minimum Salary (<span class="currency">₱</span>)</label>
                    <input type="number" id="edit_min_salary" name="min_salary" step="100" required>
                </div>

                <div class="form-group">
                    <label for="edit_max_salary">Maximum Salary (<span class="currency">₱</span>)</label>
                    <input type="number" id="edit_max_salary" name="max_salary" step="100" required>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="update_grade" class="btn-success">Update</button>
                    <button type="button" onclick="closeModal()">Cancel</button>
                </div>
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

        function updateSalaryRange() {
            const gradeSelect = document.getElementById('salary_grade');
            const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
            const infoDiv = document.getElementById('salary_range_info');

            if (selectedOption.value) {
                document.getElementById('min_salary').textContent =
                    Number(selectedOption.dataset.min).toLocaleString('en-PH', {
                        minimumFractionDigits: 2
                    });
                document.getElementById('max_salary').textContent =
                    Number(selectedOption.dataset.max).toLocaleString('en-PH', {
                        minimumFractionDigits: 2
                    });
                infoDiv.style.display = 'block';

                // Set base salary to the minimum as default
                document.getElementById('base_salary').value = selectedOption.dataset.min;
                validateSalary();
            } else {
                infoDiv.style.display = 'none';
            }
        }

        function validateSalary() {
            const gradeSelect = document.getElementById('salary_grade');
            const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
            const baseSalary = parseFloat(document.getElementById('base_salary').value);
            const validationDiv = document.getElementById('salary_validation');

            if (selectedOption.value && !isNaN(baseSalary)) {
                const minSalary = parseFloat(selectedOption.dataset.min);
                const maxSalary = parseFloat(selectedOption.dataset.max);

                if (baseSalary < minSalary) {
                    validationDiv.innerHTML = `<span class="invalid-salary">Salary is below the minimum (₱${minSalary.toLocaleString('en-PH', {minimumFractionDigits: 2})}) for this grade.</span>`;
                } else if (baseSalary > maxSalary) {
                    validationDiv.innerHTML = `<span class="invalid-salary">Salary is above the maximum (₱${maxSalary.toLocaleString('en-PH', {minimumFractionDigits: 2})}) for this grade.</span>`;
                } else {
                    validationDiv.innerHTML = `<span class="valid-salary">Salary is within the valid range for this grade.</span>`;
                }
            } else {
                validationDiv.innerHTML = '';
            }
        }

        function editGrade(id, grade, levelName, minSalary, maxSalary) {
            document.getElementById('edit_grade_id').value = id;
            document.getElementById('edit_grade').value = grade;
            document.getElementById('edit_level_name').value = levelName;
            document.getElementById('edit_min_salary').value = minSalary;
            document.getElementById('edit_max_salary').value = maxSalary;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal if clicked outside
            window.onclick = function(event) {
                const modal = document.getElementById('editModal');
                if (event.target == modal) {
                    closeModal();
                }
            }
        });
    </script>
</body>

</html>