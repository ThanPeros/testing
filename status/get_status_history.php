<?php
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

if (isset($_GET['employee_id'])) {
    $employee_id = intval($_GET['employee_id']);

    // Get status history for the employee
    $stmt = $conn->prepare("SELECT status, changed_at, changed_by FROM status WHERE employee_id = ? ORDER BY changed_at DESC");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<table class="history-table">';
        echo '<tr><th>Status</th><th>Changed By</th><th>Date & Time</th></tr>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td><span class="status-badge status-' . htmlspecialchars($row['status']) . '">' . ucfirst(htmlspecialchars($row['status'])) . '</span></td>';
            echo '<td>' . htmlspecialchars($row['changed_by']) . '</td>';
            echo '<td>' . htmlspecialchars($row['changed_at']) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } else {
        echo '<p>No status history found for this employee.</p>';
    }
} else {
    echo '<p>Invalid request.</p>';
}

$conn->close();
