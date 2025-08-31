    <?php
    ob_start();

    include  '../sidebar.php';


    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "systems";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle approval
    if (isset($_GET['approve'])) {
        $id = intval($_GET['approve']);
        $sql = "UPDATE employees SET status='approved' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: hr_review.php");
        exit();
    }

    // Get all employees
    $sql = "SELECT * FROM employees ORDER BY created_at DESC";
    $result = $conn->query($sql);
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HR Review - Employee Applications</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            h1 {
                color: #333;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
            }

            tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            .status-pending {
                color: #ffc107;
                font-weight: bold;
            }

            .status-approved {
                color: #28a745;
                font-weight: bold;
            }

            .action-btn {
                padding: 6px 12px;
                margin: 0 5px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                color: white;
            }

            .view-btn {
                background-color: #17a2b8;
            }

            .download-btn {
                background-color: #6c757d;
            }

            .approve-btn {
                background-color: #28a745;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>Employee Applications - HR Review</h1>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . htmlspecialchars($row['last_name'])) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="status-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </td>
                                <td>
                                    <a href="view_employee.php?id=<?= $row['id'] ?>" class="action-btn view-btn">View</a>
                                    <?php if (!empty($row['document_path'])): ?>
                                        <a href="<?= $row['document_path'] ?>" download class="action-btn download-btn">Download CV</a>
                                    <?php endif; ?>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="hr_review.php?approve=<?= $row['id'] ?>" class="action-btn approve-btn">Approve</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No employee applications found.</p>
            <?php endif; ?>
        </div>
    </body>

    </html>

    <?php $conn->close(); ?>