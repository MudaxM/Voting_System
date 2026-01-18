// Main JavaScript file for the voting system

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSystem();
    setupEventListeners();
    checkSession();
});

// System Initialization
function initializeSystem() {
    // Set current year in footer
    const yearElements = document.querySelectorAll('.current-year');
    yearElements.forEach(el => {
        el.textContent = new Date().getFullYear();
    });
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize modals
    initializeModals();
    
    // Check for notifications
    checkNotifications();
}

// Setup Event Listeners
function setupEventListeners() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navLinks && navLinks.classList.contains('active') && 
            !event.target.closest('.nav-links') && 
            !event.target.closest('.menu-toggle')) {
            navLinks.classList.remove('active');
            if (menuToggle) menuToggle.classList.remove('active');
        }
    });
    
    // Password visibility toggle
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? 
                '<i class="fas fa-eye"></i>' : 
                '<i class="fas fa-eye-slash"></i>';
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Please fill all required fields correctly.', 'error');
            }
        });
    });
    
    // Auto-save forms
    setupAutoSave();
    
    // Handle AJAX forms
    setupAjaxForms();
}

// Check user session
function checkSession() {
    const sessionWarning = document.getElementById('sessionWarning');
    if (sessionWarning) {
        let warningShown = false;
        
        // Check session every minute
        setInterval(() => {
            fetch('api/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.session_expiring && !warningShown) {
                        showSessionWarning();
                        warningShown = true;
                    }
                });
        }, 60000);
    }
}

// Session warning
function showSessionWarning() {
    const warning = document.createElement('div');
    warning.className = 'session-warning';
    warning.innerHTML = `
        <div class="session-warning-content">
            <i class="fas fa-clock"></i>
            <div>
                <strong>Session Expiring Soon</strong>
                <p>Your session will expire in 5 minutes. Save your work.</p>
            </div>
            <button class="btn btn-primary" onclick="extendSession()">Extend Session</button>
        </div>
    `;
    
    document.body.appendChild(warning);
    
    setTimeout(() => {
        warning.classList.add('show');
    }, 100);
}

// Extend session
function extendSession() {
    fetch('api/extend_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.session-warning').classList.remove('show');
                setTimeout(() => {
                    document.querySelector('.session-warning').remove();
                }, 300);
                showNotification('Session extended successfully.', 'success');
            }
        });
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear all previous validations
    clearAllValidations(form);
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        
        if (!value) {
            markInvalid(field, 'This field is required');
            isValid = false;
            return; // Skip further validation for empty field
        }
        
        // Additional validation based on field type/name
        if (field.type === 'email') {
            if (!isValidEmail(value)) {
                markInvalid(field, 'Please enter a valid email address');
                isValid = false;
            } else if (!isEducationalEmail(value)) {
                markInvalid(field, 'Only educational email addresses are allowed (.edu, .ac.in, etc.)');
                isValid = false;
            } else {
                markValid(field);
            }
        }
        
        else if (field.type === 'password') {
            if (value.length < 8) {
                markInvalid(field, 'Password must be at least 8 characters');
                isValid = false;
            } else if (field.name === 'confirm_password') {
                // Check password confirmation
                const passwordField = form.querySelector('input[name="password"]');
                if (passwordField && value !== passwordField.value.trim()) {
                    markInvalid(field, 'Passwords do not match');
                    isValid = false;
                } else {
                    markValid(field);
                }
            } else {
                markValid(field);
            }
        }
        
        else if (field.name === 'student_id') {
            // Student ID validation - FIX FOR YOUR ISSUE
            if (!isValidStudentID(value)) {
                markInvalid(field, 'Invalid Student ID format. Use: ABC/123/45 or ABC/D/123/45');
                isValid = false;
            } else {
                markValid(field);
            }
        }
        
        else {
            markValid(field);
        }
    });
    
    return isValid;
}
// Fixed student ID validation - MORE FLEXIBLE
function isValidStudentID(id) {
    // Trim and convert to uppercase for consistency
    const cleanedId = id.trim().toUpperCase();
    
    // If empty, return false
    if (!cleanedId) return false;
    
    // Multiple acceptable patterns
    const patterns = [
        // Format: ABC/123/45 or ABC/T/123/45 (your original format)
        /^[A-Z]{2,4}(\/[A-Z]{1,2})?\/\d+\/\d+$/,
        
        // Format: ABC12345 or ABC2023001
        /^[A-Z]{2,4}\d{5,9}$/,
        
        // Format: 2023ABC001 or 23BCA001
        /^\d{2,4}[A-Z]{2,4}\d{3,5}$/,
        
        // Format: ABC-2023-001 or ABC_23_001
        /^[A-Z]{2,4}[-\_]\d{2,4}[-\_]\d{3,5}$/,
        
        // Format: ABC23CS001 (mixed letters and numbers)
        /^[A-Z0-9]{8,12}$/,
        
        // Format with batch: BATCH2023CS001
        /^(BATCH|BTCH|B)\d{4}[A-Z]{2,4}\d{3,5}$/i,
        
        // Simple format: minimum 6 characters, alphanumeric
        /^[A-Z0-9\/\-\.\_]{6,20}$/i
    ];
    
    // Try each pattern
    for (const pattern of patterns) {
        if (pattern.test(cleanedId)) {
            return true;
        }
    }
    
    return false;
}

