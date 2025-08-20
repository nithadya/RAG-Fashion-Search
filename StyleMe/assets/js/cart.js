$(document).ready(function() {
    checkAuthStatus();
    loadCategories();
    loadCartItems();
    $('#searchBtn').click(function() {
        const query = $('#searchInput').val();
        if (query.trim() !== '') {
            window.location.href = `products.html?search=${encodeURIComponent(query)}`;
        }
    });
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            const query = $(this).val();
            if (query.trim() !== '') {
                window.location.href = `products.html?search=${encodeURIComponent(query)}`;
            }
        }
    });
    $(document).on('click', '#logoutBtn', function(e) {
        e.preventDefault();
        logoutUser();
    });
});

function checkAuthStatus() {
    $.ajax({
        url: 'api/auth.php?action=check',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.loggedIn && response.user) {
                $('#authLinks').hide();
                $('#userLinks').show();
                const userName = response.user.name || 'User';
                const firstName = userName.split(' ')[0];
                $('#userDropdown').html(`<i class="fas fa-user me-2"></i><span class="d-none d-lg-inline">${firstName}</span>`);
                $('#userDisplayName').text(firstName);
                $('#userWelcome').html(`<i class="fas fa-user-circle me-2"></i>Welcome, <span id="userDisplayName">${firstName}</span>`);
                window.currentUser = response.user;
            } else {
                $('#authLinks').show();
                $('#userLinks').hide();
                $('#userDropdown').html('<i class="fas fa-user"></i>');
                window.location.href = 'login.html?redirect=cart.html';
            }
        },
        error: function() {
            $('#authLinks').show();
            $('#userLinks').hide();
            window.location.href = 'login.html?redirect=cart.html';
        }
    });
}

function loadCategories() {
    $.ajax({
        url: 'api/products.php?action=get_categories',
        type: 'GET',
        dataType: 'json',
        success: function(categories) {
            const $menu = $('#categoriesMenu');
            $menu.empty();
            categories.forEach(category => {
                $menu.append(`<li><a class="dropdown-item" href="products.html?category=${category.id}">${category.name}</a></li>`);
            });
            $menu.append('<li><hr class="dropdown-divider"></li>');
            $menu.append('<li><a class="dropdown-item" href="products.html">All Categories</a></li>');
        }
    });
}

function loadCartItems() {
    $.ajax({
        url: 'api/cart.php?action=get',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.items && response.items.length > 0) {
                renderCartItems(response.items);
                renderOrderSummary(response.summary);
            } else {
                renderEmptyCart();
            }
        },
        error: function() {
            renderEmptyCart();
        }
    });
}

