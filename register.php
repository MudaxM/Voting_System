<?php
require_once 'includes/config.php';
require_once 'includes/functions.php'; // Make sure functions.php is included
redirectIfLoggedIn();

$errors = [];
$success = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'student_id' => sanitize($_POST['student_id'] ?? ''),
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => strtolower(sanitize($_POST['email'] ?? '')),
        'password' => trim($_POST['password'] ?? ''),
        'confirm_password' => trim($_POST['confirm_password'] ?? ''),
        'department' => sanitize($_POST['department'] ?? ''),
        'year' => intval($_POST['year'] ?? 0)
    ];
    // Validate student ID
    $id_validation = validateStudentID($form_data['student_id']);
    // Fix: Check if it's an array before accessing
    if (is_array($id_validation) && isset($id_validation['valid'])) {
        if (!$id_validation['valid']) {
            $errors[] = $id_validation['message'] ?? 'Invalid Student ID';
        } else {
            $form_data['student_id'] = $id_validation['formatted_id'] ?? $form_data['student_id'];
        }
    } else {
        // If validateStudentID doesn't return an array, there's a problem with the function
        $errors[] = 'Student ID validation failed. Please try again.';
    }
    
    // Only proceed if no student ID errors
    if (empty($errors)) {
        $result = registerUser($pdo, $form_data); 
        if ($result['success']) {
            $success = $result['message'];
            $form_data = []; // Clear form on success
        } else {
            // Fix: Ensure $result['errors'] is an array
            $errors = is_array($result['errors'] ?? null) ? $result['errors'] : ['Registration failed. Please try again.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Union Voting System</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <style>
    /* Temporary fix for visible issues */
    .password-toggle {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .year-option {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 12px !important;
    }
    
    .form-hint {
        margin-top: 8px !important;
        line-height: 1.5 !important;
    }
    
    .password-container {
        margin-bottom: 8px !important;
    }
    
    #passwordMatch {
        margin-top: 8px !important;
        font-size: 14px !important;
    }
</style>
    <div class="form-container">
        <div class="form-box">
            <div class="form-header">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <h2>Create Account</h2>
                <p>Register to participate in Student Union Elections</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Registration Failed</strong>
                    <ul style="margin: 5px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong>
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="registrationForm" novalidate>
                <div class="form-group">
                    <label for="student_id" class="form-label">
                        Student ID <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="student_id" 
                           name="student_id" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($form_data['student_id'] ?? ''); ?>"
                           required>
                    <div class="error-message" id="studentIdError"></div>
                    
                    <div class="form-hint">
                        <strong>Examples:</strong> NSR/123/12, CS-2023-101, ENG.456.21
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name" class="form-label">
                        Full Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>"
                           required>
                    <div class="error-message" id="fullNameError"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        Email Address <span class="required">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                           required>
                    <div class="error-message" id="emailError"></div>
                    <div class="form-hint">Use your university email address (.edu, .ac.in, etc.)</div>
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">
                        Department <span class="required">*</span>
                    </label>
                    <select id="department" name="department" class="form-control form-select" required>
                        <option value="">Select Department</option>
                        <?php
                        $departments = [
                            'Software Engineering',
                            'Electrical Engineering', 
                            'Mechanical Engineering',
                            'Civil Engineering',
                            'Information Technology',
                            'Computer Science',
                            'Metrology and Hydrology',
                            'Architecture and Urban Planning',
                            'Water Supply',
                            'Service'
                        ];
                        
                        foreach ($departments as $dept) {
                            $selected = (isset($form_data['department']) && $form_data['department'] == $dept) ? 'selected' : '';
                            echo "<option value=\"$dept\" $selected>$dept</option>";
                        }
                        ?>
                    </select>
                    <div class="error-message" id="departmentError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Year of Study <span class="required">*</span>
                    </label>
                    <div class="year-options" id="yearOptions">
                        <?php for ($i = 1; $i <= 5; $i++): 
                            $isSelected = (isset($form_data['year']) && $form_data['year'] == $i);
                        ?>
                        <label class="year-option <?php echo $isSelected ? 'selected' : ''; ?>">
                            <input type="radio" 
                                   name="year" 
                                   value="<?php echo $i; ?>" 
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   class="year-radio"
                                   required>
                            Year <?php echo $i; ?>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div class="error-message" id="yearError"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               required
                               minlength="8">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="error-message" id="passwordError"></div>
                    <div class="form-hint">Minimum 8 characters with letters and numbers</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="password-container">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               required
                               minlength="8">
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                    <div id="passwordMatch" class="form-hint"></div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" 
                               id="terms" 
                               name="terms" 
                               class="form-check-input" 
                               required
                               <?php echo (isset($_POST['terms']) ? 'checked' : ''); ?>>
                        <label for="terms" class="form-check-label">
                            I agree to the <a href="#" onclick="showTerms(event)">Terms and Conditions</a> 
                            and <a href="#" onclick="showPrivacy(event)">Privacy Policy</a>
                        </label>
                        <div class="error-message" id="termsError"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>

                <div class="form-footer">
                    <p>Already have an account? <a href="login.php" class="form-link">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Terms and Conditions</h3>
                <button class="modal-close" onclick="closeModal('termsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>Student Union Voting System Terms</h4>
                <p><strong>1. Eligibility:</strong> Only registered students of the university are eligible to vote.</p>
                <p><strong>2. One Vote Per Position:</strong> Each voter can cast only one vote per position.</p>
                <p><strong>3. No Vote Selling/Buying:</strong> Any attempt to buy or sell votes will result in disqualification.</p>
                <p><strong>4. Identity Verification:</strong> Your student ID will be verified before your vote is counted.</p>
                <p><strong>5. No Tampering:</strong> Any attempt to tamper with the voting system will be reported to university authorities.</p>
                <p><strong>6. Decision Final:</strong> The election committee's decision regarding any dispute is final.</p>
                <p><strong>7. Data Privacy:</strong> Your personal information will be protected and only used for election purposes.</p>
                <p><strong>8. Fair Use:</strong> The voting system should be used fairly and ethically.</p>
                <p>By registering, you agree to abide by these terms and conditions.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal('termsModal')">I Understand</button>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Privacy Policy</h3>
                <button class="modal-close" onclick="closeModal('privacyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>Data Protection Policy</h4>
                <p><strong>1. Data Collection:</strong> We collect your student ID, name, email, department, and year for verification purposes.</p>
                <p><strong>2. Data Usage:</strong> Your data is used exclusively for election administration and verification.</p>
                <p><strong>3. Data Protection:</strong> All personal data is encrypted and stored securely.</p>
                <p><strong>4. Vote Anonymity:</strong> Your vote is anonymous and cannot be traced back to you.</p>
                <p><strong>5. No Third-Party Sharing:</strong> We do not share your personal data with third parties.</p>
                <p><strong>6. Data Retention:</strong> Election data is retained for one academic year for audit purposes.</p>
                <p><strong>7. Your Rights:</strong> You have the right to request deletion of your data after the election period.</p>
                <p><strong>8. Security Measures:</strong> We implement industry-standard security measures to protect your data.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal('privacyModal')">I Understand</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize form data persistence
        const form = document.getElementById('registrationForm');
        const formFields = ['student_id', 'full_name', 'email', 'department', 'year'];
        
        // Save data to localStorage
        formFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (field.type === 'radio') {
                    document.querySelectorAll(`[name="${fieldName}"]`).forEach(radio => {
                        radio.addEventListener('change', function() {
                            localStorage.setItem(fieldName, this.value);
                        });
                    });
                } else {
                    field.addEventListener('input', function() {
                        localStorage.setItem(fieldName, this.value);
                    });
                }
            }
        });
        
        // Load saved data on page load
        document.addEventListener('DOMContentLoaded', function() {
            formFields.forEach(fieldName => {
                const savedValue = localStorage.getItem(fieldName);
                if (savedValue !== null) {
                    if (fieldName === 'year') {
                        const radio = document.querySelector(`[name="${fieldName}"][value="${savedValue}"]`);
                        if (radio) {
                            radio.checked = true;
                            radio.parentElement.classList.add('selected');
                        }
                    } else {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field) {
                            field.value = savedValue;
                        }
                    }
                }
            });
            
            // Check terms
            if (localStorage.getItem('terms') === 'true') {
                document.getElementById('terms').checked = true;
            }
        });
        
        // Student ID validation
        function isValidStudentID(id) {
            if (!id || id.trim().length < 5 || id.trim().length > 30) return false;
            
            const value = id.trim();
            if (!/[A-Za-z]/.test(value) || !/\d/.test(value)) return false;
            if (!/^[A-Za-z0-9\/\-_\.]+$/.test(value)) return false;
            if (/^[\/\-_\.]/.test(value) || /[\/\-_\.]$/.test(value)) return false;
            if (/[\/\-_\.]{2,}/.test(value)) return false;
            
            return true;
        }
        
        // Email validation
        function isEducationalEmail(email) {
            if (!email) return false;
            const domain = email.substring(email.lastIndexOf('@') + 1).toLowerCase();
            const eduPatterns = [
                /\.edu$/, /\.ac\.[a-z]{2,}$/, /\.edu\.[a-z]{2,}$/,
                /\.school$/, /\.college$/, /\.university$/, /\.institute$/,
            ];
            return eduPatterns.some(pattern => pattern.test(domain));
        }
        
        // Password validation
        function isValidPassword(password) {
            return password.length >= 8 && /[A-Za-z]/.test(password) && /\d/.test(password);
        }
        
        // Form validation
        function validateForm(event) {
            if (event) event.preventDefault();
            
            let isValid = true;
            clearErrors();
            
            // Student ID
            const studentId = document.getElementById('student_id').value.trim();
            if (!studentId) {
                showError('student_id', 'Student ID is required', 'studentIdError');
                isValid = false;
            } else if (!isValidStudentID(studentId)) {
                showError('student_id', 'Invalid format. Examples: NSR/123/12, CS-2023-101', 'studentIdError');
                isValid = false;
            }
            
            // Full name
            const fullName = document.getElementById('full_name').value.trim();
            if (!fullName) {
                showError('full_name', 'Full name is required', 'fullNameError');
                isValid = false;
            } else if (fullName.length < 3) {
                showError('full_name', 'Must be at least 3 characters', 'fullNameError');
                isValid = false;
            }
            
            // Email
            const email = document.getElementById('email').value.trim();
            if (!email) {
                showError('email', 'Email is required', 'emailError');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', 'Invalid email address', 'emailError');
                isValid = false;
            } else if (!isEducationalEmail(email)) {
                showError('email', 'Only educational emails allowed (.edu, .ac.in)', 'emailError');
                isValid = false;
            }
            
            // Department
            const department = document.getElementById('department').value;
            if (!department) {
                showError('department', 'Please select a department', 'departmentError');
                isValid = false;
            }
            
            // Year
            const yearSelected = document.querySelector('input[name="year"]:checked');
            if (!yearSelected) {
                showError('year', 'Please select year of study', 'yearError');
                document.getElementById('yearOptions').style.border = '2px solid #f43f5e';
                document.getElementById('yearOptions').style.borderRadius = '8px';
                document.getElementById('yearOptions').style.padding = '5px';
                isValid = false;
            }
            
            // Password
            const password = document.getElementById('password').value;
            if (!password) {
                showError('password', 'Password is required', 'passwordError');
                isValid = false;
            } else if (!isValidPassword(password)) {
                showError('password', '8+ chars with letters and numbers', 'passwordError');
                isValid = false;
            }
            
            // Confirm password
            const confirmPassword = document.getElementById('confirm_password').value;
            if (!confirmPassword) {
                showError('confirm_password', 'Please confirm password', 'confirmPasswordError');
                isValid = false;
            } else if (password !== confirmPassword) {
                showError('confirm_password', 'Passwords do not match', 'confirmPasswordError');
                isValid = false;
            }
            
            // Terms
            if (!document.getElementById('terms').checked) {
                showError('terms', 'You must agree to terms', 'termsError');
                isValid = false;
            }
            
            if (isValid) {
                localStorage.setItem('terms', 'true');
                form.submit();
            } else {
                const firstError = document.querySelector('.form-control.is-invalid');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return false;
        }
        
        // Helper functions
        function showError(fieldId, message, errorId) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(errorId);
            
            if (field) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
            }
            
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }
        
        function clearErrors() {
            document.querySelectorAll('.form-control').forEach(field => {
                field.classList.remove('is-invalid', 'is-valid');
            });
            
            document.querySelectorAll('.error-message').forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });
            
            const yearOptions = document.getElementById('yearOptions');
            if (yearOptions) {
                yearOptions.style.border = 'none';
                yearOptions.style.padding = '0';
            }
        }
        
        // Real-time validation
        document.getElementById('student_id').addEventListener('blur', function() {
            if (this.value.trim() && !isValidStudentID(this.value)) {
                showError('student_id', 'Invalid format. Examples: NSR/123/12', 'studentIdError');
            } else if (this.value.trim()) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                document.getElementById('studentIdError').style.display = 'none';
            }
        });
        
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !isEducationalEmail(email)) {
                showError('email', 'Only educational emails allowed', 'emailError');
            } else if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', 'Invalid email address', 'emailError');
            } else if (email) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                document.getElementById('emailError').style.display = 'none';
            }
        });
        
        // Password strength
        function checkPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            return Math.min(score, 4);
        }
        
        document.getElementById('password').addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            const strengthDiv = document.getElementById('passwordStrength');
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            strengthDiv.textContent = `Strength: ${labels[strength]}`;
            strengthDiv.className = `password-strength strength-${strength}`;
            strengthDiv.style.display = this.value ? 'block' : 'none';
            
            // Check match
            const confirmPass = document.getElementById('confirm_password').value;
            if (confirmPass && this.value !== confirmPass) {
                document.getElementById('passwordMatch').textContent = 'Passwords do not match ✗';
                document.getElementById('passwordMatch').style.color = '#f43f5e';
            } else if (confirmPass) {
                document.getElementById('passwordMatch').textContent = 'Passwords match ✓';
                document.getElementById('passwordMatch').style.color = '#10b981';
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value && password && this.value !== password) {
                document.getElementById('passwordMatch').textContent = 'Passwords do not match ✗';
                document.getElementById('passwordMatch').style.color = '#f43f5e';
            } else if (this.value && password) {
                document.getElementById('passwordMatch').textContent = 'Passwords match ✓';
                document.getElementById('passwordMatch').style.color = '#10b981';
            }
        });
        
        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirm_password');
            const type = confirmPassword.type === 'password' ? 'text' : 'password';
            confirmPassword.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Year selection
        document.querySelectorAll('.year-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.year-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input').checked = true;
                
                // Clear error
                document.getElementById('yearError').style.display = 'none';
                document.getElementById('yearOptions').style.border = 'none';
                document.getElementById('yearOptions').style.padding = '0';
            });
        });
        
        // Modal functions
        function showTerms(event) {
            event.preventDefault();
            document.getElementById('termsModal').style.display = 'flex';
        }
        
        function showPrivacy(event) {
            event.preventDefault();
            document.getElementById('privacyModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Submit form on enter
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'textarea') {
                e.preventDefault();
            }
        });
        
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Clear localStorage on successful registration
        <?php if (!empty($success)): ?>
        localStorage.clear();
        <?php endif; ?>
    </script>
</body>
</html>