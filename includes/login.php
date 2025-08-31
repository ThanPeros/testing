<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$login_error = '';
$login_success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username, password_hash, access_level, status FROM system_profiles WHERE username = ?");
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if account is active
        if ($user['status'] !== 'active') {
            $login_error = "Your account is deactivated. Please contact administrator.";
        }
        // Verify password
        elseif (password_verify($input_password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['access_level'] = $user['access_level'];
            $_SESSION['logged_in'] = true;

            $login_success = "Login successful! Redirecting...";

            // Redirect based on access level
            switch ($user['access_level']) {
                case 'hr':
                    header("refresh:1; url=http://localhost/system/assign/assign.php");
                    break;
                case 'admin':
                    header("refresh:1; url=admin_dashboard.php");
                    break;
                case 'manager':
                    header("refresh:1; url=manager_dashboard.php");
                    break;
                case 'basic':
                default:
                    header("refresh:1; url=staff.php");
                    break;
            }
            exit();
        } else {
            $login_error = "Invalid password. Please try again.";
        }
    } else {
        $login_error = "Username not found. Please check your credentials.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        .login-header {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 25px 20px;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.8;
        }

        .login-form {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn-login {
            background: #3498db;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #2980b9;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #7f8c8d;
        }

        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 12px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .access-info {
            margin-top: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            font-size: 14px;
        }

        .access-info h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 16px;
        }

        .access-item {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
        }

        .access-item:last-child {
            border-bottom: none;
        }

        .access-hr {
            color: #dc3545;
            font-weight: bold;
        }

        .access-admin {
            color: #6f42c1;
            font-weight: bold;
        }

        .access-manager {
            color: #007bff;
            font-weight: bold;
        }

        .access-basic {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>System Access Portal</h1>
            <p>Enter your credentials to access the system</p>
        </div>

        <div class="login-form">
            <?php if ($login_error): ?>
                <div class="alert alert-error">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <?php if ($login_success): ?>
                <div class="alert alert-success">
                    <?php echo $login_success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" id="togglePassword" onclick="togglePasswordVisibility()">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="access-info">
                <h3>Access Level Redirections</h3>
                <div class="access-item">
                    <span class="access-hr">HR Accounts:</span> Redirect to localhost/system/assign/assign.php
                </div>
                <div class="access-item">
                    <span class="access-admin">Admin Accounts:</span> Redirect to admin_dashboard.php
                </div>
                <div class="access-item">
                    <span class="access-manager">Manager Accounts:</span> Redirect to manager_dashboard.php
                </div>
                <div class="access-item">
                    <span class="access-basic">Basic Accounts:</span> Redirect to staff.php
                </div>
            </div>
        </div>

        <div class="login-footer">
            <p>Need an account? <a href="create_system_profile.php">Create one here</a></p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }
    </script>
</body>

</html>