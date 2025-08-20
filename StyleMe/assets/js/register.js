$(document).ready(function() {
    // Password toggle
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
    
    // Password strength checker
    $('#password').on('input', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(strength);
        
        if (password.length > 0) {
            $('#passwordStrength').show();
        } else {
            $('#passwordStrength').hide();
        }
    });
    
    function checkPasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        if (password.length >= 6) score++;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        if (score < 3) {
            return { level: 'weak', text: 'Weak password' };
        } else if (score < 5) {
            return { level: 'medium', text: 'Medium strength' };
        } else {
            return { level: 'strong', text: 'Strong password' };
        }
    }
    
    function updatePasswordStrength(strength) {
        const $strengthContainer = $('#passwordStrength');
        $strengthContainer.removeClass('strength-weak strength-medium strength-strong');
        $strengthContainer.addClass(`strength-${strength.level}`);
        $strengthContainer.find('.strength-text').text(strength.text);
    }
    
    // Form validation
    function validateForm() {
        let isValid = true;
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        
        // Clear previous errors
        $('.form-control').removeClass('error success');
        $('.input-icon').removeClass('error success');
        
        // Name validation
        if (!name) {
            setFieldError('#name', 'Please enter your full name');
            isValid = false;
        } else if (name.length < 2) {
            setFieldError('#name', 'Name must be at least 2 characters');
            isValid = false;
        } else {
            setFieldSuccess('#name');
        }
        
        // Email validation
        if (!email) {
            setFieldError('#email', 'Please enter your email address');
            isValid = false;
        } else if (!isValidEmail(email)) {
            setFieldError('#email', 'Please enter a valid email address');
            isValid = false;
        } else {
            setFieldSuccess('#email');
        }
        
        // Password validation
        if (!password) {
            setFieldError('#password', 'Please enter a password');
            isValid = false;
        } else if (password.length < 6) {
            setFieldError('#password', 'Password must be at least 6 characters');
            isValid = false;
        } else {
            setFieldSuccess('#password');
        }
        
        return isValid;
    }
    
    function setFieldError(fieldId, message) {
        $(fieldId).addClass('error');
        $(fieldId).siblings('.input-icon').removeClass('success').addClass('error');
        if (message) {
            showAlert(message, 'danger');
        }
    }
    
    function setFieldSuccess(fieldId) {
        $(fieldId).addClass('success');
        $(fieldId).siblings('.input-icon').removeClass('error').addClass('success');
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        $('#alertContainer').html(`
            <div class="alert ${alertClass}">
                <i class="fas ${icon}"></i>
                ${message}
            </div>
        `);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('#alertContainer').fadeOut(300, function() {
                $(this).empty().show();
            });
        }, 5000);
    }
    
    function setLoadingState(loading) {
        const $btn = $('#registerBtn');
        const $btnText = $('.btn-text');
        const $btnLoading = $('.btn-loading');
        
        if (loading) {
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
        } else {
            $btn.prop('disabled', false);
            $btnText.show();
            $btnLoading.hide();
        }
    }
    
    // Handle registration form submission
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        
        // Clear previous alerts
        $('#alertContainer').empty();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'register');
        
        setLoadingState(true);
        
        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Account created successfully! Redirecting...', 'success');
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 2000);
                } else {
                    showAlert(response.message || 'Registration failed. Please try again.', 'danger');
                    setLoadingState(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Registration error:', error);
                console.error('Response:', xhr.responseText);
                
                let errorMessage = 'An error occurred. Please try again.';
                
                if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                showAlert(errorMessage, 'danger');
                setLoadingState(false);
            }
        });
    });
    
    // Real-time validation
    $('#name').on('blur', function() {
        const name = $(this).val().trim();
        if (name && name.length >= 2) {
            setFieldSuccess('#name');
        } else if (name) {
            setFieldError('#name');
        }
    });
    
    $('#email').on('blur', function() {
        const email = $(this).val().trim();
        if (email && isValidEmail(email)) {
            setFieldSuccess('#email');
        } else if (email) {
            setFieldError('#email');
        }
    });
    
    // Clear errors on input
    $('.form-control').on('input', function() {
        $(this).removeClass('error');
        $(this).siblings('.input-icon').removeClass('error');
    });
});