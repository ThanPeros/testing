<?php
include '../sidebar.php';
// Database configuration
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

// Create additional tables if they don't exist
$additional_tables = [
    "CREATE TABLE IF NOT EXISTS pay_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        period_name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_current BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS earnings_calculations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        pay_period_id INT NOT NULL,
        base_pay DECIMAL(12, 2) NOT NULL,
        overtime_pay DECIMAL(12, 2) DEFAULT 0,
        holiday_pay DECIMAL(12, 2) DEFAULT 0,
        night_differential DECIMAL(12, 2) DEFAULT 0,
        incentives DECIMAL(12, 2) DEFAULT 0,
        bonuses DECIMAL(12, 2) DEFAULT 0,
        allowances DECIMAL(12, 2) DEFAULT 0,
        gross_pay DECIMAL(12, 2) NOT NULL,
        calculation_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS pay_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        pay_type ENUM('monthly', 'daily', 'hourly') NOT NULL,
        rate DECIMAL(12, 2) NOT NULL,
        effective_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS overtime_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rate_name VARCHAR(100) NOT NULL,
        multiplier DECIMAL(4, 2) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS holiday_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_name VARCHAR(100) NOT NULL,
        holiday_date DATE NOT NULL,
        multiplier DECIMAL(4, 2) NOT NULL,
        description TEXT,
        is_recurring BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS night_differential_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        multiplier DECIMAL(4, 2) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($additional_tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error;
    }
}

