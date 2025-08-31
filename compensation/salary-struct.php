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

// Create tables if they don't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS salary_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade_name VARCHAR(100) NOT NULL,
        min_salary DECIMAL(12, 2) NOT NULL,
        max_salary DECIMAL(12, 2) NOT NULL,
        job_level INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_grade'])) {
        $grade_name = $_POST['grade_name'];
        $min_salary = max(540, $_POST['min_salary']); // Ensure minimum is at least 540
        $max_salary = $_POST['max_salary'];
        $job_level = $_POST['job_level'];

        // Additional validation to ensure max is greater than min
        if ($max_salary <= $min_salary) {
            $error_message = "Maximum salary must be greater than minimum salary!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO salary_grades (grade_name, min_salary, max_salary, job_level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$grade_name, $min_salary, $max_salary, $job_level]);
            $success_message = "Salary grade added successfully!";
        }
    } elseif (isset($_POST['update_grade'])) {
        $grade_id = $_POST['grade_id'];
        $grade_name = $_POST['grade_name'];
        $min_salary = max(540, $_POST['min_salary']); // Ensure minimum is at least 540
        $max_salary = $_POST['max_salary'];
        $job_level = $_POST['job_level'];

        // Additional validation to ensure max is greater than min
        if ($max_salary <= $min_salary) {
            $error_message = "Maximum salary must be greater than minimum salary!";
        } else {
            $stmt = $pdo->prepare("UPDATE salary_grades SET grade_name=?, min_salary=?, max_salary=?, job_level=? WHERE id=?");
            $stmt->execute([$grade_name, $min_salary, $max_salary, $job_level, $grade_id]);
            $success_message = "Salary grade updated successfully!";
        }
    } elseif (isset($_POST['delete_grade'])) {
        $grade_id = $_POST['grade_id'];

        $stmt = $pdo->prepare("DELETE FROM salary_grades WHERE id=?");
        $stmt->execute([$grade_id]);
        $success_message = "Salary grade deleted successfully!";
    }
}

