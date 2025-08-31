<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['access_level'] !== 'admin') {
    header("Location: login.php");
    exit();
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

// Get admin details
$stmt = $conn->prepare("SELECT sp.*, e.first_name, e.last_name 
                       FROM system_profiles sp 
                       JOIN employees e ON sp.employee_id = e.id 
                       WHERE sp.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM system_profiles")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM system_profiles WHERE status='active'")->fetch_assoc()['count'];
$inactive_users = $conn->query("SELECT COUNT(*) as count FROM system_profiles WHERE status='inactive'")->fetch_assoc()['count'];
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];

// Get all system profiles with employee info
$profiles_query = "SELECT sp.*, e.first_name, e.last_name, e.email as emp_email, e.status as emp_status 
                   FROM system_profiles sp 
                   JOIN employees e ON sp.employee_id = e.id 
                   ORDER BY sp.created_at DESC";
$profiles_result = $conn->query($profiles_query);

// Handle user actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $profile_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'activate') {
        $stmt = $conn->prepare("UPDATE system_profiles SET status='active' WHERE id=?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
    } elseif ($action == 'deactivate') {
        $stmt = $conn->prepare("UPDATE system_profiles SET status='inactive' WHERE id=?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
    } elseif ($action == 'delete') {
        // First get employee_id to update has_system_access flag
        $stmt = $conn->prepare("SELECT employee_id FROM system_profiles WHERE id=?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();

        // Delete the profile
        $stmt = $conn->prepare("DELETE FROM system_profiles WHERE id=?");
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();

        // Update employee record
        if (isset($profile['employee_id'])) {
            $update_stmt = $conn->prepare("UPDATE employees SET has_system_access = FALSE WHERE id = ?");
            $update_stmt->bind_param("i", $profile['employee_id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }

    // Refresh the page to show updated data
    header("Location: admin_dashboard.php");
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            display: flex;
            align-items: center;
        }

        .admin-badge {
            background: #e74c3c;
            color: white;
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 15px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 16px;
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .section-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .section-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: #c0392b;
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

        .access-hr {
            color: #6f42c1;
            font-weight: bold;
        }

        .action-cell {
            white-space: nowrap;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .user-info {
            text-align: right;
            color: #ecf0f1;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Admin Dashboard <span class="admin-badge">ADMIN</span></h1>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
            <div>
                <a href="create_system_profile.php" class="btn btn-primary">Create User</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>System Administration</h2>
            <p>Manage users, view system statistics, and configure application settings.</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_users; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inactive_users; ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="create_system_profile.php" class="quick-action-btn">Create New User</a>
            <a href="manage_employees.php" class="quick-action-btn">Manage Employees</a>
            <a href="system_settings.php" class="quick-action-btn">System Settings</a>
            <a href="reports.php" class="quick-action-btn">Generate Reports</a>
        </div>

        <div class="dashboard-sections">
            <div class="section-card">
                <h3>User Management</h3>
                <p>Manage all system users, their access levels, and account status.</p>

                <?php if ($profiles_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Access Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($profile = $profiles_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($profile['username']); ?></td>
                                    <td class="access-<?php echo htmlspecialchars($profile['access_level']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($profile['access_level'])); ?>
                                    </td>
                                    <td class="status-<?php echo htmlspecialchars($profile['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($profile['status'])); ?>
                                    </td>
                                    <td class="action-cell">
                                        <?php if ($profile['status'] == 'active'): ?>
                                            <a href="?action=deactivate&id=<?php echo $profile['id']; ?>" class="btn btn-warning">Deactivate</a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $profile['id']; ?>" class="btn btn-success">Activate</a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $profile['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No system profiles found.</p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h3>Recent Activity</h3>
                <p>System logs and recent user activities.</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>User login</span>
                        <span style="color: #7f8c8d;">2 minutes ago</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>New user created</span>
                        <span style="color: #7f8c8d;">15 minutes ago</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Profile updated</span>
                        <span style="color: #7f8c8d;">1 hour ago</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>System backup</span>
                        <span style="color: #7f8c8d;">3 hours ago</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h3>System Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4>Server Status</h4>
                    <p>All systems operational</p>
                    <p>Uptime: 12 days, 4 hours, 32 minutes</p>
                </div>
                <div>
                    <h4>Database Status</h4>
                    <p>Connected successfully</p>
                    <p>Size: 45.2 MB</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>