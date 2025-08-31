<?php
// Database configuration
$host = 'localhost';
$dbname = 'systems';
$username = 'root'; // Change if needed
$password = ''; // Change if needed

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_employee'])) {
        // Delete employee
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$_POST['employee_id']]);
    }
}

// Fetch all employees with salary information
$stmt = $pdo->query("
    SELECT e.*, 
           sa.base_salary, 
           ss.min_salary as grade_min, 
           ss.max_salary as grade_max,
           adj.adjustment_amount,
           adj.adjustment_type
    FROM employees e
    LEFT JOIN salary_assignments sa ON e.id = sa.employee_id
    LEFT JOIN salary_structures ss ON sa.salary_grade = ss.grade
    LEFT JOIN (
        SELECT employee_id, adjustment_amount, adjustment_type
        FROM salary_adjustments 
        WHERE effective_date = (SELECT MAX(effective_date) FROM salary_adjustments WHERE employee_id = salary_adjustments.employee_id)
    ) adj ON e.id = adj.employee_id
    ORDER BY e.last_name, e.first_name
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for dropdown
$deptStmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch positions for dropdown
$posStmt = $pdo->query("SELECT * FROM positions ORDER BY title");
$positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Attrition AI with Database</title>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        select,
        input,
        button,
        textarea {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background-color: #2980b9;
        }

        #output {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-top: 20px;
            border-radius: 0 4px 4px 0;
            white-space: pre-wrap;
        }

        .status-low {
            color: #27ae60;
            font-weight: bold;
        }

        .status-medium {
            color: #f39c12;
            font-weight: bold;
        }

        .status-high {
            color: #e74c3c;
            font-weight: bold;
        }

        .feature-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            background-color: #e0e0e0;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .tab.active {
            background-color: white;
            border-bottom: none;
            font-weight: bold;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .training-status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            background-color: #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .action-btn {
            padding: 5px 10px;
            width: auto;
            margin: 0 5px;
        }

        .btn-delete {
            background-color: #e74c3c;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .message {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
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

        .employee-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            display: block;
        }

        .prediction-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .prediction-high {
            border-left: 4px solid #e74c3c;
            background-color: #fdedec;
        }

        .prediction-medium {
            border-left: 4px solid #f39c12;
            background-color: #fef9e7;
        }

        .prediction-low {
            border-left: 4px solid #27ae60;
            background-color: #eafaf1;
        }
    </style>
</head>