function isEducationalEmail(email) {
    const eduPatterns = [
        /\.edu$/i,
        /\.ac\.[a-z]{2,}$/i,
        /\.edu\.[a-z]{2,}$/i,
        /\.school$/i,
        /\.college$/i,
        /\.university$/i,
        /\.institute$/i
    ];
    
    const domain = email.substring(email.lastIndexOf('@') + 1);
    
    for (const pattern of eduPatterns) {
        if (pattern.test(domain)) {
            return true;
        }
    }
    
    return false;
}

function isValidEmail(email) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

function markInvalid(field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    // Show error message
    let errorDiv = field.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    }
    errorDiv.textContent = message;
}

function markValid(field) {
    field.classList.add('is-valid');
    field.classList.remove('is-invalid');
    
    // Remove error message if exists
    const errorDiv = field.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.remove();
    }
}

function clearAllValidations(form) {
    const fields = form.querySelectorAll('.form-control');
    fields.forEach(field => {
        field.classList.remove('is-invalid', 'is-valid');
        const errorDiv = field.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
            errorDiv.remove();
        }
    });
}

// Mark field as invalid
function markInvalid(field, message) {
    field.classList.add('invalid');
    field.classList.remove('valid');
    
    let errorMsg = field.parentElement.querySelector('.error-message');
    if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        field.parentElement.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
    errorMsg.style.display = 'block';
}

// Mark field as valid
function markValid(field) {
    field.classList.add('valid');
    field.classList.remove('invalid');
    
    const errorMsg = field.parentElement.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.style.display = 'none';
    }
}

// Email validation
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Password strength checker
function checkPasswordStrength(password) {
    let score = 0;
    const strength = {
        0: "Very Weak",
        1: "Weak", 
        2: "Fair",
        3: "Good",
        4: "Strong"
    };
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    // Cap score at 4
    score = Math.min(score, 4);
    
    return {
        score: score,
        text: strength[score],
        color: score === 0 ? '#ef4444' : 
               score === 1 ? '#f97316' : 
               score === 2 ? '#eab308' : 
               score === 3 ? '#22c55e' : 
               '#16a34a'
    };
}

// Initialize tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            this.tooltipElement = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltipElement) {
                this.tooltipElement.remove();
                this.tooltipElement = null;
            }
        });
    });
}

// Initialize modals
function initializeModals() {
    // Modal open buttons
    document.querySelectorAll('[data-modal]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Modal close buttons
    document.querySelectorAll('.modal-close, .modal .btn-close').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    });
}

// Show notification
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                             type === 'error' ? 'exclamation-circle' : 
                             type === 'warning' ? 'exclamation-triangle' : 
                             'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);
    }
    
    return notification;
}

