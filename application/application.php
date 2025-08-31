<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "systems";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$success = false;
$duplicate_error = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // First check if email already exists
    $check_sql = "SELECT id FROM employees WHERE email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $duplicate_error = true;
        $error_message = "An employee with this email already exists in our system!";
    } else {
        // Proceed with insertion if not duplicate
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $hire_date = $_POST['hire_date'];
        $address = $_POST['address'];
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $marital_status = $_POST['marital_status'];
        $emergency_contact_name = $_POST['emergency_contact_name'];
        $emergency_contact_phone = $_POST['emergency_contact_phone'];

        // Handle file upload
        $document_path = '';
        if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Generate unique filename
            $file_ext = pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . uniqid() . '.' . $file_ext;

            if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
                $document_path = $target_file;
            } else {
                $error_message = "Error uploading file. Please try again.";
            }
        }

        $insert_sql = "INSERT INTO employees (first_name, last_name, email, phone, hire_date, 
                      address, date_of_birth, gender, marital_status, emergency_contact_name, 
                      emergency_contact_phone, document_path)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param(
            "ssssssssssss",
            $first_name,
            $last_name,
            $email,
            $phone,
            $hire_date,
            $address,
            $date_of_birth,
            $gender,
            $marital_status,
            $emergency_contact_name,
            $emergency_contact_phone,
            $document_path
        );

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Information System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        fieldset {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 25px;
            background-color: #f9f9f9;
        }

        legend {
            font-weight: bold;
            color: #2c3e50;
            padding: 0 10px;
            font-size: 1.2em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .field-error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            display: block;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            text-align: center;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            input[type="text"],
            input[type="email"],
            input[type="tel"],
            input[type="date"],
            select,
            textarea {
                padding: 10px;
            }

            .submit-btn {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Submit Employee Information</h1>

        <?php if ($success): ?>
            <div class="success-message">
                Employee information submitted successfully!
            </div>
        <?php endif; ?>

        <?php if ($duplicate_error || $error_message): ?>
            <div class="error-message">
                <?php echo $error_message ?: "An employee with this email already exists in our system!"; ?>
            </div>
        <?php endif; ?>

        <form id="employeeForm" method="POST" action="" enctype="multipart/form-data">
            <fieldset>
                <legend>Basic Information</legend>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                    <div class="field-error-message" id="first_name-error">This field is required</div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                    <div class="field-error-message" id="last_name-error">This field is required</div>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <div class="field-error-message" id="email-required">This field is required</div>
                    <div class="field-error-message" id="email-format">Please enter a valid email address</div>
                    <div class="field-error-message" id="email-duplicate">This email is already registered</div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="hire_date">Hire Date:</label>
                    <input type="date" id="hire_date" name="hire_date" required>
                    <div class="field-error-message" id="hire_date-error">This field is required</div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Personal Details</legend>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
                </div>

                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marital_status">Marital Status:</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">Select Status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="emergency_contact_name">Emergency Contact Name:</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name">
                </div>

                <div class="form-group">
                    <label for="emergency_contact_phone">Emergency Contact Phone:</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone">
                </div>
            </fieldset>

            <fieldset>
                <legend>Documents</legend>
                <div class="form-group">
                    <label for="document">Upload Document (CV/Resume):</label>
                    <input type="file" id="document" name="document" accept=".pdf,.doc,.docx">
                    <div class="field-error-message" id="document-error">Please upload a valid file (PDF or Word)</div>
                </div>
            </fieldset>

            <button type="submit" class="submit-btn">Submit Information</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('employeeForm');

            // Real-time validation
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const emailInput = document.getElementById('email');
            const hireDateInput = document.getElementById('hire_date');

            firstNameInput.addEventListener('blur', function() {
                validateRequiredField(firstNameInput, 'first_name-error');
            });

            lastNameInput.addEventListener('blur', function() {
                validateRequiredField(lastNameInput, 'last_name-error');
            });

            emailInput.addEventListener('blur', function() {
                validateEmail(emailInput);
            });

            hireDateInput.addEventListener('blur', function() {
                validateRequiredField(hireDateInput, 'hire_date-error');
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validate required fields
                if (!validateRequiredField(firstNameInput, 'first_name-error')) isValid = false;
                if (!validateRequiredField(lastNameInput, 'last_name-error')) isValid = false;
                if (!validateEmail(emailInput)) isValid = false;
                if (!validateRequiredField(hireDateInput, 'hire_date-error')) isValid = false;

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fix the errors in the form before submitting.');
                }
            });

            function validateRequiredField(field, errorId) {
                const errorElement = document.getElementById(errorId);
                if (!field.value.trim()) {
                    errorElement.style.display = 'block';
                    field.style.borderColor = '#e74c3c';
                    return false;
                } else {
                    errorElement.style.display = 'none';
                    field.style.borderColor = '#ddd';
                    return true;
                }
            }

            function validateEmail(emailField) {
                const emailRequiredError = document.getElementById('email-required');
                const emailFormatError = document.getElementById('email-format');
                const email = emailField.value.trim();

                if (!email) {
                    emailRequiredError.style.display = 'block';
                    emailFormatError.style.display = 'none';
                    emailField.style.borderColor = '#e74c3c';
                    return false;
                } else if (!isValidEmail(email)) {
                    emailRequiredError.style.display = 'none';
                    emailFormatError.style.display = 'block';
                    emailField.style.borderColor = '#e74c3c';
                    return false;
                } else {
                    emailRequiredError.style.display = 'none';
                    emailFormatError.style.display = 'none';
                    emailField.style.borderColor = '#ddd';
                    return true;
                }
            }

            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>