<?php
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM employees WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - <?= htmlspecialchars($employee['first_name'] ?? '') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h2 {
            color: #444;
            margin-bottom: 15px;
        }

        .detail-row {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($employee): ?>
            <h1><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h1>

            <div class="detail-section">
                <h2>Basic Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <?= htmlspecialchars($employee['email']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <?= htmlspecialchars($employee['phone']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Position:</span>
                    <?= htmlspecialchars($employee['position']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hire Date:</span>
                    <?= date('M d, Y', strtotime($employee['hire_date'])) ?>
                </div>
            </div>

            <div class="detail-section">
                <h2>Personal Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <?= nl2br(htmlspecialchars($employee['address'])) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <?= date('M d, Y', strtotime($employee['date_of_birth'])) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <?= htmlspecialchars($employee['gender']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Marital Status:</span>
                    <?= htmlspecialchars($employee['marital_status']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Emergency Contact:</span>
                    <?= htmlspecialchars($employee['emergency_contact_name']) ?> (<?= htmlspecialchars($employee['emergency_contact_phone']) ?>)
                </div>
            </div>

            <?php if (!empty($employee['document_path'])): ?>
                <div class="detail-section">
                    <h2>Documents</h2>
                    <div class="detail-row">
                        <span class="detail-label">Resume/CV:</span>
                        <a href="<?= $employee['document_path'] ?>" download>Download</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="detail-section">
                <h2>Application Status</h2>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-<?= $employee['status'] ?>">
                        <?= ucfirst($employee['status']) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted On:</span>
                    <?= date('M d, Y H:i', strtotime($employee['created_at'])) ?>
                </div>
            </div>

            <a href="hr_review.php" class="back-btn">Back to Applications</a>

        <?php else: ?>
            <p>Employee not found.</p>
            <a href="hr_review.php" class="back-btn">Back to Applications</a>
        <?php endif; ?>
    </div>
</body>

</html>

<?php $conn->close(); ?>