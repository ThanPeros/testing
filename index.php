<?php
require 'sidebar.php';
// Database connection
$host = 'localhost';
$dbname = 'systems';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Get filter values from request
$dateRange = isset($_GET['date-range']) ? $_GET['date-range'] : '30';
$department = isset($_GET['department']) ? $_GET['department'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch data for dashboard with filters
function fetchEmployeeCount($pdo, $department, $statusFilter)
{
    $sql = "SELECT COUNT(DISTINCT e.id) as count 
            FROM employees e 
            LEFT JOIN status s ON e.id = s.employee_id";

    $conditions = [];
    $params = [];

    if ($department !== 'all') {
        $conditions[] = "e.department = :department";
        $params[':department'] = $department;
    }

    if ($statusFilter !== 'all') {
        // Get the latest status for each employee
        $conditions[] = "s.status = :status AND s.changed_at = (
            SELECT MAX(s2.changed_at) 
            FROM status s2 
            WHERE s2.employee_id = e.id
        )";
        $params[':status'] = $statusFilter;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function fetchStatusStats($pdo, $department, $dateRange)
{
    $dateCondition = "";
    if ($dateRange !== 'all') {
        $dateCondition = "WHERE s.changed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    }

    $sql = "SELECT s.status, COUNT(DISTINCT s.employee_id) as count 
            FROM status s
            INNER JOIN (
                SELECT employee_id, MAX(changed_at) as max_date
                FROM status
                GROUP BY employee_id
            ) latest ON s.employee_id = latest.employee_id AND s.changed_at = latest.max_date
            $dateCondition
            GROUP BY s.status";

    $stmt = $pdo->prepare($sql);
    if ($dateRange !== 'all') {
        $stmt->bindValue(':days', (int)$dateRange, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchActiveEmployees($pdo, $department, $dateRange)
{
    $dateCondition = "";
    if ($dateRange !== 'all') {
        $dateCondition = "AND s1.changed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    }

    $departmentCondition = "";
    if ($department !== 'all') {
        $departmentCondition = "AND e.department = :department";
    }

    $sql = "SELECT s1.employee_id, s1.status, s1.changed_at, e.first_name, e.last_name, e.department
            FROM status s1
            INNER JOIN employees e ON s1.employee_id = e.id
            INNER JOIN (
                SELECT employee_id, MAX(changed_at) as max_date
                FROM status
                GROUP BY employee_id
            ) s2 ON s1.employee_id = s2.employee_id AND s1.changed_at = s2.max_date
            WHERE s1.status = 'active' 
            $dateCondition
            $departmentCondition";

    $stmt = $pdo->prepare($sql);

    if ($dateRange !== 'all') {
        $stmt->bindValue(':days', (int)$dateRange, PDO::PARAM_INT);
    }

    if ($department !== 'all') {
        $stmt->bindValue(':department', $department);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchClaimsData($pdo, $dateRange)
{
    $dateCondition = "";
    if ($dateRange !== 'all') {
        $dateCondition = "WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    }

    $sql = "SELECT claim_type, COUNT(id) as count, COALESCE(SUM(amount), 0) as total_amount 
            FROM claims 
            $dateCondition
            GROUP BY claim_type";

    $stmt = $pdo->prepare($sql);
    if ($dateRange !== 'all') {
        $stmt->bindValue(':days', (int)$dateRange, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSalaryData($pdo, $department, $dateRange)
{
    $dateCondition = "";
    if ($dateRange !== 'all') {
        $dateCondition = "AND ec.calculation_date >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    }

    $departmentCondition = "";
    if ($department !== 'all') {
        $departmentCondition = "AND e.department = :department";
    }

    $sql = "SELECT 
                e.id,
                e.first_name, 
                e.last_name, 
                e.department,
                pr.rate as pay_rate,
                pr.pay_type,
                COALESCE(sa.base_salary, 0) as base_salary,
                COALESCE(ec.base_pay, 0) as base_pay,
                COALESCE(ec.overtime_pay, 0) as overtime_pay,
                COALESCE(ec.holiday_pay, 0) as holiday_pay,
                COALESCE(ec.night_differential, 0) as night_differential,
                COALESCE(ec.incentives, 0) as incentives,
                COALESCE(ec.bonuses, 0) as bonuses,
                COALESCE(ec.allowances, 0) as allowances,
                COALESCE(ec.gross_pay, 0) as gross_pay,
                ec.calculation_date
            FROM employees e 
            LEFT JOIN pay_rates pr ON e.id = pr.employee_id
            LEFT JOIN salary_assignments sa ON e.id = sa.employee_id
            LEFT JOIN earnings_calculations ec ON e.id = ec.employee_id 
            WHERE ec.id IS NOT NULL
            $dateCondition
            $departmentCondition
            ORDER BY ec.calculation_date DESC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);

    if ($dateRange !== 'all') {
        $stmt->bindValue(':days', (int)$dateRange, PDO::PARAM_INT);
    }

    if ($department !== 'all') {
        $stmt->bindValue(':department', $department);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchRecentStatusChanges($pdo, $dateRange)
{
    $dateCondition = "";
    if ($dateRange !== 'all') {
        $dateCondition = "WHERE s.changed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    }

    $sql = "SELECT s.employee_id, CONCAT(e.first_name, ' ', e.last_name) as name, 
                   s.status, s.changed_at, s.changed_by
            FROM status s
            JOIN employees e ON s.employee_id = e.id
            $dateCondition
            ORDER BY s.changed_at DESC
            LIMIT 5";

    $stmt = $pdo->prepare($sql);
    if ($dateRange !== 'all') {
        $stmt->bindValue(':days', (int)$dateRange, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchDepartments($pdo)
{
    $stmt = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get data with filters
$employeeCount = fetchEmployeeCount($pdo, $department, $statusFilter);
$statusStats = fetchStatusStats($pdo, $department, $dateRange);
$activeEmployees = fetchActiveEmployees($pdo, $department, $dateRange);
$claimsData = fetchClaimsData($pdo, $dateRange);
$departments = fetchDepartments($pdo);

// Handle potential errors with salary data
try {
    $salaryData = fetchSalaryData($pdo, $department, $dateRange);
} catch (PDOException $e) {
    // If there's an error, create empty salary data
    $salaryData = [];
    error_log("Error fetching salary data: " . $e->getMessage());
}

$recentStatusChanges = fetchRecentStatusChanges($pdo, $dateRange);

// Calculate active employee count
$activeCount = 0;
foreach ($statusStats as $stat) {
    if ($stat['status'] == 'active') {
        $activeCount = $stat['count'];
        break;
    }
}

// Calculate average salary
$totalSalary = 0;
$count = 0;
foreach ($salaryData as $salary) {
    if ($salary['gross_pay'] > 0) {
        $totalSalary += $salary['gross_pay'];
        $count++;
    }
}
$avgSalary = $count > 0 ? $totalSalary / $count : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #ecf0f1;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --text: #333;
            --text-light: #777;
            --border: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            display: block;
            min-height: 100vh;
        }

        .main-content {
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card h2 {
            font-size: 2.5rem;
            margin: 10px 0;
        }

        .stat-card.employees i {
            color: var(--accent);
        }

        .stat-card.active i {
            color: var(--success);
        }

        .stat-card.claims i {
            color: var(--warning);
        }

        .stat-card.salary i {
            color: var(--danger);
        }

        .stat-card p {
            color: var(--text-light);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table,
        th,
        td {
            border: 1px solid var(--border);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: var(--secondary);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }

        .filter-item label {
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        select,
        input {
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            background: white;
        }

        .btn {
            padding: 10px 15px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background-color: var(--success);
            color: white;
        }

        .status-inactive {
            background-color: var(--danger);
            color: white;
        }

        .recent-activity {
            margin-top: 30px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 15px;
            }

            .nav-items {
                flex-wrap: wrap;
                justify-content: center;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }

            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* New styles for AI prediction section */
        .ai-prediction-container {
            margin-top: 30px;
            display: none;
        }

        .ai-prediction-container.active {
            display: block;
        }

        .ai-iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-ai {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            padding: 12px 20px;
            font-weight: bold;
        }

        .btn-ai:hover {
            background: linear-gradient(45deg, #ee5a24, #ff6b6b);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="top-nav">
            <div class="logo">
                <h2><i class="fas fa-chart-line"></i> HR Analytics</h2>
            </div>

            <form method="GET" action="" id="filter-form">
                <div class="filters">
                    <div class="filter-item">
                        <label for="date-range"><i class="fas fa-calendar"></i> Date Range</label>
                        <select id="date-range" name="date-range">
                            <option value="7" <?php echo $dateRange == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $dateRange == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $dateRange == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="365" <?php echo $dateRange == '365' ? 'selected' : ''; ?>>Last Year</option>
                            <option value="all" <?php echo $dateRange == 'all' ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="department"><i class="fas fa-building"></i> Department</label>
                        <select id="department" name="department">
                            <option value="all" <?php echo $department == 'all' ? 'selected' : ''; ?>>All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"
                                    <?php echo $department == $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="status"><i class="fas fa-user-check"></i> Employment Status</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn" id="apply-filters"><i class="fas fa-filter"></i> Apply Filters</button>
                    </div>
                    <div class="filter-item">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-ai" id="predict-attrition-btn">
                            <i class="fas fa-robot"></i> Predict Attrition Employee
                        </button>
                    </div>
                </div>
            </form>

            <div class="loading" id="loading-indicator">
                <div class="loading-spinner"></div>
                <p>Loading data...</p>
            </div>

            <div class="ai-prediction-container" id="ai-prediction-container">
                <div class="card">
                    <h3><i class="fas fa-brain"></i> AI Attrition Prediction</h3>
                    <iframe src="" class="ai-iframe" id="ai-iframe"></iframe>
                </div>
            </div>

            <div class="dashboard-cards">
                <div class="card stat-card employees">
                    <i class="fas fa-users"></i>
                    <h3>Total Employees</h3>
                    <h2><?php echo $employeeCount; ?></h2>
                    <p>Across all departments</p>
                </div>

                <div class="card stat-card active">
                    <i class="fas fa-user-check"></i>
                    <h3>Active Employees</h3>
                    <h2><?php echo $activeCount; ?></h2>
                    <p>Currently employed</p>
                </div>

                <div class="card stat-card claims">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3>Total Claims</h3>
                    <h2>
                        <?php
                        $totalClaims = 0;
                        foreach ($claimsData as $claim) {
                            $totalClaims += $claim['count'];
                        }
                        echo $totalClaims;
                        ?>
                    </h2>
                    <p>All claim types</p>
                </div>

                <div class="card stat-card salary">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Avg. Salary</h3>
                    <h2>$<?php echo number_format($avgSalary, 2); ?></h2>
                    <p>Average gross pay</p>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Employee Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Claims by Type</h3>
                <div class="chart-container">
                    <canvas id="claimsChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-money-bill-wave"></i> Salary Data</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Pay Type</th>
                            <th>Pay Rate</th>
                            <th>Base Salary</th>
                            <th>Overtime</th>
                            <th>Bonuses</th>
                            <th>Gross Pay</th>
                            <th>Calculation Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($salaryData) > 0): ?>
                            <?php foreach ($salaryData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pay_type']); ?></td>
                                    <td>$<?php echo number_format($row['pay_rate'], 2); ?></td>
                                    <td>$<?php echo number_format($row['base_salary'], 2); ?></td>
                                    <td>$<?php echo number_format($row['overtime_pay'], 2); ?></td>
                                    <td>$<?php echo number_format($row['bonuses'], 2); ?></td>
                                    <td>$<?php echo number_format($row['gross_pay'], 2); ?></td>
                                    <td><?php echo $row['calculation_date']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No salary data available for the selected filters</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card recent-activity">
                <h3><i class="fas fa-history"></i> Recent Status Changes</h3>
                <?php if (count($recentStatusChanges) > 0): ?>
                    <?php foreach ($recentStatusChanges as $change): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="activity-details">
                                <p><strong><?php echo htmlspecialchars($change['name']); ?></strong> status changed to
                                    <span class="status-badge status-<?php echo $change['status']; ?>"><?php echo $change['status']; ?></span>
                                </p>
                                <small>By <?php echo $change['changed_by'] ?: 'System'; ?> on <?php echo $change['changed_at']; ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recent status changes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php
                        foreach ($statusStats as $stat) {
                            echo "'" . ucfirst($stat['status']) . "',";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php
                            foreach ($statusStats as $stat) {
                                echo $stat['count'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Claims Chart
            const claimsCtx = document.getElementById('claimsChart').getContext('2d');
            const claimsChart = new Chart(claimsCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        foreach ($claimsData as $claim) {
                            echo "'" . ucfirst($claim['claim_type']) . "',";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Number of Claims',
                        data: [
                            <?php
                            foreach ($claimsData as $claim) {
                                echo $claim['count'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: '#3498db'
                    }, {
                        label: 'Total Amount',
                        data: [
                            <?php
                            foreach ($claimsData as $claim) {
                                echo $claim['total_amount'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: '#2ecc71',
                        type: 'line',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Claims'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Amount'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

            // Filter functionality
            const filterForm = document.getElementById('filter-form');
            const loadingIndicator = document.getElementById('loading-indicator');

            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });

            function applyFilters() {
                // Show loading indicator
                loadingIndicator.style.display = 'block';

                // Submit the form to reload the page with new filters
                filterForm.submit();
            }

            // Automatically apply filters when dropdown values change
            document.getElementById('date-range').addEventListener('change', applyFilters);
            document.getElementById('department').addEventListener('change', applyFilters);
            document.getElementById('status').addEventListener('change', applyFilters);

            // AI Prediction Button functionality
            const predictBtn = document.getElementById('predict-attrition-btn');
            const aiContainer = document.getElementById('ai-prediction-container');
            const aiIframe = document.getElementById('ai-iframe');

            predictBtn.addEventListener('click', function() {
                // Toggle the AI prediction container
                aiContainer.classList.toggle('active');

                // If the container is visible, load the AI page
                if (aiContainer.classList.contains('active')) {
                    aiIframe.src = 'ai.php';

                    // Scroll to the AI prediction section
                    aiContainer.scrollIntoView({
                        behavior: 'smooth'
                    });
                } else {
                    // If hiding, unload the iframe
                    aiIframe.src = '';
                }
            });
        });
    </script>
</body>

</html>