// Check for notifications from server
function checkNotifications() {
    // Check for session notifications
    if (sessionStorage.getItem('showNotification')) {
        const notification = JSON.parse(sessionStorage.getItem('showNotification'));
        showNotification(notification.message, notification.type);
        sessionStorage.removeItem('showNotification');
    }
    
    // Check URL for notification parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('notification')) {
        const message = decodeURIComponent(urlParams.get('notification'));
        const type = urlParams.get('type') || 'info';
        showNotification(message, type);
        
        // Remove from URL
        const url = new URL(window.location);
        url.searchParams.delete('notification');
        url.searchParams.delete('type');
        window.history.replaceState({}, '', url);
    }
}

// Setup auto-save for forms
function setupAutoSave() {
    const autosaveForms = document.querySelectorAll('.autosave-form');
    autosaveForms.forEach(form => {
        let saveTimeout;
        
        form.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveFormDraft(form);
            }, 2000);
        });
        
        // Load draft on page load
        loadFormDraft(form);
    });
}

// Save form draft
function saveFormDraft(form) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    const draftId = form.id || 'form-' + form.getAttribute('name') || 'draft';
    localStorage.setItem(draftId, JSON.stringify({
        data: data,
        timestamp: new Date().getTime()
    }));
    
    // Show auto-save indicator
    const indicator = form.querySelector('.autosave-indicator') || createAutoSaveIndicator(form);
    indicator.textContent = 'Auto-saved';
    indicator.style.color = '#22c55e';
    
    setTimeout(() => {
        indicator.textContent = '';
    }, 2000);
}

// Load form draft
function loadFormDraft(form) {
    const draftId = form.id || 'form-' + form.getAttribute('name') || 'draft';
    const draft = localStorage.getItem(draftId);
    
    if (draft) {
        try {
            const { data, timestamp } = JSON.parse(draft);
            const oneDay = 24 * 60 * 60 * 1000;
            
            if (new Date().getTime() - timestamp < oneDay) {
                // Fill form with saved data
                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = data[key] === 'on';
                        } else {
                            field.value = data[key];
                        }
                    }
                });
                
                // Show loaded indicator
                const indicator = form.querySelector('.autosave-indicator') || createAutoSaveIndicator(form);
                indicator.textContent = 'Draft restored';
                indicator.style.color = '#3b82f6';
                
                setTimeout(() => {
                    indicator.textContent = '';
                }, 3000);
            } else {
                // Draft expired, remove it
                localStorage.removeItem(draftId);
            }
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
}

// Create auto-save indicator
function createAutoSaveIndicator(form) {
    const indicator = document.createElement('div');
    indicator.className = 'autosave-indicator';
    indicator.style.fontSize = '0.8rem';
    indicator.style.marginTop = '5px';
    indicator.style.transition = 'color 0.3s';
    form.appendChild(indicator);
    return indicator;
}

// Setup AJAX forms
function setupAjaxForms() {
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Success!', 'success');
                    
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else if (data.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showNotification(data.message || 'An error occurred', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                showNotification('Network error. Please try again.', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                console.error('Error:', error);
            });
        });
    });
}

// Image preview and upload
function setupImageUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Please select a valid image file (JPEG, PNG, GIF)', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Image size must be less than 2MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.style.backgroundImage = `url(${e.target.result})`;
                        preview.innerHTML = '';
                    }
                };
                reader.readAsDataURL(file);
                
                // Upload image
                uploadImage(file, function(response) {
                    if (response.success) {
                        // Set hidden input with filename
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name + '_filename';
                        hiddenInput.value = response.filename;
                        input.parentElement.appendChild(hiddenInput);
                    } else {
                        showNotification('Failed to upload image', 'error');
                    }
                });
            }
        });
    }
}

// Upload image via AJAX
function uploadImage(file, callback) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('upload_type', 'candidate_photo');
    
    fetch('api/upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Upload error:', error);
        callback({ success: false });
    });
}

