$(document).ready(function() {
    // Initialize app with proper async handling
    initializeProfile();
    
    // Form submissions
    $('#personalInfoForm').submit(handlePersonalInfoUpdate);
    $('#addressForm').submit(handleAddressUpdate);
    $('#passwordForm').submit(handlePasswordUpdate);
    
    // Search functionality
    $('#searchBtn').click(handleSearch);
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) handleSearch();
    });
    
    // Logout functionality
    $('#logoutBtn').click(handleLogout);
});

// Fixed: Proper async initialization
async function initializeProfile() {
    try {
        await checkAuthStatus();
        await loadUserProfile();
        await loadUserStats();
        await loadRecentOrders();
    } catch (error) {
        console.error('Profile initialization error:', error);
        window.location.href = 'login.html?redirect=profile.html';
    }
}

// Fixed: Return Promise for proper chaining
function checkAuthStatus() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api/auth.php?action=check',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.loggedIn) {
                    const user = response.user;
                    $('#authLinks').hide();
                    $('#userLinks').show();
                    $('#userDisplayName').text(user.name.split(' ')[0]);
                    window.currentUser = user;
                    resolve(user);
                } else {
                    reject('Not logged in');
                }
            },
            error: function() {
                reject('Auth check failed');
            }
        });
    });
}

// Fixed: Use currentUser data properly
function loadUserProfile() {
    return new Promise((resolve) => {
        if (!window.currentUser) {
            resolve();
            return;
        }
        
        const user = window.currentUser;
        
        // Update profile header
        $('#profileName').text(user.name);
        $('#profileEmail').text(user.email);
        $('#avatarInitials').text(user.name.charAt(0).toUpperCase());
        
        // Set member since date
        const memberSince = new Date(user.created_at).getFullYear();
        $('#memberSince').text(memberSince);
        
        // Fill personal info form
        const nameParts = user.name.split(' ');
        $('#firstName').val(nameParts[0] || '');
        $('#lastName').val(nameParts.slice(1).join(' ') || '');
        $('#email').val(user.email);
        $('#phone').val(user.phone || '');
        
        // Fill address form
        $('#address').val(user.address || '');
        $('#city').val(user.city || '');
        $('#postalCode').val(user.postal_code || '');
        
        resolve();
    });
}

// Fixed: Proper API endpoint
function loadUserStats() {
    return new Promise((resolve) => {
        $.ajax({
            url: 'api/profile.php?action=stats',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#totalOrders').text(response.stats.total_orders || 0);
                    $('#totalSpent').text('Rs. ' + (response.stats.total_spent || 0).toLocaleString());
                    $('#wishlistCount').text(response.stats.wishlist_count || 0);
                    $('#cartItemsCount').text(response.stats.cart_count || 0);
                }
                resolve();
            },
            error: function() {
                console.error('Failed to load user stats');
                resolve();
            }
        });
    });
}

// Fixed: Use profile.php for orders
function loadRecentOrders() {
    return new Promise((resolve) => {
        $.ajax({
            url: 'api/profile.php?action=recent_orders',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.orders && response.orders.length > 0) {
                    renderRecentOrders(response.orders);
                } else {
                    $('#recentOrders').html(`
                        <div class="text-center py-4">
                            <i class="fas fa-box fa-3x text-muted mb-3"></i>
                            <h5>No Orders Yet</h5>
                            <p class="text-muted">Start shopping to see your orders here!</p>
                            <a href="products.html" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Browse Products
                            </a>
                        </div>
                    `);
                }
                resolve();
            },
            error: function() {
                $('#recentOrders').html(`
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-muted mb-3"></i>
                        <p>Unable to load recent orders</p>
                    </div>
                `);
                resolve();
            }
        });
    });
}

function renderRecentOrders(orders) {
    const $container = $('#recentOrders');
    $container.empty();
    
    orders.forEach(order => {
        const orderDate = new Date(order.created_at).toLocaleDateString();
        
        $container.append(`
            <div class="order-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Order #${order.order_number}</h6>
                        <small class="text-muted">${orderDate} • Rs. ${parseFloat(order.total_amount).toFixed(2)}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                        <a href="order-detail.html?id=${order.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
        `);
    });
}

// Keep existing form handlers
function handlePersonalInfoUpdate(e) {
    e.preventDefault();
    
    const formData = {
        action: 'update_personal',
        firstName: $('#firstName').val(),
        lastName: $('#lastName').val(),
        phone: $('#phone').val(),
        dateOfBirth: $('#dateOfBirth').val(),
        gender: $('#gender').val(),
        preferredLanguage: $('#preferredLanguage').val()
    };
    
    $.ajax({
        url: 'api/profile.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                showToast('Personal information updated successfully!', 'success');
                
                // Update profile name
                const fullName = `${formData.firstName} ${formData.lastName}`.trim();
                $('#profileName').text(fullName);
                $('#avatarInitials').text(fullName.charAt(0).toUpperCase());
                $('#userDisplayName').text(formData.firstName);
                
                // Update currentUser
                window.currentUser.name = fullName;
            } else {
                showToast(response.message || 'Failed to update personal information', 'error');
            }
        },
        error: function() {
            showToast('Something went wrong. Please try again.', 'error');
        }
    });
}

function handleAddressUpdate(e) {
    e.preventDefault();
    
    const formData = {
        action: 'update_address',
        address: $('#address').val(),
        city: $('#city').val(),
        postalCode: $('#postalCode').val(),
        province: $('#province').val(),
        country: $('#country').val()
    };
    
    $.ajax({
        url: 'api/profile.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                showToast('Address information updated successfully!', 'success');
            } else {
                showToast(response.message || 'Failed to update address', 'error');
            }
        },
        error: function() {
            showToast('Something went wrong. Please try again.', 'error');
        }
    });
}

function handlePasswordUpdate(e) {
    e.preventDefault();
    
    const newPassword = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();
    
    if (newPassword !== confirmPassword) {
        showToast('New passwords do not match', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showToast('Password must be at least 8 characters long', 'error');
        return;
    }
    
    const formData = {
        action: 'update_password',
        currentPassword: $('#currentPassword').val(),
        newPassword: newPassword
    };
    
    $.ajax({
        url: 'api/profile.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                showToast('Password updated successfully!', 'success');
                $('#passwordForm')[0].reset();
            } else {
                showToast(response.message || 'Failed to update password', 'error');
            }
        },
        error: function() {
            showToast('Something went wrong. Please try again.', 'error');
        }
    });
}

function handleSearch() {
    const query = $('#searchInput').val().trim();
    if (query !== '') {
        window.location.href = `products.html?search=${encodeURIComponent(query)}`;
    }
}

function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'logout' },
            success: function(response) {
                if (response.success) {
                    showToast('Logged out successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 1000);
                }
            },
            error: function() {
                showToast('Logout failed. Please try again.', 'error');
            }
        });
    }
}

function showToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const iconMap = {
        success: 'fas fa-check-circle text-success',
        error: 'fas fa-exclamation-circle text-danger',
        warning: 'fas fa-exclamation-triangle text-warning',
        info: 'fas fa-info-circle text-info'
    };
    
    const toast = `
        <div class="toast toast-${type}" id="${toastId}" role="alert">
            <div class="toast-header">
                <i class="${iconMap[type]} me-2"></i>
                <strong class="me-auto">StyleMe</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    $('#toastContainer').append(toast);
    const toastElement = new bootstrap.Toast(document.getElementById(toastId));
    toastElement.show();
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        $(`#${toastId}`).remove();
    }, 5000);
}