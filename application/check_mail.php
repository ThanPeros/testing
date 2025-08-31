<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "application";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

if (!isset($_GET['email'])) {
    die(json_encode(['error' => 'Email parameter missing']));
}

$email = $_GET['email'];

// Use prepared statement to prevent SQL injection
$sql = "SELECT id FROM employees WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);

$stmt->close();
$conn->close();