<body>
    <h1>Predictive Attrition AI with Database</h1>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('predict')">Predict Attrition</div>
        <div class="tab" onclick="switchTab('database')">Employee Database</div>
        <div class="tab" onclick="switchTab('train')">Train Model</div>
    </div>

    <div id="predict-tab" class="tab-content active container">
        <h2>Predict Employee Attrition</h2>
        <label for="employee">Select Employee:</label>
        <select id="employee">
            <option value="">-- Select an employee --</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?php echo $emp['id']; ?>" data-salary="<?php echo $emp['base_salary'] ?? 0; ?>">
                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?>
                    <?php if (!empty($emp['base_salary'])): ?>
                        (₱<?php echo number_format($emp['base_salary'], 2); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button onclick="predictEmployee()">Predict Attrition</button>

        <div id="output">Select an employee to predict attrition risk.</div>
    </div>

    <div id="database-tab" class="tab-content container">
        <h2>Employee Database</h2>
        <div id="database-content">
            <?php if (count($employees) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Salary</th>
                            <th>Hire Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($emp['base_salary'])): ?>
                                        ₱<?php echo number_format($emp['base_salary'], 2); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $emp['hire_date']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" name="delete_employee" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No employees in the database.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="train-tab" class="tab-content container">
        <h2>Train Model</h2>
        <p>The model can be trained on the current employee database.</p>
        <button onclick="trainModel()">Train Model</button>
        <div id="training-status" class="training-status">Model is ready for predictions.</div>
    </div>

    <script>
        // Store employees data for JavaScript
        const employees = <?php echo json_encode($employees); ?>;
        let model;

        // Initialize the application
        const initializeApp = async () => {
            // Create the model structure
            model = createModel();

            // Try to load a previously saved model
            try {
                const savedModel = localStorage.getItem('attritionModel');
                if (savedModel) {
                    const modelInfo = JSON.parse(savedModel);
                    const weights = modelInfo.weights.map(w => tf.tensor(w.data, w.shape));
                    model.setWeights(weights);
                    document.getElementById('training-status').textContent = "Pre-trained model loaded successfully!";
                }
            } catch (e) {
                console.error("Error loading saved model:", e);
            }
        };

        // Create model architecture
        const createModel = () => {
            const model = tf.sequential();
            model.add(tf.layers.dense({
                units: 64,
                activation: 'relu',
                inputShape: [10]
            }));
            model.add(tf.layers.dense({
                units: 32,
                activation: 'relu'
            }));
            model.add(tf.layers.dense({
                units: 16,
                activation: 'relu'
            }));
            model.add(tf.layers.dense({
                units: 3,
                activation: 'softmax'
            }));

            model.compile({
                optimizer: 'adam',
                loss: 'categoricalCrossentropy',
                metrics: ['accuracy']
            });

            return model;
        };

        // Switch between tabs
        const switchTab = (tabName) => {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            const tabIndex = {
                'predict': 1,
                'database': 2,
                'train': 3
            } [tabName];

            document.querySelector(`.tab:nth-child(${tabIndex})`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        };

        // Calculate tenure in months from hire date
        const calculateTenure = (hireDate) => {
            const today = new Date();
            const hire = new Date(hireDate);
            const months = (today.getFullYear() - hire.getFullYear()) * 12;
            return months + (today.getMonth() - hire.getMonth());
        };

        // Calculate age from date of birth
        const calculateAge = (dob) => {
            if (!dob) return 30; // Default age if not provided
            const today = new Date();
            const birthDate = new Date(dob);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        };

        // Generate features for employees based on database information
        const generateEmployeeFeatures = (employee) => {
            const age = calculateAge(employee.date_of_birth);
            const tenure = calculateTenure(employee.hire_date);
            const baseSalary = employee.base_salary || 0;

            // Calculate salary competitiveness (0-1)
            let salaryCompetitiveness = 0.5; // Default
            if (employee.grade_min && employee.grade_max && baseSalary) {
                const range = employee.grade_max - employee.grade_min;
                const positionInRange = (baseSalary - employee.grade_min) / range;
                salaryCompetitiveness = Math.max(0, Math.min(1, positionInRange));
            }

            // For demo purposes, generate synthetic data for missing fields
            return {
                age: age,
                tenure: tenure,
                baseSalary: baseSalary,
                salaryCompetitiveness: salaryCompetitiveness,
                recentRaise: employee.adjustment_amount ? 1 : 0, // 1 if had recent raise
                raiseType: employee.adjustment_type === 'Promotion' ? 1 : 0, // 1 if promotion
                projects: Math.floor(1 + Math.random() * 5), // Synthetic projects
                satisfaction: Math.floor(50 + Math.random() * 50), // Synthetic satisfaction
                hours: Math.floor(160 + Math.random() * 80), // Synthetic work hours
                workLifeBalance: Math.floor(60 + Math.random() * 40) // Synthetic work-life balance
            };
        };

        // Format currency in Philippine Peso
        const formatPeso = (amount) => {
            return '₱' + amount.toLocaleString('en-PH', {
                maximumFractionDigits: 2
            });
        };

        // Train the model with employee data
        const trainModel = async () => {
            const statusElement = document.getElementById('training-status');
            statusElement.textContent = "Training model...";

            try {
                // Prepare training data
                const features = employees.map(emp => {
                    const empFeatures = generateEmployeeFeatures(emp);
                    return [
                        empFeatures.age / 100,
                        empFeatures.tenure / 100,
                        empFeatures.baseSalary / 500000, // Normalize salary
                        empFeatures.salaryCompetitiveness,
                        empFeatures.recentRaise,
                        empFeatures.raiseType,
                        empFeatures.projects / 10,
                        empFeatures.satisfaction / 100,
                        empFeatures.hours / 250,
                        empFeatures.workLifeBalance / 100
                    ];
                });

                // For demo purposes, we'll generate synthetic labels since we don't have real attrition data
                // In a real application, you would have historical attrition data
                const labels = employees.map(emp => {
                    const empFeatures = generateEmployeeFeatures(emp);

                    // Synthetic attrition probability based on features
                    let stayProb = 0.6; // Base probability of staying
                    let resignProb = 0.2; // Base probability of resigning
                    let leaveProb = 0.2; // Base probability of taking leave

                    // Adjust probabilities based on features
                    if (empFeatures.salaryCompetitiveness < 0.3) {
                        stayProb -= 0.3;
                        resignProb += 0.25;
                        leaveProb += 0.05;
                    }

                    if (empFeatures.tenure < 12) {
                        stayProb -= 0.1;
                        resignProb += 0.08;
                        leaveProb += 0.02;
                    }

                    if (empFeatures.satisfaction < 40) {
                        stayProb -= 0.2;
                        resignProb += 0.15;
                        leaveProb += 0.05;
                    }

                    if (empFeatures.recentRaise) {
                        stayProb += 0.15;
                        resignProb -= 0.1;
                        leaveProb -= 0.05;
                    }

                    // Ensure probabilities sum to 1
                    const total = stayProb + resignProb + leaveProb;
                    stayProb /= total;
                    resignProb /= total;
                    leaveProb /= total;

                    return [stayProb, resignProb, leaveProb];
                });

                // Convert to tensors
                const featureTensor = tf.tensor2d(features);
                const labelTensor = tf.tensor2d(labels);

                // Train the model
                await model.fit(featureTensor, labelTensor, {
                    epochs: 150,
                    batchSize: 4,
                    shuffle: true,
                    validationSplit: 0.2,
                    callbacks: {
                        onEpochEnd: (epoch, logs) => {
                            statusElement.textContent = `Training... Epoch ${epoch + 1}/150 - Loss: ${logs.loss.toFixed(4)}`;
                        }
                    }
                });

                statusElement.textContent = "Model training completed!";

                // Save model to localStorage
                const weights = await model.getWeights();
                const weightData = await Promise.all(weights.map(async w => {
                    return {
                        data: await w.data(),
                        shape: w.shape
                    };
                }));

                localStorage.setItem('attritionModel', JSON.stringify({
                    weights: weightData
                }));

                // Clean up tensors
                featureTensor.dispose();
                labelTensor.dispose();
            } catch (error) {
                console.error('Error training model:', error);
                statusElement.textContent = "Error training model. Please check console for details.";
            }
        };

        // Predict attrition for selected employee
        const predictEmployee = async () => {
            const employeeId = document.getElementById('employee').value;

            if (!employeeId) {
                alert("Please select an employee first.");
                return;
            }

            if (!model) {
                alert("Model is not trained yet. Please train the model first.");
                return;
            }

            const employee = employees.find(emp => emp.id == employeeId);

            if (!employee) {
                alert("Employee not found.");
                return;
            }

            // Generate features for the employee
            const empFeatures = generateEmployeeFeatures(employee);

            // Prepare input data
            const input = tf.tensor2d([
                [
                    empFeatures.age / 100,
                    empFeatures.tenure / 100,
                    empFeatures.baseSalary / 500000, // Normalize salary
                    empFeatures.salaryCompetitiveness,
                    empFeatures.recentRaise,
                    empFeatures.raiseType,
                    empFeatures.projects / 10,
                    empFeatures.satisfaction / 100,
                    empFeatures.hours / 250,
                    empFeatures.workLifeBalance / 100
                ]
            ]);

            // Make prediction
            const prediction = model.predict(input);
            const probs = await prediction.data();

            // Get the predicted class
            const classes = ['Stay and Work', 'Resign', 'Take Leave'];
            const maxIndex = probs.indexOf(Math.max(...probs));
            const predictedClass = classes[maxIndex];
            const confidence = probs[maxIndex];

            let statusClass, suggestions;

            if (predictedClass === 'Stay and Work') {
                statusClass = "prediction-low";
                suggestions = [
                    "Continue recognizing achievements and maintaining positive work environment",
                    "Consider career development opportunities to retain this employee long-term",
                    "Monitor work-life balance to prevent future burnout"
                ];
            } else if (predictedClass === 'Resign') {
                statusClass = "prediction-high";
                suggestions = [
                    "Schedule one-on-one meeting to understand concerns",
                    "Review compensation and benefits package for competitiveness",
                    "Consider promotion or special projects to increase engagement"
                ];
            } else {
                statusClass = "prediction-medium";
                suggestions = [
                    "Check if employee is experiencing burnout or personal issues",
                    "Review workload and consider temporary adjustments",
                    "Discuss flexible work arrangements if appropriate"
                ];
            }

            // Format the output
            document.getElementById('output').innerHTML = `
        <div class="prediction-result ${statusClass}">
          <h3>Prediction Results for ${employee.first_name} ${employee.last_name}</h3>
          <p><strong>Predicted Action:</strong> ${predictedClass}</p>
          <p><strong>Confidence:</strong> ${(confidence * 100).toFixed(1)}%</p>
          
          <h4>Probability Distribution:</h4>
          <ul>
            <li>Stay and Work: ${(probs[0] * 100).toFixed(1)}%</li>
            <li>Resign: ${(probs[1] * 100).toFixed(1)}%</li>
            <li>Take Leave: ${(probs[2] * 100).toFixed(1)}%</li>
          </ul>
          
          <h4>Recommended Actions:</h4>
          <ul>
            ${suggestions.map(s => `<li>${s}</li>`).join('')}
          </ul>
        </div>
        
        <div class="employee-details">
          <div>
            <div class="detail-item">
              <span class="detail-label">Age:</span> ${empFeatures.age}
            </div>
            <div class="detail-item">
              <span class="detail-label">Tenure:</span> ${empFeatures.tenure} months
            </div>
            <div class="detail-item">
              <span class="detail-label">Base Salary:</span> ${formatPeso(empFeatures.baseSalary)}
            </div>
            <div class="detail-item">
              <span class="detail-label">Salary Competitiveness:</span> ${(empFeatures.salaryCompetitiveness * 100).toFixed(1)}%
            </div>
            <div class="detail-item">
              <span class="detail-label">Recent Raise:</span> ${empFeatures.recentRaise ? 'Yes' : 'No'}
            </div>
          </div>
          <div>
            <div class="detail-item">
              <span class="detail-label">Raise Type:</span> ${employee.adjustment_type || 'None'}
            </div>
            <div class="detail-item">
              <span class="detail-label">Satisfaction:</span> ${empFeatures.satisfaction}/100
            </div>
            <div class="detail-item">
              <span class="detail-label">Work Hours:</span> ${empFeatures.hours}/month
            </div>
            <div class="detail-item">
              <span class="detail-label">Work-Life Balance:</span> ${empFeatures.workLifeBalance}/100
            </div>
            <div class="detail-item">
              <span class="detail-label">Department:</span> ${employee.department || 'N/A'}
            </div>
          </div>
        </div>
      `;

            input.dispose();
            prediction.dispose();
        };

        // Start the application
        initializeApp();
    </script>
</body>

</html>