// Data table search
function setupTableSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Bulk actions for tables
function setupBulkActions(tableId, actions) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Select all checkbox
    const selectAll = table.querySelector('thead input[type="checkbox"]');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Individual checkboxes
    table.querySelectorAll('tbody input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(table.querySelectorAll('tbody input[type="checkbox"]'))
                .every(cb => cb.checked);
            if (selectAll) {
                selectAll.checked = allChecked;
            }
        });
    });
    
    // Apply bulk action
    window.applyBulkAction = function(action) {
        const selectedIds = [];
        table.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(checkbox => {
            selectedIds.push(checkbox.value);
        });
        
        if (selectedIds.length === 0) {
            showNotification('Please select at least one item', 'warning');
            return;
        }
        
        if (confirm(`Are you sure you want to ${action} ${selectedIds.length} item(s)?`)) {
            fetch('api/bulk_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    ids: selectedIds,
                    table: tableId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error performing bulk action', 'error');
                console.error('Error:', error);
            });
        }
    };
}

// Pagination
function setupPagination(containerId, itemsPerPage = 10) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const items = container.children;
    const totalPages = Math.ceil(items.length / itemsPerPage);
    let currentPage = 1;
    
    // Create pagination controls
    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    container.parentElement.appendChild(pagination);
    
    function showPage(page) {
        currentPage = page;
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        // Hide all items
        Array.from(items).forEach(item => {
            item.style.display = 'none';
        });
        
        // Show items for current page
        for (let i = start; i < end && i < items.length; i++) {
            items[i].style.display = '';
        }
        
        updatePaginationControls();
    }
    
    function updatePaginationControls() {
        pagination.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => showPage(currentPage - 1));
        pagination.appendChild(prevBtn);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn';
            pageBtn.textContent = i;
            pageBtn.classList.toggle('active', i === currentPage);
            pageBtn.addEventListener('click', () => showPage(i));
            pagination.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => showPage(currentPage + 1));
        pagination.appendChild(nextBtn);
        
        // Page info
        const info = document.createElement('span');
        info.className = 'pagination-info';
        info.textContent = `Page ${currentPage} of ${totalPages}`;
        pagination.appendChild(info);
    }
    
    // Show first page initially
    showPage(1);
}

// Countdown timer
function startCountdown(elementId, endTime) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            element.innerHTML = "Time's up!";
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        element.innerHTML = `
            <div class="countdown-unit">
                <span>${days.toString().padStart(2, '0')}</span>
                <small>Days</small>
            </div>
            <div class="countdown-unit">
                <span>${hours.toString().padStart(2, '0')}</span>
                <small>Hours</small>
            </div>
            <div class="countdown-unit">
                <span>${minutes.toString().padStart(2, '0')}</span>
                <small>Minutes</small>
            </div>
            <div class="countdown-unit">
                <span>${seconds.toString().padStart(2, '0')}</span>
                <small>Seconds</small>
            </div>
        `;
    }
    
    updateCountdown();
    const interval = setInterval(updateCountdown, 1000);
    
    return interval;
}

// Export data
function exportData(data, filename, type = 'csv') {
    let content, mimeType;
    
    if (type === 'csv') {
        content = convertToCSV(data);
        mimeType = 'text/csv;charset=utf-8;';
    } else if (type === 'json') {
        content = JSON.stringify(data, null, 2);
        mimeType = 'application/json;charset=utf-8;';
    } else if (type === 'excel') {
        content = convertToExcel(data);
        mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;';
    }
    
    const blob = new Blob([content], { type: mimeType });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Convert data to CSV
function convertToCSV(data) {
    if (!Array.isArray(data) || data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const rows = data.map(row => 
        headers.map(header => 
            JSON.stringify(row[header] || '')
        ).join(',')
    );
    
    return [headers.join(','), ...rows].join('\n');
}

// Convert data to Excel (simplified)
function convertToExcel(data) {
    // In a real implementation, you would use a library like SheetJS
    // This is a simplified version that creates a CSV with .xls extension
    return convertToCSV(data);
}

// File upload with progress
function uploadFileWithProgress(file, url, onProgress, onComplete) {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append('file', file);
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            onProgress(percentComplete);
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            onComplete(JSON.parse(xhr.responseText));
        } else {
            onComplete({ success: false, error: 'Upload failed' });
        }
    });
    
    xhr.addEventListener('error', function() {
        onComplete({ success: false, error: 'Network error' });
    });
    
    xhr.open('POST', url);
    xhr.send(formData);
}

