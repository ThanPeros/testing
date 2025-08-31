<?php
require '../sidebar.php';
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

// Handle form submissions
$success = '';
$error = '';

// Add Performance Review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $employee_id = intval($_POST['employee_id']);
    $review_date = $_POST['review_date'];
    $performance_score = !empty($_POST['performance_score']) ? floatval($_POST['performance_score']) : null;
    $strengths = $_POST['strengths'];
    $areas_for_improvement = $_POST['areas_for_improvement'];
    $goals = $_POST['goals'];
    $comments = $_POST['comments'];

    $stmt = $conn->prepare("INSERT INTO performance_reviews 
                          (employee_id, review_date, performance_score, strengths, 
                          areas_for_improvement, goals, comments) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isdssss",
        $employee_id,
        $review_date,
        $performance_score,
        $strengths,
        $areas_for_improvement,
        $goals,
        $comments
    );

    if ($stmt->execute()) {
        $success = "Performance review added successfully!";
    } else {
        $error = "Error adding performance review: " . $stmt->error;
    }
    $stmt->close();
}

// Record Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_attendance'])) {
    $employee_id = intval($_POST['employee_id']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $check_in = !empty($_POST['check_in']) ? $_POST['check_in'] : null;
    $check_out = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO attendance 
                          (employee_id, date, status, check_in, check_out, notes) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $employee_id, $date, $status, $check_in, $check_out, $notes);

    if ($stmt->execute()) {
        $success = "Attendance recorded successfully!";
    } else {
        $error = "Error recording attendance: " . $stmt->error;
    }
    $stmt->close();
}

