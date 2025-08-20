$(document).ready(function() {
    // Load cart items and summary
    loadCheckoutData();
    
    // Load user data if logged in
    loadUserData();
    
    // Payment method selection
    $('.payment-method').click(function() {
        $('.payment-method').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
        
        // Show/hide card details
        if ($(this).data('method') === 'card') {
            $('.card-details').show();
        } else {
            $('.card-details').hide();
        }
    });
    
    // Form submission
    $('#checkoutForm').submit(function(e) {
        e.preventDefault();
        processOrder();
    });
    
    // Format card number input
    $('#cardNumber').on('input', function() {
        let value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        $(this).val(formattedValue);
    });
    
    // Format expiry date input
    $('#expiryDate').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        $(this).val(value);
    });
});

function loadCheckoutData() {
    $.ajax({
        url: 'api/cart.php?action=get',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.items.length > 0) {
                renderOrderItems(response.items);
                renderOrderSummary(response.summary);
            } else {
                window.location.href = 'cart.html';
            }
        },
        error: function() {
            window.location.href = 'cart.html';
        }
    });
}

function loadUserData() {
    $.ajax({
        url: 'api/auth.php?action=check',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.loggedIn) {
                // Pre-fill form with user data
                const user = response.user;
                $('#firstName').val(user.name.split(' ')[0] || '');
                $('#lastName').val(user.name.split(' ').slice(1).join(' ') || '');
                $('#email').val(user.email || '');
                $('#phone').val(user.phone || '');
                $('#address').val(user.address || '');
                $('#city').val(user.city || '');
                $('#postalCode').val(user.postal_code || '');
            } else {
                window.location.href = 'login.html?redirect=checkout.html';
            }
        }
    });
}

function renderOrderItems(items) {
    const $container = $('#orderItems');
    $container.empty();
    
    items.forEach(item => {
        const hasDiscount = item.discount_price && parseFloat(item.discount_price) < parseFloat(item.price);
        const currentPrice = hasDiscount ? parseFloat(item.discount_price) : parseFloat(item.price);
        
        $container.append(`
            <div class="checkout-item">
                <img src="assets/uploads/${item.image1}" class="checkout-item-img" alt="${item.name}">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${item.name}</h6>
                    <small class="text-muted">Qty: ${item.quantity}</small>
                    <div class="fw-bold text-primary">Rs. ${(currentPrice * item.quantity).toFixed(2)}</div>
                </div>
            </div>
        `);
    });
}

function renderOrderSummary(summary) {
    const $container = $('#orderSummary');
    $container.empty();
    
    $container.append(`
        <div class="summary-item">
            <span>Subtotal (${summary.total_items} items)</span>
            <span>Rs. ${parseFloat(summary.subtotal).toFixed(2)}</span>
        </div>
        <div class="summary-item">
            <span>Shipping</span>
            <span>${summary.shipping > 0 ? 'Rs. ' + parseFloat(summary.shipping).toFixed(2) : 'Free'}</span>
        </div>
        ${summary.discount > 0 ? `
        <div class="summary-item text-success">
            <span>Discount</span>
            <span>-Rs. ${parseFloat(summary.discount).toFixed(2)}</span>
        </div>
        ` : ''}
        <div class="summary-item">
            <span>Total</span>
            <span>Rs. ${parseFloat(summary.total).toFixed(2)}</span>
        </div>
    `);
}

function processOrder() {
    // Validate form first
    if (!$('#checkoutForm')[0].checkValidity()) {
        $('#checkoutForm')[0].reportValidity();
        return;
    }
    
    // Show loading overlay
    $('#loadingOverlay').css('display', 'flex');
    
    // Collect form data
    const formData = {
        action: 'create',
        firstName: $('#firstName').val().trim(),
        lastName: $('#lastName').val().trim(),
        email: $('#email').val().trim(),
        phone: $('#phone').val().trim(),
        address: $('#address').val().trim(),
        city: $('#city').val().trim(),
        postalCode: $('#postalCode').val().trim(),
        notes: $('#notes').val().trim(),
        paymentMethod: $('input[name="paymentMethod"]:checked').val()
    };
    
    // Add card details if card payment is selected
    if (formData.paymentMethod === 'card') {
        formData.cardNumber = $('#cardNumber').val().trim();
        formData.expiryDate = $('#expiryDate').val().trim();
        formData.cvv = $('#cvv').val().trim();
        formData.cardName = $('#cardName').val().trim();
        
        // Validate card details
        if (!formData.cardNumber || !formData.expiryDate || !formData.cvv || !formData.cardName) {
            $('#loadingOverlay').hide();
            alert('Please fill in all card details');
            return;
        }
    }
    
    console.log('Sending order data:', formData); // Debug log
    
    $.ajax({
        url: 'api/orders.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        timeout: 30000, // 30 second timeout
        success: function(response) {
            console.log('Order response:', response); // Debug log
            $('#loadingOverlay').hide();
            
            if (response.success) {
                // Redirect to order confirmation page
                window.location.href = `order-confirmation.html?order=${response.order_number}`;
            } else {
                alert('Error processing order: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Order error:', {xhr, status, error}); // Debug log
            console.error('Response text:', xhr.responseText); // Debug log
            $('#loadingOverlay').hide();
            
            let errorMessage = 'Network error. Please try again.';
            
            if (xhr.status === 404) {
                errorMessage = 'Order processing service not found. Please contact support.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error. Please try again later.';
            } else if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    errorMessage = errorResponse.message || errorMessage;
                } catch (e) {
                    // Keep default error message
                }
            }
            
            alert(errorMessage);
        }
    });
}