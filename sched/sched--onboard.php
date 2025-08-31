<?php
// Start session if not already started
session_start();

// Handle action requests FIRST before any output
if (isset($_GET['action']) && isset($_GET['id'])) {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "systems";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Function to complete a session
    function completeSession($session_id, $conn)
    {
        $stmt = $conn->prepare("UPDATE onboarding_schedules SET status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $session_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Session marked as completed successfully!";
        } else {
            $_SESSION['error'] = "Error completing session: " . $stmt->error;
        }
        $stmt->close();
    }

    // Function to cancel a session
    function cancelSession($session_id, $conn)
    {
        $stmt = $conn->prepare("UPDATE onboarding_schedules SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $session_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Session cancelled successfully!";
        } else {
            $_SESSION['error'] = "Error cancelling session: " . $stmt->error;
        }
        $stmt->close();
    }

    $action = $_GET['action'];
    $session_id = intval($_GET['id']);

    switch ($action) {
        case 'complete':
            completeSession($session_id, $conn);
            break;
        case 'cancel':
            cancelSession($session_id, $conn);
            break;
    }

    $conn->close();

    // Redirect back to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Database connection for main page
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create onboarding_schedules table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS onboarding_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    session_type ENUM('orientation', 'onboarding') NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    location VARCHAR(100) NOT NULL,
    facilitator VARCHAR(100) NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
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

// Check for session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $session_type = $_POST['session_type'];
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];
    $location = $_POST['location'];
    $facilitator = $_POST['facilitator'];
    $notes = $_POST['notes'];

    // Proceed with normal insertion
    $stmt = $conn->prepare("INSERT INTO onboarding_schedules 
                          (employee_id, session_type, session_date, session_time, location, facilitator, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $employee_id, $session_type, $session_date, $session_time, $location, $facilitator, $notes);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Session scheduled successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Error scheduling session: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch employees who need onboarding (approved but not completed onboarding)
$employees = [];
$employeesSQL = "SELECT e.id, e.first_name, e.last_name 
                FROM employees e
                LEFT JOIN onboarding_schedules os ON e.id = os.employee_id AND os.session_type = 'onboarding' AND os.status = 'completed'
                WHERE e.status = 'approved' AND os.id IS NULL
                ORDER BY e.first_name";
$result = $conn->query($employeesSQL);
if ($result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch scheduled sessions
$scheduledSessions = [];
$sessionsSQL = "SELECT os.*, e.first_name, e.last_name 
               FROM onboarding_schedules os
               JOIN employees e ON os.employee_id = e.id
               ORDER BY os.session_date, os.session_time";
$result = $conn->query($sessionsSQL);
if ($result->num_rows > 0) {
    $scheduledSessions = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

// Now include the sidebar after all processing that might require redirects
require_once '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Onboarding & Orientation</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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
            overflow-x: auto;
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
            min-width: 800px;
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

        .status-scheduled {
            color: #17a2b8;
            font-weight: bold;
        }

        .status-completed {
            color: #28a745;
            font-weight: bold;
        }

        .status-cancelled {
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
            margin: 2px;
            text-align: center;
            white-space: nowrap;
        }

        .btn-complete {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-complete:hover {
            background-color: #218838;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .btn-reschedule {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-reschedule:hover {
            background-color: #e0a800;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background-color: #138496;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        /* Responsive styles */
        @media (min-width: 768px) {
            .form-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 767px) {
            body {
                padding: 10px;
            }

            .card {
                padding: 15px;
            }

            th,
            td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .btn {
                padding: 5px 8px;
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
            }

            .card h2 {
                font-size: 20px;
            }

            button {
                width: 100%;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Schedule Onboarding & Orientation</h1>

        <?php if ($success): ?>
            <div class="success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php elseif ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Schedule New Session</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="employee_id">Employee:</label>
                        <select name="employee_id" id="employee_id" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="session_type">Session Type:</label>
                        <select name="session_type" id="session_type" required>
                            <option value="orientation">Orientation</option>
                            <option value="onboarding">Onboarding</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="session_date">Date:</label>
                        <input type="date" name="session_date" id="session_date" required>
                    </div>

                    <div class="form-group">
                        <label for="session_time">Time:</label>
                        <input type="time" name="session_time" id="session_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" name="location" id="location" required>
                    </div>

                    <div class="form-group">
                        <label for="facilitator">Facilitator:</label>
                        <input type="text" name="facilitator" id="facilitator" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes"></textarea>
                </div>

                <button type="submit">Schedule Session</button>
            </form>
        </div>

        <div class="card">
            <h2>Scheduled Sessions</h2>
            <?php if (count($scheduledSessions) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Session Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Facilitator</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduledSessions as $session): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($session['session_type'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($session['session_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($session['session_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($session['location']); ?></td>
                                    <td><?php echo htmlspecialchars($session['facilitator']); ?></td>
                                    <td class="status-<?php echo htmlspecialchars($session['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($session['status'] == 'scheduled'): ?>
                                                <a href="?action=complete&id=<?php echo $session['id']; ?>" class="btn btn-complete" onclick="return confirm('Mark this session as completed?')">Complete</a>
                                                <a href="?action=cancel&id=<?php echo $session['id']; ?>" class="btn btn-cancel" onclick="return confirm('Cancel this session?')">Cancel</a>
                                            <?php elseif ($session['status'] == 'cancelled'): ?>
                                                <a href="?action=complete&id=<?php echo $session['id']; ?>" class="btn btn-complete" onclick="return confirm('Mark this session as completed?')">Complete</a>
                                            <?php elseif ($session['status'] == 'completed'): ?>
                                                <span class="btn btn-view">Completed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No sessions have been scheduled yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmAction(action, sessionId) {
            if (confirm(`Are you sure you want to ${action} this session?`)) {
                window.location.href = `?action=${action}&id=${sessionId}`;
            }
        }
    </script>
</body>

</html>