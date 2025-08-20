$(document).ready(function() {
    checkAuthAndLoadOrders();
});

function checkAuthAndLoadOrders() {
    $.ajax({
        url: 'api/auth.php?action=check',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.loggedIn) {
                loadOrders();
            } else {
                window.location.href = 'login.html?redirect=orders.html';
            }
        },
        error: function() {
            window.location.href = 'login.html?redirect=orders.html';
        }
    });
}

function loadOrders() {
    $.ajax({
        url: 'api/orders.php?action=history',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderOrders(response.orders);
            } else {
                showEmptyOrders();
            }
        },
        error: function() {
            showError();
        }
    });
}

function renderOrders(orders) {
    const $container = $('#ordersContainer');
    $container.empty();
    
    if (orders.length === 0) {
        showEmptyOrders();
        return;
    }
    
    orders.forEach(order => {
        $container.append(`
            <div class="order-card">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h6 class="mb-1">Order #${order.order_number}</h6>
                        <small class="text-muted">${order.created_at}</small>
                    </div>
                    <div class="col-md-2">
                        <span class="status-badge status-${order.status.toLowerCase()}">
                            ${order.status}
                        </span>
                    </div>
                    <div class="col-md-2">
                        <div class="fw-bold">Rs. ${parseFloat(order.total_amount).toFixed(2)}</div>
                        <small class="text-muted">${order.payment_method}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Payment Method</small>
                        <div>${order.payment_method.toUpperCase()}</div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-outline-primary btn-sm view-order" data-order-id="${order.id}">
                            <i class="fas fa-eye me-1"></i>View Details
                        </button>
                        <a href="track-order.html?order=${order.order_number}" class="btn btn-outline-secondary btn-sm mt-1">
                            <i class="fas fa-truck me-1"></i>Track
                        </a>
                    </div>
                </div>
            </div>
        `);
    });
    
    // Add event listeners
    $('.view-order').click(function() {
        const orderId = $(this).data('order-id');
        viewOrderDetails(orderId);
    });
}

function showEmptyOrders() {
    $('#ordersContainer').html(`
        <div class="empty-orders">
            <i class="fas fa-shopping-bag"></i>
            <h4>No orders yet</h4>
            <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="products.html" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
            </a>
        </div>
    `);
}

function showError() {
    $('#ordersContainer').html(`
        <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
            <h4>Error loading orders</h4>
            <p>Please try again later</p>
            <button class="btn btn-primary" onclick="loadOrders()">Retry</button>
        </div>
    `);
}

function viewOrderDetails(orderId) {
    window.location.href = `order-detail.html?id=${orderId}`;
}