function renderCartItems(items) {
    const $container = $('#cartItemsContainer');
    $container.empty();
    items.forEach(item => {
        const hasDiscount = item.discount_price && parseFloat(item.discount_price) < parseFloat(item.price);
        const currentPrice = hasDiscount ? parseFloat(item.discount_price) : parseFloat(item.price);
        $container.append(`
            <div class="cart-item">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <img src="assets/uploads/${item.image1}" class="cart-item-img" alt="${item.name}">
                    </div>
                    <div class="col-md-6 cart-item-details">
                        <h6>
                            <a href="product-detail.html?id=${item.product_id}" class="text-decoration-none">${item.name}</a>
                        </h6>
                        <p class="price mb-1">Rs. ${currentPrice.toFixed(2)}</p>
                        <small class="text-muted">Stock: ${item.stock}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group" style="width: 120px;">
                                <button class="btn btn-outline-secondary quantity-minus" type="button" data-id="${item.id}">-</button>
                                <input type="number" class="form-control quantity-input text-center" value="${item.quantity}" min="1" max="${item.stock}" data-id="${item.id}">
                                <button class="btn btn-outline-secondary quantity-plus" type="button" data-id="${item.id}">+</button>
                            </div>
                            <button class="btn btn-outline-danger remove-item" data-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="text-end mt-2">
                            <strong>Rs. ${(currentPrice * item.quantity).toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });
    $('.quantity-minus').click(function() {
        const cartItemId = $(this).data('id');
        const $input = $(this).siblings('.quantity-input');
        let quantity = parseInt($input.val());
        if (quantity > 1) {
            quantity--;
            $input.val(quantity);
            updateCartItem(cartItemId, quantity);
        }
    });
    $('.quantity-plus').click(function() {
        const cartItemId = $(this).data('id');
        const $input = $(this).siblings('.quantity-input');
        let quantity = parseInt($input.val());
        const max = parseInt($input.attr('max'));
        if (quantity < max) {
            quantity++;
            $input.val(quantity);
            updateCartItem(cartItemId, quantity);
        } else {
            alert('Maximum available quantity reached');
        }
    });
    $('.quantity-input').change(function() {
        const cartItemId = $(this).data('id');
        const quantity = parseInt($(this).val());
        const max = parseInt($(this).attr('max'));
        if (quantity >= 1 && quantity <= max) {
            updateCartItem(cartItemId, quantity);
        } else {
            alert(`Please enter a quantity between 1 and ${max}`);
            $(this).val(1);
            updateCartItem(cartItemId, 1);
        }
    });
    $('.remove-item').click(function() {
        const cartItemId = $(this).data('id');
        removeCartItem(cartItemId);
    });
}

function renderOrderSummary(summary) {
    const $summary = $('#orderSummary');
    $summary.empty();
    $summary.append(`
        <div class="summary-row">
            <span>Subtotal (${summary.total_items} items)</span>
            <span>Rs. ${parseFloat(summary.subtotal).toFixed(2)}</span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span>${summary.shipping > 0 ? 'Rs. ' + parseFloat(summary.shipping).toFixed(2) : 'Free'}</span>
        </div>
        ${summary.discount > 0 ? `
        <div class="summary-row text-success">
            <span>Discount</span>
            <span>-Rs. ${parseFloat(summary.discount).toFixed(2)}</span>
        </div>
        ` : ''}
        <div class="summary-row">
            <span>Total</span>
            <span>Rs. ${parseFloat(summary.total).toFixed(2)}</span>
        </div>
    `);
    $('#cartCount').text(summary.total_items);
    if (summary.total_items > 0) {
        $('#checkoutBtn').removeClass('disabled').prop('disabled', false);
    } else {
        $('#checkoutBtn').addClass('disabled').prop('disabled', true);
    }
}

function renderEmptyCart() {
    $('#cartItemsContainer').html(`
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h4>Your cart is empty</h4>
            <p>Looks like you haven't added any items to your cart yet</p>
            <a href="products.html" class="btn btn-primary">Browse Products</a>
        </div>
    `);
    $('#orderSummary').html(`
        <div class="summary-row">
            <span>Subtotal</span>
            <span>Rs. 0.00</span>
        </div>
        <div class="summary-row">
            <span>Delivery</span>
            <span>Rs. 0.00</span>
        </div>
        <div class="summary-row">
            <span>Total</span>
            <span>Rs. 0.00</span>
        </div>
    `);
    $('#checkoutBtn').addClass('disabled');
    $('#cartCount').text('0');
}

function updateCartItem(cartItemId, quantity) {
    $.ajax({
        url: 'api/cart.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'update',
            cart_item_id: cartItemId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                loadCartItems();
            }
        }
    });
}

function removeCartItem(cartItemId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        $.ajax({
            url: 'api/cart.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'remove',
                cart_item_id: cartItemId
            },
            success: function(response) {
                if (response.success) {
                    loadCartItems();
                }
            }
        });
    }
}

function logoutUser() {
    if (!confirm('Are you sure you want to logout?')) return;
    $('#logoutBtn').html('<i class="fas fa-spinner fa-spin me-2"></i>Logging out...');
    $.ajax({
        url: 'api/auth.php',
        type: 'POST',
        dataType: 'json',
        data: { action: 'logout' },
        success: function(response) {
            if (response.success) {
                window.currentUser = null;
                $('#logoutBtn').html('<i class="fas fa-check me-2"></i>Logged out');
                setTimeout(() => { window.location.href = 'index.html'; }, 1000);
            } else {
                alert('Logout failed: ' + (response.message || 'Unknown error'));
                $('#logoutBtn').html('<i class="fas fa-sign-out-alt me-2"></i>Logout');
            }
        },
        error: function() {
            alert('Logout request failed, but you will be logged out.');
            window.location.href = 'index.html';
        }
    });
}