// Drag and drop file upload
function setupFileDropzone(dropzoneId, inputId) {
    const dropzone = document.getElementById(dropzoneId);
    const input = document.getElementById(inputId);
    
    if (!dropzone || !input) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropzone.classList.add('highlight');
    }
    
    function unhighlight() {
        dropzone.classList.remove('highlight');
    }
    
    dropzone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            input.files = files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        }
    }
}

// Real-time updates (using Server-Sent Events or WebSockets)
function setupRealTimeUpdates(channel, callback) {
    if (typeof EventSource !== 'undefined') {
        const eventSource = new EventSource(`api/events.php?channel=${channel}`);
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            callback(data);
        };
        
        eventSource.onerror = function() {
            console.error('EventSource failed.');
            // Fallback to polling
            setTimeout(() => setupPolling(channel, callback), 5000);
        };
        
        return eventSource;
    } else {
        // Fallback to polling
        return setupPolling(channel, callback);
    }
}

// Polling fallback
function setupPolling(channel, callback) {
    let isPolling = true;
    
    async function poll() {
        if (!isPolling) return;
        
        try {
            const response = await fetch(`api/poll.php?channel=${channel}`);
            const data = await response.json();
            
            if (data.updates && data.updates.length > 0) {
                callback(data);
            }
            
            // Poll again after delay
            setTimeout(poll, 5000);
        } catch (error) {
            console.error('Polling error:', error);
            setTimeout(poll, 10000); // Retry after 10 seconds on error
        }
    }
    
    poll();
    
    return {
        stop: function() {
            isPolling = false;
        }
    };
}

// Print functionality
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const originalContents = document.body.innerHTML;
    const printContents = element.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// Copy to clipboard
function copyToClipboard(text, showNotification = true) {
    navigator.clipboard.writeText(text).then(() => {
        if (showNotification) {
            showNotification('Copied to clipboard!', 'success');
        }
    }).catch(err => {
        console.error('Failed to copy: ', err);
        if (showNotification) {
            showNotification('Failed to copy to clipboard', 'error');
        }
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Theme switching
function setupThemeSwitcher() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    if (currentTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        this.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
}

// Accessibility improvements
function setupAccessibility() {
    // Add keyboard navigation to all interactive elements
    document.querySelectorAll('button, a, input, select, textarea').forEach(element => {
        if (!element.getAttribute('tabindex')) {
            element.setAttribute('tabindex', '0');
        }
    });
    
    // Add aria-labels to icons
    document.querySelectorAll('i[class*="fa-"]').forEach(icon => {
        if (!icon.parentElement.getAttribute('aria-label')) {
            const action = icon.className.match(/fa-([a-z-]+)/);
            if (action) {
                icon.parentElement.setAttribute('aria-label', action[1].replace('-', ' '));
            }
        }
    });
    
    // Handle focus trapping in modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            }
            
            if (e.key === 'Escape') {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSystem();
    setupEventListeners();
    setupThemeSwitcher();
    setupAccessibility();
});

// Global error handling
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    
    // Don't show error notifications in production for minor errors
    if (window.location.hostname !== 'localhost') {
        return;
    }
    
    showNotification(`Error: ${e.message}`, 'error');
});

// Unhandled promise rejection
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    
    if (window.location.hostname !== 'localhost') {
        return;
    }
    
    showNotification(`Promise error: ${e.reason}`, 'error');
});

// Make functions available globally
window.showNotification = showNotification;
window.copyToClipboard = copyToClipboard;
window.startCountdown = startCountdown;
window.exportData = exportData;
window.printElement = printElement;