// Add KPI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_kpi'])) {
    $employee_id = intval($_POST['employee_id']);
    $kpi_name = $_POST['kpi_name'];
    $target_value = !empty($_POST['target_value']) ? floatval($_POST['target_value']) : null;
    $measurement_period = $_POST['measurement_period'];
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    $stmt = $conn->prepare("INSERT INTO kpis 
                          (employee_id, kpi_name, target_value, measurement_period, start_date, end_date) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsss", $employee_id, $kpi_name, $target_value, $measurement_period, $start_date, $end_date);

    if ($stmt->execute()) {
        $success = "KPI added successfully!";
    } else {
        $error = "Error adding KPI: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch data for display
$employees = $conn->query("SELECT id, first_name, last_name FROM employees ORDER BY first_name");
$performance_reviews = $conn->query("SELECT pr.*, e.first_name, e.last_name
                                   FROM performance_reviews pr 
                                   JOIN employees e ON pr.employee_id = e.id 
                                   ORDER BY pr.review_date DESC");
$attendance = $conn->query("SELECT a.*, e.first_name, e.last_name 
                          FROM attendance a 
                          JOIN employees e ON a.employee_id = e.id 
                          ORDER BY a.date DESC");
$kpis = $conn->query("SELECT k.*, e.first_name, e.last_name 
                     FROM kpis k 
                     JOIN employees e ON k.employee_id = e.id 
                     ORDER BY k.start_date DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring & Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-fluid">
        <h1 class="mb-4">Monitoring & Evaluation System</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="mandeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">Performance Reviews</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">Attendance Tracking</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="kpis-tab" data-bs-toggle="tab" data-bs-target="#kpis" type="button" role="tab">Key Performance Indicators</button>
            </li>
        </ul>

        <div class="tab-content" id="mandeTabsContent">
            <!-- Performance Reviews Tab -->
            <div class="tab-pane fade show active" id="performance" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Add Performance Review</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select class="form-select employee-select" id="employee_id" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        <?php while ($employee = $employees->fetch_assoc()): ?>
                                            <option value="<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="review_date" class="form-label">Review Date</label>
                                    <input type="date" class="form-control" id="review_date" name="review_date" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="performance_score" class="form-label">Performance Score (1-100)</label>
                                    <input type="number" class="form-control" id="performance_score" name="performance_score" min="1" max="100">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="strengths" class="form-label">Strengths</label>
                                <textarea class="form-control" id="strengths" name="strengths" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="areas_for_improvement" class="form-label">Areas for Improvement</label>
                                <textarea class="form-control" id="areas_for_improvement" name="areas_for_improvement" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="goals" class="form-label">Goals</label>
                                <textarea class="form-control" id="goals" name="goals" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="2"></textarea>
                            </div>
                            <button type="submit" name="add_review" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Performance Review History</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Review Date</th>
                                        <th>Score</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($review = $performance_reviews->fetch_assoc()):
                                        $score_class = '';
                                        if ($review['performance_score'] >= 90) $score_class = 'score-excellent';
                                        elseif ($review['performance_score'] >= 75) $score_class = 'score-good';
                                        elseif ($review['performance_score'] >= 60) $score_class = 'score-average';
                                        else $score_class = 'score-poor';
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($review['review_date'])); ?></td>
                                            <td class="performance-score <?php echo $score_class; ?>">
                                                <?php echo $review['performance_score'] ?? 'N/A'; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-review" data-id="<?php echo $review['id']; ?>">View Details</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Tracking Tab -->
            <div class="tab-pane fade" id="attendance" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Record Attendance</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="attendance_employee_id" class="form-label">Employee</label>
                                    <select class="form-select employee-select" id="attendance_employee_id" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        <?php
                                        $employees->data_seek(0); // Reset pointer
                                        while ($employee = $employees->fetch_assoc()): ?>
                                            <option value="<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                        <option value="leave">Leave</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="check_in" class="form-label">Check In</label>
                                    <input type="time" class="form-control" id="check_in" name="check_in">
                                </div>
                                <div class="col-md-4">
                                    <label for="check_out" class="form-label">Check Out</label>
                                    <input type="time" class="form-control" id="check_out" name="check_out">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                            <button type="submit" name="record_attendance" class="btn btn-primary">Record Attendance</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Attendance Records</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $attendance->fetch_assoc()):
                                        $status_class = 'attendance-' . $record['status'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td class="<?php echo $status_class; ?>">
                                                <?php echo ucfirst(htmlspecialchars($record['status'])); ?>
                                            </td>
                                            <td><?php echo $record['check_in'] ? date('h:i A', strtotime($record['check_in'])) : 'N/A'; ?></td>
                                            <td><?php echo $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($record['notes']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs Tab -->
            <div class="tab-pane fade" id="kpis" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Add Key Performance Indicator</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="kpi_employee_id" class="form-label">Employee</label>
                                    <select class="form-select employee-select" id="kpi_employee_id" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        <?php
                                        $employees->data_seek(0); // Reset pointer
                                        while ($employee = $employees->fetch_assoc()): ?>
                                            <option value="<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="kpi_name" class="form-label">KPI Name</label>
                                    <input type="text" class="form-control" id="kpi_name" name="kpi_name" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="target_value" class="form-label">Target Value</label>
                                    <input type="number" class="form-control" id="target_value" name="target_value" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label for="measurement_period" class="form-label">Measurement Period</label>
                                    <select class="form-select" id="measurement_period" name="measurement_period" required>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date (optional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                            <button type="submit" name="add_kpi" class="btn btn-primary">Add KPI</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Key Performance Indicators</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>KPI Name</th>
                                        <th>Target Value</th>
                                        <th>Period</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($kpi = $kpis->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($kpi['first_name'] . ' ' . $kpi['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($kpi['kpi_name']); ?></td>
                                            <td><?php echo $kpi['target_value'] ?? 'N/A'; ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($kpi['measurement_period'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($kpi['start_date'])); ?></td>
                                            <td><?php echo $kpi['end_date'] ? date('M d, Y', strtotime($kpi['end_date'])) : 'Ongoing'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Details Modal -->
    <div class="modal fade" id="reviewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Performance Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reviewDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.employee-select').select2({
                width: '100%'
            });

            // View Review Details
            $('.view-review').click(function() {
                var reviewId = $(this).data('id');
                $.ajax({
                    url: 'get_review_details.php',
                    type: 'GET',
                    data: {
                        id: reviewId
                    },
                    success: function(response) {
                        $('#reviewDetailsContent').html(response);
                        $('#reviewDetailsModal').modal('show');
                    },
                    error: function() {
                        alert('Error loading review details');
                    }
                });
            });

            // Tab persistence
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                localStorage.setItem('activeTab', e.target.id);
            });

            var activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                $('#' + activeTab).tab('show');
            }
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>