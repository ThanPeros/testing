<?php
// Start session and database connection
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = new mysqli("localhost", "root", "", "capstone");

// Update session status to cancelled
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE onboarding_schedules SET status='cancelled' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../sched/sched--onboard.php");
exit();
