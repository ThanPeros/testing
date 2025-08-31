document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('employeeForm');
    const emailField = document.getElementById('email');
    
    // Required fields with their error messages
    const requiredFields = {
        'first_name': 'first_name-error',
        'last_name': 'last_name-error',
        'email': 'email-required',
        'position': 'position-error',
        'hire_date': 'hire_date-error'
    };
    
    // Validate email format
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Check if email exists (AJAX)
    function checkEmailExists(email) {
        if (!validateEmail(email)) {
            document.getElementById('email-format').style.display = 'block';
            document.getElementById('email-duplicate').style.display = 'none';
            return;
        }
        
        fetch('check_email.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    emailField.classList.add('duplicate-field');
                    document.getElementById('email-duplicate').style.display = 'block';
                    document.getElementById('email-format').style.display = 'none';
                } else {
                    emailField.classList.remove('duplicate-field');
                    document.getElementById('email-duplicate').style.display = 'none';
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Real-time validation
    emailField.addEventListener('blur', function() {
        if (this.value.trim()) {
            if (!validateEmail(this.value)) {
                this.classList.add('invalid-field');
                document.getElementById('email-format').style.display = 'block';
            } else {
                this.classList.remove('invalid-field');
                document.getElementById('email-format').style.display = 'none';
                checkEmailExists(this.value);
            }
        }
    });
    
    // Add blur event for all required fields
    Object.keys(requiredFields).forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const errorId = requiredFields[fieldId];
        
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('missing-field');
                document.getElementById(errorId).style.display = 'block';
            } else {
                this.classList.remove('missing-field');
                document.getElementById(errorId).style.display = 'none';
            }
        });
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        Object.keys(requiredFields).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.classList.add('missing-field');
                document.getElementById(requiredFields[fieldId]).style.display = 'block';
                isValid = false;
            }
        });
        
        // Validate email format
        if (emailField.value.trim() && !validateEmail(emailField.value)) {
            emailField.classList.add('invalid-field');
            document.getElementById('email-format').style.display = 'block';
            isValid = false;
        }
        
        // Check for duplicate email
        if (emailField.classList.contains('duplicate-field')) {
            isValid = false;
        }
        
        // Validate file upload if present
        const fileInput = document.getElementById('document');
        if (fileInput.files.length > 0) {
            const allowedTypes = ['application/pdf', 'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const file = fileInput.files[0];
            
            if (!allowedTypes.includes(file.type)) {
                document.getElementById('document-error').style.display = 'block';
                isValid = false;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB
                document.getElementById('document-error').textContent = 'File size should not exceed 5MB';
                document.getElementById('document-error').style.display = 'block';
                isValid = false;
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please correct the errors in the form before submitting.');
        }
    });
    
    // Clear success message after 5 seconds
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 5000);
    }
});