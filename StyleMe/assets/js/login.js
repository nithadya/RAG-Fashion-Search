$(document).ready(function() {
    // Password toggle
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
    
    // Form validation
    function validateForm() {
        let isValid = true;
        const email = $('#email').val().trim();
        const password = $('#password').val();
        
        // Clear previous errors
        $('.form-control').removeClass('error');
        
        // Email validation
        if (!email) {
            $('#email').addClass('error');
            showAlert('Please enter your email address', 'danger');
            isValid = false;
        } else if (!isValidEmail(email)) {
            $('#email').addClass('error');
            showAlert('Please enter a valid email address', 'danger');
            isValid = false;
        }
        
        // Password validation
        if (!password) {
            $('#password').addClass('error');
            showAlert('Please enter your password', 'danger');
            isValid = false;
        }
        
        return isValid;
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
        const $btn = $('#loginBtn');
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
    
    // Handle login form submission with RBAC
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        
        // Clear previous alerts
        $('#alertContainer').empty();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const rememberMe = $('#rememberMe').is(':checked');
        
        setLoadingState(true);
        
        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'login',
                email: email,
                password: password,
                remember: rememberMe
            },
            success: function(response) {
                if (response.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    
                    // RBAC: Redirect based on user role
                    setTimeout(() => {
                        if (response.user.role === 'admin') {
                            window.location.href = 'admin/dashboard.html';
                        } else {
                            const urlParams = new URLSearchParams(window.location.search);
                            const redirect = urlParams.get('redirect');
                            window.location.href = redirect || 'index.html';
                        }
                    }, 1500);
                } else {
                    showAlert(response.message || 'Invalid email or password', 'danger');
                    setLoadingState(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', error);
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
    $('#email').on('blur', function() {
        const email = $(this).val().trim();
        if (email && !isValidEmail(email)) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    $('#password').on('input', function() {
        $(this).removeClass('error');
    });
});