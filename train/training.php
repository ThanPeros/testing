<?php
require '../sidebar.php';
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create employees table if it doesn't exist (for reference)
$createEmployeesTable = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createEmployeesTable)) {
    die("Error creating employees table: " . $conn->error);
}

// Create training_records table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS training_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    training_name VARCHAR(100) NOT NULL,
    training_date DATE NOT NULL,
    completion_date DATE NOT NULL,
    trainer VARCHAR(100) NOT NULL,
    score DECIMAL(5,2),
    status ENUM('completed', 'in-progress', 'failed') DEFAULT 'completed',
    certificate_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating table: " . $conn->error);
}

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $training_name = $_POST['training_name'];
    $training_date = $_POST['training_date'];
    $completion_date = $_POST['completion_date'];
    $trainer = $_POST['trainer'];
    $score = !empty($_POST['score']) ? floatval($_POST['score']) : null;
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    // Handle file upload
    $certificate_path = '';
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/certificates/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["certificate"]["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . uniqid() . '.' . $file_ext;

        if (move_uploaded_file($_FILES["certificate"]["tmp_name"], $target_file)) {
            $certificate_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO training_records 
                          (employee_id, training_name, training_date, completion_date, trainer, score, status, certificate_path, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdsss", $employee_id, $training_name, $training_date, $completion_date, $trainer, $score, $status, $certificate_path, $notes);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "Error adding training record: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all active employees
$employees = [];
$employeesSQL = "SELECT id, first_name, last_name, department 
                FROM employees 
                ORDER BY first_name, last_name";
$result = $conn->query($employeesSQL);
if ($result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch existing training records with employee details
$trainingRecords = [];
$recordsSQL = "SELECT tr.*, e.first_name, e.last_name, e.department
              FROM training_records tr
              JOIN employees e ON tr.employee_id = e.id
              ORDER BY tr.completion_date DESC";
$result = $conn->query($recordsSQL);
if ($result->num_rows > 0) {
    $trainingRecords = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Records Management</title>
    <!-- Add Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .card h2 {
            color: #3498db;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select,
        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            min-height: 100px;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-completed {
            color: #28a745;
            font-weight: bold;
        }

        .status-in-progress {
            color: #ffc107;
            font-weight: bold;
        }

        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
            border: none;
        }

        .btn-download {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .file-input-label {
            display: block;
            padding: 12px;
            background: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .file-input-label:hover {
            background: #e9ecef;
        }

        .file-name {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Select2 custom styles */
        .select2-container--default .select2-selection--single {
            height: 46px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
        }

        .select2-container .select2-selection--single {
            box-sizing: border-box;
            cursor: pointer;
            display: block;
        }
    </style>
</head>

<body>
    <h1>Training Records Management</h1>

    <?php if ($success): ?>
        <div class="success">
            Training record added successfully!
        </div>
    <?php elseif ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Add New Training Record</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="employee_id">Employee:</label>
                <select name="employee_id" id="employee_id" class="employee-select" required>
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee['id']; ?>">
                            <?php echo htmlspecialchars($employee['first_name']) . ' ' . $employee['last_name'] . ' (' . $employee['department'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="training_name">Training Name:</label>
                <input type="text" name="training_name" id="training_name" required>
            </div>

            <div class="form-group">
                <label for="training_date">Training Date:</label>
                <input type="date" name="training_date" id="training_date" required>
            </div>

            <div class="form-group">
                <label for="completion_date">Completion Date:</label>
                <input type="date" name="completion_date" id="completion_date" required>
            </div>

            <div class="form-group">
                <label for="trainer">Trainer/Facilitator:</label>
                <input type="text" name="trainer" id="trainer" required>
            </div>

            <div class="form-group">
                <label for="score">Score (if applicable):</label>
                <input type="number" name="score" id="score" min="0" max="100" step="0.01">
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status" required>
                    <option value="completed">Completed</option>
                    <option value="in-progress">In Progress</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Certificate (if available):</label>
                <label for="certificate" class="file-input-label">
                    Click to upload certificate
                    <input type="file" name="certificate" id="certificate" style="display: none;">
                </label>
                <div class="file-name" id="file-name">No file selected</div>
            </div>

            <div class="form-group">
                <label for="notes">Notes:</label>
                <textarea name="notes" id="notes"></textarea>
            </div>

            <button type="submit">Save Training Record</button>
        </form>
    </div>

    <div class="card">
        <h2>Training Records</h2>
        <?php if (count($trainingRecords) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Training</th>
                        <th>Date</th>
                        <th>Trainer</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trainingRecords as $record): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($record['department']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['training_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['training_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['trainer']); ?></td>
                            <td><?php echo $record['score'] !== null ? htmlspecialchars($record['score']) : 'N/A'; ?></td>
                            <td class="status-<?php echo str_replace('-', '', htmlspecialchars($record['status'])); ?>">
                                <?php echo ucfirst(htmlspecialchars($record['status'])); ?>
                            </td>
                            <td>
                                <?php if (!empty($record['certificate_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($record['certificate_path']); ?>" target="_blank" class="btn btn-download">View Certificate</a>
                                <?php else: ?>
                                    <span>No certificate</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No training records found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 for employee dropdown
        $(document).ready(function() {
            $('.employee-select').select2({
                placeholder: "Select an employee",
                allowClear: true,
                width: '100%'
            });

            // Show selected file name
            document.getElementById('certificate').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
                document.getElementById('file-name').textContent = fileName;
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>