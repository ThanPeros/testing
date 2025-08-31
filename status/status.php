<?php
// Start output buffering at the very beginning
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Function to update employee status in the status table
function updateEmployeeStatus($conn, $employee_id, $status)
{
    $stmt = $conn->prepare("INSERT INTO status (employee_id, status, changed_by) VALUES (?, ?, ?)");
    $changed_by = $_SESSION['username'] ?? 'Admin'; // Get the current user from session or use 'Admin' as default
    $stmt->bind_param("iss", $employee_id, $status, $changed_by);
    return $stmt->execute();
}

// Function to get current status of an employee
function getCurrentStatus($conn, $employee_id)
{
    $stmt = $conn->prepare("SELECT status FROM status WHERE employee_id = ? ORDER BY changed_at DESC LIMIT 1");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['status'];
    }
    return 'unknown';
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $employee_id = intval($_POST['employee_id']);
    $new_status = $_POST['new_status'];

    if (updateEmployeeStatus($conn, $employee_id, $new_status)) {
        $_SESSION['success'] = "Employee status updated successfully!";
        // Clear output buffer before redirect
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error'] = "Error updating employee status: " . $conn->error;
        // Clear output buffer before redirect
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all employees with their current status
$employeesSQL = "SELECT e.id, e.first_name, e.last_name, e.department, 
                (SELECT s.status FROM status s WHERE s.employee_id = e.id ORDER BY s.changed_at DESC LIMIT 1) as status
                FROM employees e 
                ORDER BY e.first_name, e.last_name";
$result = $conn->query($employeesSQL);
$employees = [];
if ($result->num_rows > 0) {
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}

// Check for messages in session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
// Clear session messages after retrieving them
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Status Management</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            position: sticky;
            top: 0;
        }

        .status-toggle-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-unknown {
            background-color: #fff3cd;
            color: #856404;
        }

        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .view-history {
            margin-left: 10px;
            color: #3498db;
            cursor: pointer;
        }

        /* Modal styles */
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
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

        .history-table {
            width: 100%;
            margin-top: 15px;
        }

        .history-table th {
            background-color: #2c3e50;
        }

        /* Filter section */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #777;
        }
    </style>
</head>

<body>
    <h1>Employee Status Management</h1>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php elseif (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Employee List</h2>

        <!-- Filter Section -->
        <div class="filters">
            <div class="filter-group">
                <label for="statusFilter">Filter by Status</label>
                <select id="statusFilter">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="departmentFilter">Filter by Department</label>
                <select id="departmentFilter">
                    <option value="all">All Departments</option>
                    <?php
                    // Get unique departments
                    $deptSQL = "SELECT DISTINCT department FROM employees ORDER BY department";
                    $deptResult = $conn->query($deptSQL);
                    if ($deptResult->num_rows > 0) {
                        while ($dept = $deptResult->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($dept['department']) . '">' . htmlspecialchars($dept['department']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="searchInput">Search by Name</label>
                <input type="text" id="searchInput" placeholder="Enter employee name">
            </div>
        </div>

        <table id="employeesTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Current Status</th>
                    <th>Update Status</th>
                    <th>History</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($employees) > 0): ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['department']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($employee['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($employee['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="status-toggle-form">
                                    <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                    <select name="new_status" class="status-select">
                                        <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn">Update</button>
                                </form>
                            </td>
                            <td>
                                <span class="view-history" data-employee-id="<?php echo $employee['id']; ?>" data-employee-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                    <i class="fas fa-history"></i> View History
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">No employees found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for status history -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Status History</h2>
            <div id="historyContent"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.status-select').select2({
                minimumResultsForSearch: Infinity // Disable search for this simple dropdown
            });

            // Filter functionality
            $('#statusFilter, #departmentFilter, #searchInput').on('change keyup', function() {
                var statusFilter = $('#statusFilter').val();
                var departmentFilter = $('#departmentFilter').val();
                var searchText = $('#searchInput').val().toLowerCase();

                $('#employeesTable tbody tr').each(function() {
                    var status = $(this).find('.status-badge').text().toLowerCase();
                    var department = $(this).find('td:eq(1)').text().toLowerCase();
                    var name = $(this).find('td:eq(0)').text().toLowerCase();

                    var statusMatch = (statusFilter === 'all' || status === statusFilter);
                    var departmentMatch = (departmentFilter === 'all' || department === departmentFilter.toLowerCase());
                    var searchMatch = (searchText === '' || name.includes(searchText));

                    if (statusMatch && departmentMatch && searchMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Modal functionality
            var modal = document.getElementById("historyModal");
            var span = document.getElementsByClassName("close")[0];

            // Open modal when history link is clicked
            $('.view-history').click(function() {
                var employeeId = $(this).data('employee-id');
                var employeeName = $(this).data('employee-name');

                $('#modalTitle').text('Status History for ' + employeeName);

                // Show loading message
                $('#historyContent').html('<p>Loading history...</p>');
                modal.style.display = "block";

                // AJAX request to get status history
                $.ajax({
                    url: 'get_status_history.php',
                    method: 'GET',
                    data: {
                        employee_id: employeeId
                    },
                    success: function(response) {
                        $('#historyContent').html(response);
                    },
                    error: function() {
                        $('#historyContent').html('<p>Error loading history.</p>');
                    }
                });
            });

            // Close modal when X is clicked
            span.onclick = function() {
                modal.style.display = "none";
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        });
    </script>
</body>

</html>

<?php
// End output buffering and flush the output
ob_end_flush();
$conn->close();
?>