// Insert default data if tables are empty
$check_pay_rates = $conn->query("SELECT COUNT(*) as count FROM pay_rates");
$row = $check_pay_rates->fetch_assoc();
if ($row['count'] == 0) {
    // Get employee IDs
    $employee_ids = $conn->query("SELECT id FROM employees");

    // Insert sample pay rates
    while ($employee = $employee_ids->fetch_assoc()) {
        $pay_type = rand(0, 2) == 0 ? 'monthly' : (rand(0, 1) == 0 ? 'daily' : 'hourly');
        $rate = $pay_type == 'monthly' ? rand(20000, 50000) : ($pay_type == 'daily' ? rand(800, 2000) : rand(100, 300));

        $conn->query("INSERT INTO pay_rates (employee_id, pay_type, rate, effective_date) VALUES 
            (" . $employee['id'] . ", '" . $pay_type . "', " . $rate . ", CURDATE())");
    }

    // Insert overtime rates
    $conn->query("INSERT INTO overtime_rates (rate_name, multiplier, description) VALUES 
        ('Regular Overtime', 1.25, 'Standard overtime rate'),
        ('Rest Day Overtime', 1.69, 'Overtime on rest days'),
        ('Holiday Overtime', 2.60, 'Overtime on holidays')");

    // Insert holiday settings
    $current_year = date('Y');
    $conn->query("INSERT INTO holiday_settings (holiday_name, holiday_date, multiplier, description) VALUES 
        ('New Year''s Day', '$current_year-01-01', 2.0, 'Regular holiday'),
        ('Independence Day', '$current_year-06-12', 2.0, 'Regular holiday'),
        ('Christmas Day', '$current_year-12-25', 2.0, 'Regular holiday')");

    // Insert night differential settings
    $conn->query("INSERT INTO night_differential_settings (start_time, end_time, multiplier, description) VALUES 
        ('22:00:00', '06:00:00', 1.10, 'Night differential rate')");

    // Insert current pay period
    $conn->query("INSERT INTO pay_periods (period_name, start_date, end_date, is_current) VALUES 
        ('Current Period', DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), TRUE)");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['calculate_earnings'])) {
        $employee_id = $_POST['employee_id'];
        $pay_period_id = $_POST['pay_period_id'];
        $overtime_hours = $_POST['overtime_hours'];
        $holiday_hours = $_POST['holiday_hours'];
        $night_hours = $_POST['night_hours'];
        $incentives = $_POST['incentives'];
        $bonuses = $_POST['bonuses'];

        // Get employee pay rate
        $pay_rate_result = $conn->query("SELECT * FROM pay_rates WHERE employee_id = $employee_id ORDER BY effective_date DESC LIMIT 1");
        $pay_rate = $pay_rate_result->fetch_assoc();

        // Get overtime rate
        $overtime_rate_result = $conn->query("SELECT multiplier FROM overtime_rates WHERE is_active = TRUE ORDER BY id LIMIT 1");
        $overtime_rate = $overtime_rate_result->fetch_assoc();

        // Get holiday rate
        $holiday_rate_result = $conn->query("SELECT multiplier FROM holiday_settings ORDER BY id LIMIT 1");
        $holiday_rate = $holiday_rate_result->fetch_assoc();

        // Get night differential rate
        $night_rate_result = $conn->query("SELECT multiplier FROM night_differential_settings WHERE is_active = TRUE ORDER BY id LIMIT 1");
        $night_rate = $night_rate_result->fetch_assoc();

        // Calculate base pay based on pay type
        $base_pay = 0;
        if ($pay_rate['pay_type'] == 'monthly') {
            $base_pay = $pay_rate['rate'];
        } elseif ($pay_rate['pay_type'] == 'daily') {
            // Assuming 22 working days in a month
            $base_pay = $pay_rate['rate'] * 22;
        } elseif ($pay_rate['pay_type'] == 'hourly') {
            // Assuming 8 hours per day and 22 working days
            $base_pay = $pay_rate['rate'] * 8 * 22;
        }

        // Calculate overtime pay
        $hourly_rate = $pay_rate['pay_type'] == 'hourly' ? $pay_rate['rate'] : $base_pay / (22 * 8);
        $overtime_pay = $overtime_hours * $hourly_rate * $overtime_rate['multiplier'];

        // Calculate holiday pay
        $holiday_pay = $holiday_hours * $hourly_rate * $holiday_rate['multiplier'];

        // Calculate night differential
        $night_differential = $night_hours * $hourly_rate * ($night_rate['multiplier'] - 1);

        // Get allowances
        $allowances_result = $conn->query("SELECT transport, meal, housing, communication, other_benefits FROM allowances WHERE employee_id = $employee_id ORDER BY created_at DESC LIMIT 1");
        $allowances_data = $allowances_result->fetch_assoc();
        $allowances = $allowances_data ? array_sum($allowances_data) : 0;

        // Calculate gross pay
        $gross_pay = $base_pay + $overtime_pay + $holiday_pay + $night_differential + $incentives + $bonuses + $allowances;

        // Insert calculation into database
        $stmt = $conn->prepare("INSERT INTO earnings_calculations (employee_id, pay_period_id, base_pay, overtime_pay, holiday_pay, night_differential, incentives, bonuses, allowances, gross_pay, calculation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("iidddddddd", $employee_id, $pay_period_id, $base_pay, $overtime_pay, $holiday_pay, $night_differential, $incentives, $bonuses, $allowances, $gross_pay);

        if ($stmt->execute()) {
            echo "<div class='success-msg'>Earnings calculated successfully! Gross Pay: ₱" . number_format($gross_pay, 2) . "</div>";
        } else {
            echo "<div class='error-msg'>Error calculating earnings: " . $conn->error . "</div>";
        }
    }
}

// Fetch data for display
$employees = $conn->query("SELECT * FROM employees ORDER BY first_name, last_name");
$pay_periods = $conn->query("SELECT * FROM pay_periods ORDER BY start_date DESC");
$earnings_data = $conn->query("SELECT ec.*, e.first_name, e.last_name, pp.period_name 
                              FROM earnings_calculations ec 
                              JOIN employees e ON ec.employee_id = e.id 
                              JOIN pay_periods pp ON ec.pay_period_id = pp.id 
                              ORDER BY ec.created_at DESC LIMIT 10");
$overtime_rates = $conn->query("SELECT * FROM overtime_rates");
$holiday_settings = $conn->query("SELECT * FROM holiday_settings");
$night_settings = $conn->query("SELECT * FROM night_differential_settings");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Calculation System</title>
    <style>
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
            padding-bottom: 20px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 15px 0;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .description {
            font-size: 1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaeaea;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        button {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.3s;
            margin-top: 5px;
        }

        button:hover {
            background: #4a6491;
        }

        .recent-data {
            margin-top: 20px;
        }

        .recent-data h3 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 0.8rem;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-size: 0.85rem;
        }

        .success-msg {
            background-color: #d4edda;
            color: #155724;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            overflow-x: auto;
            white-space: nowrap;
        }

        .tab-button {
            padding: 10px 20px;
            background: #f1f1f1;
            border: none;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            min-width: 120px;
            text-align: center;
        }

        .tab-button.active {
            background: #2c3e50;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .compact-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .full-width {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-button {
                margin-bottom: 5px;
                border-radius: 4px;
                min-width: auto;
            }

            .compact-form {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            h1 {
                font-size: 1.5rem;
            }
        }

        .form-section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }

        .form-section-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a6491;
            font-size: 0.95rem;
        }

        .calculation-result {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid #2c3e50;
        }

        .calculation-result h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .calculation-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #ddd;
        }

        .calculation-total {
            font-weight: bold;
            border-top: 2px solid #2c3e50;
            padding-top: 8px;
            margin-top: 8px;
        }

        .history-table {
            font-size: 0.8rem;
        }

        .history-table th,
        .history-table td {
            padding: 6px;
        }

        .history-table-container {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Earnings Calculation System</h1>
            <p class="description">Calculate gross pay including base pay, overtime, holiday pay, night differential, incentives, bonuses, and allowances.</p>
        </div>
    </header>

    <div class="container">
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('calculate')">Calculate Earnings</button>
            <button class="tab-button" onclick="openTab('history')">Calculation History</button>
        </div>

        <div id="calculate" class="tab-content active">
            <div class="dashboard">
                <div class="card">
                    <h2>Earnings Calculation</h2>
                    <form method="POST" class="compact-form">
                        <div class="form-group full-width">
                            <label for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id" required onchange="loadEmployeeDetails(this.value)">
                                <option value="">Select Employee</option>
                                <?php
                                $employees_result = $conn->query("SELECT * FROM employees ORDER BY first_name, last_name");
                                while ($employee = $employees_result->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pay_period_id">Pay Period</label>
                            <select id="pay_period_id" name="pay_period_id" required>
                                <option value="">Select Pay Period</option>
                                <?php
                                $pay_periods_result = $conn->query("SELECT * FROM pay_periods ORDER BY start_date DESC");
                                while ($period = $pay_periods_result->fetch_assoc()): ?>
                                    <option value="<?php echo $period['id']; ?>" <?php echo $period['is_current'] ? 'selected' : ''; ?>>
                                        <?php echo $period['period_name'] . ' (' . date('M j', strtotime($period['start_date'])) . ' - ' . date('M j', strtotime($period['end_date'])) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <div class="form-section-title">Employee Pay Details</div>
                            <div id="employee-details">Select an employee to view details</div>
                        </div>

                        <div class="form-group full-width">
                            <div class="form-section-title">Additional Earnings</div>
                        </div>

                        <div class="form-group">
                            <label for="overtime_hours">Overtime Hours</label>
                            <input type="number" id="overtime_hours" name="overtime_hours" step="0.5" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label for="holiday_hours">Holiday Hours</label>
                            <input type="number" id="holiday_hours" name="holiday_hours" step="0.5" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label for="night_hours">Night Differential Hours</label>
                            <input type="number" id="night_hours" name="night_hours" step="0.5" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label for="incentives">Incentives (₱)</label>
                            <input type="number" id="incentives" name="incentives" step="0.01" value="0" min="0">
                        </div>

                        <div class="form-group">
                            <label for="bonuses">Bonuses (₱)</label>
                            <input type="number" id="bonuses" name="bonuses" step="0.01" value="0" min="0">
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="calculate_earnings">Calculate Earnings</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Current Rates & Settings</h2>

                    <div class="form-section">
                        <div class="form-section-title">Overtime Rates</div>
                        <table>
                            <tr>
                                <th>Rate Name</th>
                                <th>Multiplier</th>
                                <th>Description</th>
                            </tr>
                            <?php
                            $overtime_rates_result = $conn->query("SELECT * FROM overtime_rates");
                            if ($overtime_rates_result && $overtime_rates_result->num_rows > 0): ?>
                                <?php while ($rate = $overtime_rates_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $rate['rate_name']; ?></td>
                                        <td><?php echo $rate['multiplier']; ?>x</td>
                                        <td><?php echo $rate['description']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No overtime rates found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Holiday Settings</div>
                        <table>
                            <tr>
                                <th>Holiday Name</th>
                                <th>Date</th>
                                <th>Multiplier</th>
                            </tr>
                            <?php
                            $holiday_settings_result = $conn->query("SELECT * FROM holiday_settings");
                            if ($holiday_settings_result && $holiday_settings_result->num_rows > 0): ?>
                                <?php while ($holiday = $holiday_settings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $holiday['holiday_name']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($holiday['holiday_date'])); ?></td>
                                        <td><?php echo $holiday['multiplier']; ?>x</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No holiday settings found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Night Differential</div>
                        <table>
                            <tr>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Multiplier</th>
                            </tr>
                            <?php
                            $night_settings_result = $conn->query("SELECT * FROM night_differential_settings");
                            if ($night_settings_result && $night_settings_result->num_rows > 0): ?>
                                <?php while ($night = $night_settings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('g:i A', strtotime($night['start_time'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($night['end_time'])); ?></td>
                                        <td><?php echo $night['multiplier']; ?>x</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No night differential settings found</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="history" class="tab-content">
            <div class="card">
                <h2>Earnings Calculation History</h2>
                <div class="history-table-container">
                    <table class="history-table">
                        <tr>
                            <th>Employee</th>
                            <th>Pay Period</th>
                            <th>Base Pay</th>
                            <th>Overtime</th>
                            <th>Holiday</th>
                            <th>Night Diff</th>
                            <th>Incentives</th>
                            <th>Bonuses</th>
                            <th>Allowances</th>
                            <th>Gross Pay</th>
                            <th>Date</th>
                        </tr>
                        <?php
                        $earnings_history = $conn->query("SELECT ec.*, e.first_name, e.last_name, pp.period_name 
                                  FROM earnings_calculations ec 
                                  JOIN employees e ON ec.employee_id = e.id 
                                  JOIN pay_periods pp ON ec.pay_period_id = pp.id 
                                  ORDER BY ec.created_at DESC");
                        if ($earnings_history && $earnings_history->num_rows > 0): ?>
                            <?php while ($earning = $earnings_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $earning['first_name'] . ' ' . $earning['last_name']; ?></td>
                                    <td><?php echo $earning['period_name']; ?></td>
                                    <td>₱<?php echo number_format($earning['base_pay'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['overtime_pay'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['holiday_pay'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['night_differential'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['incentives'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['bonuses'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['allowances'], 2); ?></td>
                                    <td>₱<?php echo number_format($earning['gross_pay'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($earning['calculation_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11">No earnings calculations found</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>Earnings Calculation System &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script>
        // Tab functionality
        function openTab(tabName) {
            var i, tabContent, tabButtons;

            // Hide all tab content
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].classList.remove("active");
            }

            // Remove active class from all buttons
            tabButtons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            // Show the specific tab content and add active class to the button
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }

        // Load employee details when selected
        function loadEmployeeDetails(employeeId) {
            if (!employeeId) {
                document.getElementById('employee-details').innerHTML = 'Select an employee to view details';
                return;
            }

            // In a real application, you would fetch this data via AJAX
            // For this example, we'll just show a placeholder
            document.getElementById('employee-details').innerHTML =
                '<div style="padding: 10px; background: #f9f9f9; border-radius: 4px;">' +
                'Employee details will be loaded here. This would include current pay rate, allowances, etc.' +
                '</div>';
        }

        // Simple form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let valid = true;
                this.querySelectorAll('input[required], select[required]').forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = 'red';
                    } else {
                        input.style.borderColor = '#ddd';
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>