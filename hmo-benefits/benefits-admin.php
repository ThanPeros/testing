<?php
session_start();
include '../sidebar.php';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT sp.*, e.first_name, e.last_name, e.department 
                FROM system_profiles sp 
                JOIN employees e ON sp.employee_id = e.id 
                WHERE sp.email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['employee_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['access_level'] = $user['access_level'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['department'] = $user['department'];

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
    }

    if (isset($_POST['request_benefit'])) {
        $employee_id = $_SESSION['user_id'];
        $benefit_type = $_POST['benefit_type'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $date_used = $_POST['date_used'];

        $sql = "INSERT INTO benefits_usage (employee_id, benefit_type, description, amount, date_used, status) 
                VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issds", $employee_id, $benefit_type, $description, $amount, $date_used);
        $stmt->execute();

        $success = "Benefit request submitted successfully!";
    }

    if (isset($_POST['approve_benefit'])) {
        $benefit_id = $_POST['benefit_id'];
        $status = $_POST['status'];

        $sql = "UPDATE benefits_usage SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $benefit_id);
        $stmt->execute();

        $success = "Benefit request updated!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMO & Benefits Management System</title>
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
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
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
            background-color: #f8f9fa;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .stat-title {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            border-bottom: 3px solid #3498db;
            color: #3498db;
            font-weight: 500;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <header>
        <div class="container header-content">
            <div class="logo">HMO & Benefits Management System</div>
            <?php if ($logged_in): ?>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['access_level']; ?>)</span>
                    <a href="?logout=true" class="btn btn-danger">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$logged_in): ?>
            <!-- Login Form -->
            <div class="card">
                <div class="card-header">Login</div>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Dashboard -->
            <div class="tabs">
                <div class="tab active" onclick="switchTab('dashboard')">Dashboard</div>
                <div class="tab" onclick="switchTab('benefits')">Benefits</div>
                <?php if ($_SESSION['access_level'] === 'admin' || $_SESSION['access_level'] === 'manager'): ?>
                    <div class="tab" onclick="switchTab('approvals')">Approvals</div>
                    <div class="tab" onclick="switchTab('reports')">Reports</div>
                <?php endif; ?>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="tab-content active">
                <h2>Employee Dashboard</h2>
                <div class="dashboard">
                    <div class="stat-card">
                        <div class="stat-title">Pending Requests</div>
                        <div class="stat-number">
                            <?php
                            $employee_id = $_SESSION['user_id'];
                            $sql = "SELECT COUNT(*) as count FROM benefits_usage WHERE employee_id = ? AND status = 'Pending'";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employee_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-title">Approved Requests</div>
                        <div class="stat-number">
                            <?php
                            $sql = "SELECT COUNT(*) as count FROM benefits_usage WHERE employee_id = ? AND status = 'Approved'";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employee_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-title">Total Benefits Used</div>
                        <div class="stat-number">
                            <?php
                            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM benefits_usage WHERE employee_id = ? AND status = 'Approved'";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employee_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            echo "₱" . number_format($row['total'], 2);
                            ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Recent Benefit Requests</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM benefits_usage WHERE employee_id = ? ORDER BY reg_date DESC LIMIT 5";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employee_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['date_used'] . "</td>";
                                echo "<td>" . $row['benefit_type'] . "</td>";
                                echo "<td>" . $row['description'] . "</td>";
                                echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                                echo "<td><span class='status-" . strtolower($row['status']) . "'>" . $row['status'] . "</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Benefits Tab -->
            <div id="benefits-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">Request New Benefit</div>
                    <form method="POST">
                        <div class="form-group">
                            <label for="benefit_type">Benefit Type</label>
                            <select id="benefit_type" name="benefit_type" required>
                                <option value="">Select Benefit Type</option>
                                <option value="Consultation">Medical Consultation</option>
                                <option value="Hospitalization">Hospitalization</option>
                                <option value="Reimbursement">Reimbursement</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount (₱)</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="date_used">Date Used</label>
                            <input type="date" id="date_used" name="date_used" required>
                        </div>
                        <button type="submit" name="request_benefit" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">My Benefit Requests</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date Submitted</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Date Used</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM benefits_usage WHERE employee_id = ? ORDER BY reg_date DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employee_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['reg_date'] . "</td>";
                                    echo "<td>" . $row['benefit_type'] . "</td>";
                                    echo "<td>" . $row['description'] . "</td>";
                                    echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                                    echo "<td>" . $row['date_used'] . "</td>";
                                    echo "<td><span class='status-" . strtolower($row['status']) . "'>" . $row['status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No benefit requests found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approvals Tab (for HR/Managers) -->
            <?php if ($_SESSION['access_level'] === 'admin' || $_SESSION['access_level'] === 'manager'): ?>
                <div id="approvals-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">Pending Benefit Approvals</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date Submitted</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Date Used</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT bu.*, e.first_name, e.last_name 
                                    FROM benefits_usage bu 
                                    JOIN employees e ON bu.employee_id = e.id 
                                    WHERE bu.status = 'Pending' 
                                    ORDER BY bu.reg_date DESC";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
                                        echo "<td>" . $row['reg_date'] . "</td>";
                                        echo "<td>" . $row['benefit_type'] . "</td>";
                                        echo "<td>" . $row['description'] . "</td>";
                                        echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                                        echo "<td>" . $row['date_used'] . "</td>";
                                        echo "<td>
                                        <form method='POST' style='display:inline;'>
                                            <input type='hidden' name='benefit_id' value='" . $row['id'] . "'>
                                            <input type='hidden' name='status' value='Approved'>
                                            <button type='submit' name='approve_benefit' class='btn btn-success'>Approve</button>
                                        </form>
                                        <form method='POST' style='display:inline;'>
                                            <input type='hidden' name='benefit_id' value='" . $row['id'] . "'>
                                            <input type='hidden' name='status' value='Rejected'>
                                            <button type='submit' name='approve_benefit' class='btn btn-danger'>Reject</button>
                                        </form>
                                    </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>No pending approvals.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reports Tab (for HR/Managers) -->
                <div id="reports-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">Benefits Utilization Report</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Requests</th>
                                    <th>Approved Requests</th>
                                    <th>Total Amount</th>
                                    <th>Average per Employee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT e.department, 
                                    COUNT(bu.id) as total_requests,
                                    SUM(CASE WHEN bu.status = 'Approved' THEN 1 ELSE 0 END) as approved_requests,
                                    COALESCE(SUM(CASE WHEN bu.status = 'Approved' THEN bu.amount ELSE 0 END), 0) as total_amount,
                                    COUNT(DISTINCT e.id) as employee_count
                                    FROM employees e
                                    LEFT JOIN benefits_usage bu ON e.id = bu.employee_id
                                    GROUP BY e.department
                                    ORDER BY total_amount DESC";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $avg_per_employee = $row['employee_count'] > 0 ? $row['total_amount'] / $row['employee_count'] : 0;
                                        echo "<tr>";
                                        echo "<td>" . $row['department'] . "</td>";
                                        echo "<td>" . $row['total_requests'] . "</td>";
                                        echo "<td>" . $row['approved_requests'] . "</td>";
                                        echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
                                        echo "<td>₱" . number_format($avg_per_employee, 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No data available.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');

            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            event.currentTarget.classList.add('active');
        }

        // Set current date as default for date_used field
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_used').value = today;
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>