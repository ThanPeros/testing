<?php
// Start output buffering at the VERY top
ob_start();

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

// Handle deactivation
if (isset($_GET['deactivate'])) {
    $profile_id = intval($_GET['deactivate']);
    $stmt = $conn->prepare("UPDATE system_profiles SET status='inactive' WHERE id=?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $stmt->close();

    // Check if headers were already sent
    if (!headers_sent()) {
        header("Location: create_system_profile.php");
        exit();
    } else {
        // Fallback: JavaScript redirect
        echo '<script>window.location.href = "create_system_profile.php";</script>';
        exit();
    }
}

// Handle reactivation
if (isset($_GET['reactivate'])) {
    $profile_id = intval($_GET['reactivate']);
    $stmt = $conn->prepare("UPDATE system_profiles SET status='active' WHERE id=?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $stmt->close();

    // Check if headers were already sent
    if (!headers_sent()) {
        header("Location: create_system_profile.php");
        exit();
    } else {
        // Fallback: JavaScript redirect
        echo '<script>window.location.href = "create_system_profile.php";</script>';
        exit();
    }
}

// Initialize variables
$success = false;
$error = '';
$employees = [];
$generated_password = '';
$existing_credentials = [];

// Fetch approved employees who don't have system access yet
$sql = "SELECT e.id, e.first_name, e.last_name, e.email 
        FROM employees e
        LEFT JOIN system_profiles sp ON e.id = sp.employee_id
        WHERE e.status = 'approved' AND sp.id IS NULL";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch existing credentials with profile IDs
$credential_sql = "SELECT sp.id, e.first_name, e.last_name, sp.username, sp.access_level, sp.status 
                   FROM system_profiles sp
                   JOIN employees e ON sp.employee_id = e.id
                   ORDER BY e.last_name, e.first_name";
$credential_result = $conn->query($credential_sql);
if ($credential_result->num_rows > 0) {
    $existing_credentials = $credential_result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employee_id'])) {
    $employee_id = intval($_POST['employee_id']);
    $access_level = $_POST['access_level'];

    // Generate random username and password
    $username = 'user' . $employee_id . substr(md5(uniqid()), 0, 4);
    $password = bin2hex(random_bytes(4)); // 8-character random password
    $generated_password = $password; // Store for display
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Get employee email
    $email = '';
    foreach ($employees as $emp) {
        if ($emp['id'] == $employee_id) {
            $email = $emp['email'];
            break;
        }
    }

    // Insert into system_profiles table
    $stmt = $conn->prepare("INSERT INTO system_profiles (employee_id, username, password_hash, email, access_level) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $employee_id, $username, $password_hash, $email, $access_level);

    if ($stmt->execute()) {
        // Update employee record
        $update_stmt = $conn->prepare("UPDATE employees SET has_system_access = TRUE WHERE id = ?");
        $update_stmt->bind_param("i", $employee_id);
        $update_stmt->execute();
        $update_stmt->close();

        $success = true;

        // Refresh the employee list
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $employees = $result->fetch_all(MYSQLI_ASSOC);
        }

        // Refresh credentials list
        $credential_result = $conn->query($credential_sql);
        if ($credential_result->num_rows > 0) {
            $existing_credentials = $credential_result->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $error = "Error creating system profile: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create System Access Profile</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        .credentials {
            background-color: #e2e3e5;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-family: monospace;
            border-left: 4px solid #6c757d;
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
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .access-basic {
            color: #6c757d;
        }

        .access-manager {
            color: #007bff;
            font-weight: bold;
        }

        .access-admin {
            color: #dc3545;
            font-weight: bold;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .action-cell {
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
    </style>
</head>

<body>
    <h1>Employee System Access Management</h1>

    <?php if ($success): ?>
        <div class="success">
            <strong>Success!</strong> System profile created successfully!
            <div class="credentials">
                <strong>Generated Credentials:</strong><br>
                Username: <?php echo htmlspecialchars($username); ?><br>
                Password: <?php echo htmlspecialchars($generated_password); ?>
            </div>
            <p>Please provide these credentials to the employee and instruct them to change their password immediately.</p>
        </div>
    <?php elseif ($error): ?>
        <div class="error">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Create New System Access</h2>
        <?php if (count($employees) > 0): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="employee_id">Select Approved Employee:</label>
                    <select name="employee_id" id="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                (<?php echo htmlspecialchars($employee['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="access_level">Access Level:</label>
                    <select name="access_level" id="access_level" required>
                        <option value="basic">Basic Access</option>
                        <option value="manager">Manager Access</option>
                        <option value="admin">Admin Access</option>
                    </select>
                </div>

                <button type="submit">Create System Profile</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <p>No approved employees without system access found.</p>
                <p>All approved employees already have system access profiles or there are no approved employees.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Existing System Credentials</h2>
        <?php if (count($existing_credentials) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Username</th>
                        <th>Access Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existing_credentials as $cred): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cred['first_name'] . ' ' . $cred['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($cred['username']); ?></td>
                            <td class="access-<?php echo htmlspecialchars($cred['access_level']); ?>">
                                <?php echo ucfirst(htmlspecialchars($cred['access_level'])); ?>
                            </td>
                            <td class="status-<?php echo htmlspecialchars($cred['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($cred['status'])); ?>
                            </td>
                            <td class="action-cell">
                                <?php if ($cred['status'] == 'active'): ?>
                                    <a href="?deactivate=<?php echo $cred['id']; ?>" class="btn btn-danger">Deactivate</a>
                                <?php else: ?>
                                    <a href="?reactivate=<?php echo $cred['id']; ?>" class="btn btn-success">Reactivate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No system credentials have been created yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>

<?php
$conn->close();
// End output buffering and flush all output
ob_end_flush();
?>