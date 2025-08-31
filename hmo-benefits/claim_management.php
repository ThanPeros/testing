<?php
include '../sidebar.php';
// Database connection
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

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_claim'])) {
        // Claim submission handling
        $employee_id = $_POST['employee_id'];
        $claim_type = $_POST['claim_type'];
        $provider_id = $_POST['provider'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];

        // Get provider name from provider ID
        $provider_name = "";
        $provider_result = $conn->query("SELECT name FROM providers WHERE id = $provider_id");
        if ($provider_result && $provider_result->num_rows > 0) {
            $provider_row = $provider_result->fetch_assoc();
            $provider_name = $provider_row['name'];
        }

        // Basic validation
        $valid = true;
        if (empty($employee_id) || empty($claim_type) || empty($provider_id) || empty($amount)) {
            $valid = false;
            $error_message = "Please fill in all required fields.";
        }

        if ($valid) {
            // Handle file upload
            $document_path = "";
            if (!empty($_FILES["documents"]["name"])) {
                $target_dir = "uploads/claims/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $file_name = time() . "_" . basename($_FILES["documents"]["name"]);
                $target_file = $target_dir . $file_name;

                if (move_uploaded_file($_FILES["documents"]["tmp_name"], $target_file)) {
                    $document_path = $target_file;
                }
            }

            // Insert into database - using the correct column names from your database
            $stmt = $conn->prepare("INSERT INTO claims (employee_id, claim_type, provider_id, amount, description, document_path, status) VALUES (?, ?, ?, ?, ?, ?, 'submitted')");
            $stmt->bind_param("isidss", $employee_id, $claim_type, $provider_id, $amount, $description, $document_path);

            if ($stmt->execute()) {
                $success_message = "Claim submitted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['validate_claim'])) {
        // Claim validation handling
        $claim_id = $_POST['claim_id'];
        $action = $_POST['validate_claim']; // Changed from validation_action to validate_claim
        $reviewer_notes = $_POST['reviewer_notes'] ?? '';

        if ($action == 'approve') {
            $status = 'approved';
            $stmt = $conn->prepare("UPDATE claims SET status = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $reviewer_notes, $claim_id);
        } elseif ($action == 'reject') {
            $status = 'rejected';
            $stmt = $conn->prepare("UPDATE claims SET status = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $reviewer_notes, $claim_id);
        }

        if (isset($stmt) && $stmt->execute()) {
            $success_message = "Claim #$claim_id has been $status.";
        } else {
            $error_message = "Error updating claim: " . ($stmt->error ?? 'Unknown error');
        }
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// Fetch employees for dropdown (only active ones)
$employees = [];
$result = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as employee_name FROM employees WHERE status = 'approved' ORDER BY first_name, last_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Fetch claims for display with joined data
$claims = [];
$result = $conn->query("
    SELECT c.*, e.first_name, e.last_name, p.name as provider_name 
    FROM claims c 
    LEFT JOIN employees e ON c.employee_id = e.id 
    LEFT JOIN providers p ON c.provider_id = p.id 
    ORDER BY c.submitted_at DESC
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $claims[] = $row;
    }
}

// Fetch providers for dropdown (only active ones)
$providers = [];
$result = $conn->query("SELECT id, name FROM providers WHERE status = 'active'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Management System</title>
    <style>
        /* Your CSS remains the same */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #0066cc, #004799);
            color: white;
            padding: 20px 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        header p {
            text-align: center;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            color: #0066cc;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 15px;
        }

        .form-group {
            flex: 1 0 calc(50% - 20px);
            margin: 0 10px 15px;
            min-width: 250px;
        }

        .form-group.full-width {
            flex: 1 0 calc(100% - 20px);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }

        button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        button:hover {
            background: #0052a3;
        }

        .btn-approve {
            background: #0c9d61;
        }

        .btn-approve:hover {
            background: #0a7a4a;
        }

        .btn-reject {
            background: #f44336;
        }

        .btn-reject:hover {
            background: #d32f2f;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            color: #444;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status.submitted {
            background-color: #e8f4ff;
            color: #0066cc;
        }

        .status.approved {
            background-color: #e6f7ee;
            color: #0c9d61;
        }

        .status.rejected {
            background-color: #feeaea;
            color: #f44336;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: #0066cc;
            border-bottom: 3px solid #0066cc;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .validation-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .employee-selector {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .employee-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        .employee-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #e8f4ff;
            border-radius: 8px;
            border: 1px solid #cce5ff;
        }

        .employee-details p {
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 calc(100% - 20px);
            }

            .container {
                padding: 15px;
            }

            .validation-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Claim Management System</h1>
            <p>Submit, track, and validate employee HMO/benefit claims</p>
        </header>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('submit')">Submit Claim</div>
            <div class="tab" onclick="switchTab('track')">Track Claims</div>
            <div class="tab" onclick="switchTab('validate')">Validate Claims</div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div id="submit" class="tab-content active">
            <div class="card">
                <h2>Claim Intake Form</h2>

                <div class="employee-selector">
                    <label for="select_employee">Select Employee *</label>
                    <select id="select_employee" name="select_employee" required onchange="populateEmployeeDetails()">
                        <option value="">Select an Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>"
                                data-name="<?php echo $employee['employee_name']; ?>">
                                <?php echo $employee['employee_name']; ?> (ID: <?php echo $employee['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="employee_details" class="employee-details">
                        <p><strong>Employee ID:</strong> <span id="detail_id"></span></p>
                        <p><strong>Name:</strong> <span id="detail_name"></span></p>
                    </div>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <input type="hidden" id="employee_id" name="employee_id">
                    <input type="hidden" id="employee_name" name="employee_name">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="claim_type">Claim Type *</label>
                            <select id="claim_type" name="claim_type" required>
                                <option value="">Select Claim Type</option>
                                <option value="medical">Medical</option>
                                <option value="dental">Dental</option>
                                <option value="vision">Vision</option>
                                <option value="wellness">Wellness</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="provider">Provider *</label>
                            <select id="provider" name="provider" required>
                                <option value="">Select Provider</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>"><?php echo $provider['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount ($) *</label>
                            <input type="number" id="amount" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="documents">Supporting Documents</label>
                            <input type="file" id="documents" name="documents">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" placeholder="Please describe the treatment or service received" required></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" name="submit_claim">Submit Claim</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="track" class="tab-content">
            <div class="card">
                <h2>Claim Status Tracking</h2>
                <?php if (count($claims) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td>#<?php echo $claim['id']; ?></td>
                                    <td><?php echo $claim['first_name'] . ' ' . $claim['last_name']; ?></td>
                                    <td><?php echo ucfirst($claim['claim_type']); ?></td>
                                    <td><?php echo $claim['provider_name']; ?></td>
                                    <td>$<?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($claim['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($claim['status']); ?>">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No claims found. Submit a claim to see it here.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="validate" class="tab-content">
            <div class="card">
                <h2>Claim Validation</h2>
                <p>Validate submitted claims by approving or rejecting them.</p>

                <?php if (count($claims) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td>#<?php echo $claim['id']; ?></td>
                                    <td><?php echo $claim['first_name'] . ' ' . $claim['last_name']; ?></td>
                                    <td><?php echo ucfirst($claim['claim_type']); ?></td>
                                    <td><?php echo $claim['provider_name']; ?></td>
                                    <td>$<?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($claim['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($claim['status']); ?>">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($claim['status'] == 'submitted'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                <div class="validation-actions">
                                                    <button type="submit" name="validate_claim" value="approve" class="btn-approve">Approve</button>
                                                    <button type="submit" name="validate_claim" value="reject" class="btn-reject">Reject</button>
                                                </div>
                                                <textarea name="reviewer_notes" placeholder="Review notes (optional)" style="width: 100%; margin-top: 10px; padding: 8px;"></textarea>
                                            </form>
                                        <?php else: ?>
                                            <em>Processed</em>
                                            <?php if (!empty($claim['reviewer_notes'])): ?>
                                                <br><small>Notes: <?php echo $claim['reviewer_notes']; ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No claims to validate.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Update active tab
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
                if (tabs[i].textContent.toLowerCase().includes(tabName.toLowerCase())) {
                    tabs[i].classList.add('active');
                }
            }
        }

        function populateEmployeeDetails() {
            const select = document.getElementById('select_employee');
            const selectedOption = select.options[select.selectedIndex];
            const detailsDiv = document.getElementById('employee_details');

            if (selectedOption.value) {
                // Show employee details
                detailsDiv.style.display = 'block';
                document.getElementById('detail_id').textContent = selectedOption.value;
                document.getElementById('detail_name').textContent = selectedOption.getAttribute('data-name');

                // Populate hidden form fields
                document.getElementById('employee_id').value = selectedOption.value;
                document.getElementById('employee_name').value = selectedOption.getAttribute('data-name');
            } else {
                // Hide employee details
                detailsDiv.style.display = 'none';
                document.getElementById('employee_id').value = '';
                document.getElementById('employee_name').value = '';
            }
        }

        function validateForm() {
            const employeeId = document.getElementById('employee_id').value;
            const claimType = document.getElementById('claim_type').value;
            const provider = document.getElementById('provider').value;
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;

            if (!employeeId || !claimType || !provider || !amount || !description) {
                alert('Please fill in all required fields.');
                return false;
            }

            if (amount <= 0) {
                alert('Please enter a valid amount greater than zero.');
                return false;
            }

            return true;
        }
    </script>
</body>

</html>