<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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

// Get user details
$stmt = $conn->prepare("SELECT sp.*, e.first_name, e.last_name 
                       FROM system_profiles sp 
                       JOIN employees e ON sp.employee_id = e.id 
                       WHERE sp.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .welcome-card p {
            color: #7f8c8d;
            font-size: 18px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #3498db;
        }

        .feature-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
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

        .user-info {
            text-align: center;
            margin-bottom: 20px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Staff Dashboard</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
            <p>Staff Member Access Portal</p>
            <div class="user-info">
                <p>Username: <?php echo htmlspecialchars($user['username']); ?> |
                    Access Level: <?php echo ucfirst(htmlspecialchars($user['access_level'])); ?></p>
            </div>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>View My Profile</h3>
                <p>Access and update your personal information</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Schedule</h3>
                <p>View your work schedule and request time off</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💼</div>
                <h3>Tasks</h3>
                <p>View assigned tasks and update progress</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3>Reports</h3>
                <p>Generate and view work reports</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Payroll</h3>
                <p>View payment history and payslips</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>Resources</h3>
                <p>Access company resources and documents</p>
            </div>
        </div>
    </div>
</body>

</html>