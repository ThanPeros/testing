<?php
include '../sidebar.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create positions table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(title)
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating positions table: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['employee_id'])) {
        $employee_id = intval($_POST['employee_id']);
        $department = $_POST['department'];
        $position = $_POST['position'];

        $sql = "UPDATE employees SET department=?, position=? WHERE id=? AND status='approved'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $department, $position, $employee_id);

        if ($stmt->execute()) {
            $success_message = "Employee assignment updated successfully!";
        } else {
            $error_message = "Error updating assignment: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get only approved employees WITH department and position fields
$employees = $conn->query("SELECT id, first_name, last_name, department, position FROM employees WHERE status='approved' ORDER BY first_name");

// Get all departments and positions from database
$departments_result = $conn->query("SHOW TABLES LIKE 'departments'");
if ($departments_result->num_rows > 0) {
    $departments = $conn->query("SELECT name FROM departments ORDER BY name");
} else {
    $departments = ['Operations', 'Logistics', 'Human Resources', 'Finance', 'IT'];
}

// Get positions from the positions table
$positions = $conn->query("SELECT title FROM positions ORDER BY title");
if ($positions->num_rows == 0) {
    // Insert default positions if table is empty
    $defaultPositions = ['Driver', 'Dock Worker', 'Supervisor', 'Manager', 'Coordinator', 'Analyst', 'Specialist'];
    foreach ($defaultPositions as $position) {
        $conn->query("INSERT IGNORE INTO positions (title) VALUES ('$position')");
    }
    $positions = $conn->query("SELECT title FROM positions ORDER BY title");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Positions & Departments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #3498db;
            --secondary-hover: #2980b9;
            --danger-color: #e74c3c;
            --danger-hover: #c0392b;
            --text-color: #333;
            --light-gray: #f5f7fa;
            --border-color: #ddd;
            --card-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            --success-color: #27ae60;
            --error-color: #c0392b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-gray);
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.8rem;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        select,
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        select:focus,
        input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        button {
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: var(--secondary-color);
        }

        .btn-secondary:hover {
            background: var(--secondary-hover);
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-danger:hover {
            background: var(--danger-hover);
        }

        .success {
            color: var(--success-color);
            margin: 15px 0;
            padding: 12px;
            background-color: #e8f6ef;
            border-radius: 6px;
            border-left: 4px solid var(--success-color);
        }

        .error {
            color: var(--error-color);
            margin: 15px 0;
            padding: 12px;
            background-color: #fdedec;
            border-radius: 6px;
            border-left: 4px solid var(--error-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f8ff;
        }

        .status-approved {
            color: var(--success-color);
            font-weight: 600;
        }

        .no-employees {
            color: #7f8c8d;
            font-style: italic;
            text-align: center;
            padding: 30px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-input {
            width: 300px;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #7f8c8d;
        }

        .close:hover {
            color: #2c3e50;
        }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin: 10px auto;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
                margin-bottom: 0;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th,
            td {
                padding: 10px;
            }

            .actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }

            /* Make form elements more mobile-friendly */
            select,
            input {
                padding: 14px 12px;
                font-size: 16px;
                /* Prevents zoom on iOS */
            }

            button {
                width: 100%;
                padding: 14px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.3rem;
            }

            h2 {
                font-size: 1.2rem;
            }

            .card {
                padding: 12px;
            }

            th,
            td {
                padding: 8px;
                font-size: 14px;
            }

            .modal-content {
                padding: 20px;
            }

            /* Stack table rows for very small screens */
            .mobile-stack {
                display: flex;
                flex-direction: column;
            }

            .mobile-stack td {
                display: block;
                width: 100%;
                padding: 5px 0;
                border: none;
            }

            .mobile-stack td:before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                width: 40%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Employee Assignments</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?= $success_message ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Assign Position & Department</h2>
            <form method="POST" id="assignment-form">
                <div class="form-group">
                    <label for="employee_id">Select Employee:</label>
                    <select name="employee_id" id="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php if ($employees->num_rows > 0): ?>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <option value="<?= $employee['id'] ?>"
                                    data-dept="<?= htmlspecialchars($employee['department'] ?? '') ?>"
                                    data-position="<?= htmlspecialchars($employee['position'] ?? '') ?>">
                                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                    <?php if (!empty($employee['department']) || !empty($employee['position'])): ?>
                                        (Current: <?= htmlspecialchars($employee['department'] ?? 'No dept') ?>/<?= htmlspecialchars($employee['position'] ?? 'No position') ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No approved employees available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department:</label>
                    <select name="department" id="department" required>
                        <option value="">-- Select Department --</option>
                        <?php if (is_object($departments)): ?>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="position">Position:</label>
                    <select name="position" id="position" required>
                        <option value="">-- Select Position --</option>
                        <?php
                        // Reset positions pointer
                        $positions->data_seek(0);
                        while ($p = $positions->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary" <?= ($employees->num_rows == 0) ? 'disabled' : '' ?>>
                    <i class="fas fa-save"></i> Save Assignment
                </button>
            </form>
        </div>

        <div class="card">
            <div class="search-container">
                <h2>Current Employee Assignments</h2>
                <input type="text" id="searchInput" class="search-input" placeholder="Search employees...">
            </div>

            <?php
            // Reset the employees result set for the table
            $employees->data_seek(0);
            if ($employees->num_rows > 0): ?>
                <table id="assignmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees->fetch_assoc()): ?>
                            <tr class="mobile-stack">
                                <td data-label="ID"><?= htmlspecialchars($employee['id']) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                <td data-label="Department"><?= htmlspecialchars($employee['department'] ?: 'Not assigned') ?></td>
                                <td data-label="Position"><?= htmlspecialchars($employee['position'] ?: 'Not assigned') ?></td>
                                <td data-label="Status" class="status-approved">Approved</td>
                                <td data-label="Actions" class="actions">
                                    <button class="action-btn btn-secondary" onclick="openEditModal(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>', '<?= htmlspecialchars($employee['department']) ?>', '<?= htmlspecialchars($employee['position']) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-employees">No approved employees available for assignment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Employee Assignment</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" id="edit_employee_id" name="employee_id">

                <div class="form-group">
                    <label for="edit_employee_name">Employee:</label>
                    <input type="text" id="edit_employee_name" class="search-input" readonly>
                </div>

                <div class="form-group">
                    <label for="edit_department">Department:</label>
                    <select name="department" id="edit_department" required>
                        <option value="">-- Select Department --</option>
                        <?php
                        // Reset departments pointer
                        if (is_object($departments)) {
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endwhile;
                        } else {
                            foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach;
                        } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_position">Position:</label>
                    <select name="position" id="edit_position" required>
                        <option value="">-- Select Position --</option>
                        <?php
                        // Reset positions pointer
                        $positions->data_seek(0);
                        while ($p = $positions->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Update Assignment
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-fill current assignments when employee is selected
        document.getElementById('employee_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentDept = selectedOption.getAttribute('data-dept') || '';
            const currentPosition = selectedOption.getAttribute('data-position') || '';

            document.getElementById('department').value = currentDept;
            document.getElementById('position').value = currentPosition;
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const input = this.value.toLowerCase();
            const table = document.getElementById('assignmentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const dept = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                const position = rows[i].getElementsByTagName('td')[3].textContent.toLowerCase();

                if (name.includes(input) || dept.includes(input) || position.includes(input)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });

        // Modal functions
        function openEditModal(employeeId, employeeName, department, position) {
            document.getElementById('edit_employee_id').value = employeeId;
            document.getElementById('edit_employee_name').value = employeeName;
            document.getElementById('edit_department').value = department;
            document.getElementById('edit_position').value = position;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        };

        // Make table rows stack on very small screens
        function checkScreenSize() {
            const tableRows = document.querySelectorAll('#assignmentsTable tr');
            if (window.innerWidth <= 480) {
                tableRows.forEach(row => row.classList.add('mobile-stack'));
            } else {
                tableRows.forEach(row => row.classList.remove('mobile-stack'));
            }
        }

        // Run on load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    </script>
</body>

</html>

<?php $conn->close(); ?>