// Fetch existing salary grades
$stmt = $pdo->query("SELECT * FROM salary_grades ORDER BY job_level, min_salary");
$salary_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define job levels
$job_levels = [
    1 => 'Level 1 (Minimum Wage)',
    2 => 'Level 2 (Mid-Level)',
    3 => 'Level 3 (Executive)'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Structure & Grades Management</title>
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

        .btn-delete {
            background: var(--accent);
        }

        .btn-delete:hover {
            background: #c0392b;
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

        .salary-band {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .salary-band-range {
            flex: 1;
            height: 20px;
            background: linear-gradient(90deg, #e0e0e0, #3498db);
            border-radius: 10px;
            margin: 0 1rem;
            position: relative;
        }

        .salary-band-label {
            min-width: 100px;
            font-weight: 600;
        }

        .currency {
            font-weight: bold;
            color: #2c3e50;
        }

        .level-1 {
            background-color: #f8d7da;
        }

        .level-2 {
            background-color: #fff3cd;
        }

        .level-3 {
            background-color: #d1ecf1;
        }

        .info-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Salary Structure & Grades Management</h1>
            <p>Setup salary bands, pay grades, and pay ranges tied to job levels</p>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('manage')">Manage Grades</div>
            <div class="tab" onclick="switchTab('visualize')">Visualize Structure</div>
        </div>

        <div id="manage" class="tab-content active">
            <div class="flex-row">
                <div class="card" style="flex: 1;">
                    <h2>Add New Salary Grade</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="grade_name">Grade Name</label>
                            <input type="text" id="grade_name" name="grade_name" required>
                        </div>

                        <div class="form-group">
                            <label for="min_salary">Minimum Salary (<span class="currency">₱</span>)</label>
                            <input type="number" id="min_salary" name="min_salary" min="540" step="10" required>
                            <div class="info-text">Minimum value: ₱540.00</div>
                        </div>

                        <div class="form-group">
                            <label for="max_salary">Maximum Salary (<span class="currency">₱</span>)</label>
                            <input type="number" id="max_salary" name="max_salary" min="541" step="10" required>
                            <div class="info-text">Must be greater than minimum salary</div>
                        </div>

                        <div class="form-group">
                            <label for="job_level">Job Level</label>
                            <select id="job_level" name="job_level" required>
                                <option value="">Select Job Level</option>
                                <?php foreach ($job_levels as $level => $description): ?>
                                    <option value="<?= $level ?>"><?= htmlspecialchars($description) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="add_grade">Add Salary Grade</button>
                    </form>
                </div>

                <div class="card" style="flex: 2;">
                    <h2>Existing Salary Grades</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Grade Name</th>
                                <th>Min Salary (₱)</th>
                                <th>Max Salary (₱)</th>
                                <th>Job Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($salary_grades)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No salary grades found. Add your first one!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($salary_grades as $grade): ?>
                                    <tr class="level-<?= $grade['job_level'] ?>">
                                        <td><?= htmlspecialchars($grade['grade_name']) ?></td>
                                        <td>₱<?= number_format($grade['min_salary'], 2) ?></td>
                                        <td>₱<?= number_format($grade['max_salary'], 2) ?></td>
                                        <td><?= htmlspecialchars($job_levels[$grade['job_level']]) ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="grade_id" value="<?= $grade['id'] ?>">
                                                <input type="hidden" name="grade_name" value="<?= $grade['grade_name'] ?>">
                                                <input type="hidden" name="min_salary" value="<?= $grade['min_salary'] ?>">
                                                <input type="hidden" name="max_salary" value="<?= $grade['max_salary'] ?>">
                                                <input type="hidden" name="job_level" value="<?= $grade['job_level'] ?>">
                                                <button type="submit" name="update_grade">Update</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="grade_id" value="<?= $grade['id'] ?>">
                                                <button type="submit" name="delete_grade" class="btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="visualize" class="tab-content">
            <div class="card">
                <h2>Salary Structure Visualization</h2>

                <div class="form-group">
                    <label for="level_filter">Filter by Job Level:</label>
                    <select id="level_filter" onchange="filterVisualization()">
                        <option value="">All Levels</option>
                        <?php foreach ($job_levels as $level => $description): ?>
                            <option value="<?= $level ?>"><?= htmlspecialchars($description) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="visualization-content">
                    <?php
                    // Group by job level
                    $grades_by_level = [];
                    foreach ($salary_grades as $grade) {
                        $level = $grade['job_level'];
                        if (!isset($grades_by_level[$level])) {
                            $grades_by_level[$level] = [];
                        }
                        $grades_by_level[$level][] = $grade;
                    }

                    // Find overall min and max for scaling
                    $overall_min = 540; // Fixed minimum
                    $overall_max = 0;
                    foreach ($salary_grades as $grade) {
                        if ($grade['max_salary'] > $overall_max) $overall_max = $grade['max_salary'];
                    }
                    // Add some padding to the max for better visualization
                    $overall_max = max($overall_max * 1.1, 1000);
                    $range = $overall_max - $overall_min;

                    foreach ($grades_by_level as $level => $grades):
                    ?>
                        <div class="level-section" data-level="<?= $level ?>">
                            <h3><?= htmlspecialchars($job_levels[$level]) ?></h3>

                            <?php foreach ($grades as $grade):
                                $left = $range > 0 ? (($grade['min_salary'] - $overall_min) / $range * 100) : 0;
                                $width = $range > 0 ? (($grade['max_salary'] - $grade['min_salary']) / $range * 100) : 100;
                            ?>
                                <div class="salary-band">
                                    <div class="salary-band-label"><?= htmlspecialchars($grade['grade_name']) ?></div>
                                    <div class="salary-band-range" style="margin-left: <?= $left ?>%; width: <?= $width ?>%">
                                        <div style="position: absolute; left: 0; bottom: -20px; font-size: 0.8em;">
                                            ₱<?= number_format($grade['min_salary'], 2) ?>
                                        </div>
                                        <div style="position: absolute; right: 0; bottom: -20px; font-size: 0.8em;">
                                            ₱<?= number_format($grade['max_salary'], 2) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
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
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }

        function filterVisualization() {
            const levelFilter = document.getElementById('level_filter').value;
            const levelSections = document.querySelectorAll('.level-section');

            levelSections.forEach(section => {
                if (!levelFilter || section.getAttribute('data-level') === levelFilter) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const minSalary = form.querySelector('input[name="min_salary"]');
                    const maxSalary = form.querySelector('input[name="max_salary"]');

                    if (minSalary && maxSalary) {
                        if (parseFloat(minSalary.value) > parseFloat(maxSalary.value)) {
                            e.preventDefault();
                            alert('Minimum salary cannot be greater than maximum salary!');
                        }

                        // Ensure minimum is at least 540
                        if (parseFloat(minSalary.value) < 540) {
                            e.preventDefault();
                            alert('Minimum salary must be at least ₱540.00!');
                        }
                    }
                });
            });

            // Set up dynamic validation for max salary based on min salary
            const minSalaryInput = document.getElementById('min_salary');
            const maxSalaryInput = document.getElementById('max_salary');

            if (minSalaryInput && maxSalaryInput) {
                minSalaryInput.addEventListener('input', function() {
                    const minValue = parseFloat(this.value) || 540;
                    maxSalaryInput.min = minValue + 1;
                });
            }
        });
    </script>
</body>

</html>