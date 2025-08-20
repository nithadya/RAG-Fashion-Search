$(document).ready(function() {
    // Initialize app
    checkAuthStatus();
    loadCategories();
    updateCartCount();
    
    // Get product ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');
    
    if (productId) {
        loadProductDetails(productId);
        loadRelatedProducts(productId);
    } else {
        showProductNotFound();
    }
    
    // Search functionality
    $('#searchBtn').click(handleSearch);
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            handleSearch();
        }
    });
    
    // Logout functionality
    $('#logoutBtn').click(handleLogout);
});

function checkAuthStatus() {
    $.ajax({
        url: 'api/auth.php?action=check',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.loggedIn) {
                const user = response.user;
                $('#authLinks').hide();
                $('#userLinks').show();
                
                // Update user display
                $('#userDisplayName').text(user.name.split(' ')[0]);
                $('#userFullName').text(user.name);
                $('#userEmail').text(user.email);
                $('#userAvatar').text(user.name.charAt(0).toUpperCase());
            } else {
                $('#authLinks').show();
                $('#userLinks').hide();
                $('#userDisplayName').text('Account');
            }
        },
        error: function() {
            $('#authLinks').show();
            $('#userLinks').hide();
            $('#userDisplayName').text('Account');
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
            
            if (categories && Array.isArray(categories)) {
                categories.forEach(category => {
                    $menu.append(`
                        <li>
                            <a class="dropdown-item" href="products.html?category=${category.id}">
                                <i class="fas fa-tag me-2"></i>${category.name}
                            </a>
                        </li>
                    `);
                });
                
                $menu.append('<li><hr class="dropdown-divider"></li>');
                $menu.append(`
                    <li>
                        <a class="dropdown-item" href="products.html">
                            <i class="fas fa-th me-2"></i>All Categories
                        </a>
                    </li>
                `);
            }
        },
        error: function() {
            console.error('Failed to load categories');
        }
    });
}

function loadProductDetails(productId) {
    $.ajax({
        url: `api/products.php?action=detail&id=${productId}`,
        type: 'GET',
        dataType: 'json',
        success: function(product) {
            if (product && product.id) {
                renderProductDetails(product);
            } else {
                showProductNotFound();
            }
        },
        error: function() {
            showProductNotFound();
        }
    });
}

function renderProductDetails(product) {
    const hasDiscount = product.discount_price && parseFloat(product.discount_price) < parseFloat(product.price);
    const discountPercentage = hasDiscount 
        ? Math.round(((parseFloat(product.price) - parseFloat(product.discount_price)) / parseFloat(product.price)) * 100)
        : 0;
    
    // Update page title and breadcrumb
    document.title = `${product.name} - StyleMe`;
    $('#productCategoryBreadcrumb').html(`<i class="fas fa-tag me-1"></i>${product.category_name || 'Product'}`);
    
    // Create image thumbnails
    let thumbnails = '';
    const images = [product.image1, product.image2, product.image3].filter(img => img && img.trim() !== '');
    
    images.forEach((img, index) => {
        thumbnails += `
            <img src="assets/uploads/${img}" 
                 class="thumbnail ${index === 0 ? 'active' : ''}" 
                 data-img="${img}"
                 alt="Product image ${index + 1}"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
        `;
    });
    
    // Create size options
    let sizeOptions = '';
    if (product.size) {
        const sizes = product.size.split(',');
        sizes.forEach(size => {
            sizeOptions += `
                <div class="size-option" data-size="${size.trim()}">${size.trim()}</div>
            `;
        });
    }
    
    // Create color options
    let colorOptions = '';
    if (product.color) {
        const colors = product.color.split(',');
        colors.forEach(color => {
            const colorValue = color.trim().toLowerCase();
            colorOptions += `
                <div class="color-option" 
                     data-color="${color.trim()}" 
                     style="background-color: ${colorValue};" 
                     title="${color.trim()}"></div>
            `;
        });
    }
    
    // Render product details
    $('#productDetailContainer').html(`
        <div class="col-lg-6">
            <div class="product-gallery">
                <img src="assets/uploads/${product.image1}" 
                     class="main-image" 
                     id="mainImage" 
                     alt="${product.name}"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                <div class="thumbnails">
                    ${thumbnails}
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="product-details">
                <h1 class="product-title">${product.name}</h1>
                <div class="product-price">
                    ${hasDiscount 
                        ? `Rs. ${parseFloat(product.discount_price).toFixed(2)}
                           <span class="discount-price">Rs. ${parseFloat(product.price).toFixed(2)}</span>
                           <span class="badge-sale">${discountPercentage}% OFF</span>`
                        : `Rs. ${parseFloat(product.price).toFixed(2)}`
                    }
                </div>
                <div class="product-meta">
                    ${product.brand ? `<span><i class="fas fa-tag"></i> ${product.brand}</span>` : ''}
                    ${product.category_name ? `<span><i class="fas fa-layer-group"></i> ${product.category_name}</span>` : ''}
                    <span><i class="fas fa-box"></i> ${parseInt(product.stock) > 0 ? `${product.stock} in stock` : 'Out of stock'}</span>
                    ${product.gender ? `<span><i class="fas fa-user"></i> ${product.gender}</span>` : ''}
                </div>
                <div class="mb-4">
                    <p class="lead">${product.description || 'No description available.'}</p>
                </div>
                
                ${sizeOptions ? `
                <div class="mb-4">
                    <h6><i class="fas fa-ruler me-2"></i>Size</h6>
                    <div class="size-options">
                        ${sizeOptions}
                    </div>
                </div>
                ` : ''}
                
                ${colorOptions ? `
                <div class="mb-4">
                    <h6><i class="fas fa-palette me-2"></i>Color</h6>
                    <div class="color-options">
                        ${colorOptions}
                    </div>
                </div>
                ` : ''}
                
                <div class="quantity-container mb-4">
                    <div>
                        <label for="quantity" class="form-label fw-bold">
                            <i class="fas fa-sort-numeric-up me-2"></i>Quantity
                        </label>
                        <input type="number" 
                               class="form-control quantity-input" 
                               id="quantity" 
                               min="1" 
                               max="${product.stock}" 
                               value="1"
                               ${parseInt(product.stock) <= 0 ? 'disabled' : ''}>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-lg" 
                                id="addToCartBtn"
                                ${parseInt(product.stock) <= 0 ? 'disabled' : ''}>
                            <i class="fas fa-cart-plus me-2"></i>
                            ${parseInt(product.stock) > 0 ? 'Add to Cart' : 'Out of Stock'}
                        </button>
                        <button class="btn btn-outline-secondary btn-lg" id="addToWishlistBtn">
                            <i class="far fa-heart me-2"></i>Wishlist
                        </button>
                    </div>
                </div>
                
                <div class="product-tabs">
                    <ul class="nav nav-tabs" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">
                                <i class="fas fa-info-circle me-2"></i>Description
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">
                                <i class="fas fa-star me-2"></i>Reviews
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button">
                                <i class="fas fa-truck me-2"></i>Shipping
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="productTabsContent">
                        <div class="tab-pane fade show active" id="description" role="tabpanel">
                            <h5>Product Details</h5>
                            <p>${product.description || 'No detailed description available.'}</p>
                            ${product.brand ? `<p><strong>Brand:</strong> ${product.brand}</p>` : ''}
                            ${product.gender ? `<p><strong>Gender:</strong> ${product.gender}</p>` : ''}
                            ${product.occasion ? `<p><strong>Occasion:</strong> ${product.occasion}</p>` : ''}
                        </div>
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <div id="productReviews">
                                <div class="text-center py-4">
                                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                                    <h5>No reviews yet</h5>
                                    <p class="text-muted">Be the first to review this product!</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h5>Write a Review</h5>
                                <form id="reviewForm">
                                    <div class="mb-3">
                                        <label for="reviewRating" class="form-label">Rating</label>
                                        <select class="form-select" id="reviewRating" required>
                                            <option value="">Select Rating</option>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                                            <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                                            <option value="3">⭐⭐⭐ 3 Stars</option>
                                            <option value="2">⭐⭐ 2 Stars</option>
                                            <option value="1">⭐ 1 Star</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reviewComment" class="form-label">Review</label>
                                        <textarea class="form-control" id="reviewComment" rows="4" placeholder="Share your experience with this product..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Review
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="shipping" role="tabpanel">
                            <h5><i class="fas fa-truck me-2"></i>Shipping Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Islandwide delivery</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>3-5 working days</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Free shipping over Rs. 5,000</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cash on delivery available</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-undo me-2"></i>Returns & Exchanges</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>7-day return policy</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Original tags required</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unused condition</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    // Setup event handlers
    setupProductEventHandlers(product);
}

function setupProductEventHandlers(product) {
    // Thumbnail click event
    $('.thumbnail').click(function() {
        $('.thumbnail').removeClass('active');
        $(this).addClass('active');
        const imgSrc = $(this).data('img');
        $('#mainImage').attr('src', `assets/uploads/${imgSrc}`);
    });
    
    // Size selection
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected');
        $(this).addClass('selected');
    });
    
    // Color selection
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected');
        $(this).addClass('selected');
    });
    
    // Add to cart
    $('#addToCartBtn').click(function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        if (parseInt(product.stock) <= 0) {
            showToast('This product is currently out of stock', 'error');
            return;
        }
        
        const quantity = $('#quantity').val();
        const size = $('.size-option.selected').data('size') || '';
        const color = $('.color-option.selected').data('color') || '';
        
        $btn.prop('disabled', true).html('<span class="spinner me-2"></span>Adding...');
        
        $.ajax({
            url: 'api/cart.php',
            type: 'POST',
            dataType: 'json',
            data: { 
                action: 'add', 
                product_id: product.id,
                quantity: quantity,
                size: size,
                color: color
            },
            success: function(response) {
                if (response.success) {
                    updateCartCount();
                    $btn.removeClass('btn-primary').addClass('btn-success')
                        .html('<i class="fas fa-check me-2"></i>Added to Cart');
                    showToast('Product added to cart successfully!', 'success');
                    
                    setTimeout(() => {
                        $btn.removeClass('btn-success').addClass('btn-primary')
                            .html(originalHtml).prop('disabled', false);
                    }, 2000);
                } else {
                    showToast(response.message || 'Please login to add items to cart', 'error');
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = 'login.html';
                        }, 1500);
                    }
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                showToast('Something went wrong. Please try again.', 'error');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Add to wishlist
    $('#addToWishlistBtn').click(function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="spinner me-2"></span>Adding...');
        
        $.ajax({
            url: 'api/wishlist.php',
            type: 'POST',
            dataType: 'json',
            data: { 
                action: 'add', 
                product_id: product.id
            },
            success: function(response) {
                if (response.success) {
                    $btn.removeClass('btn-outline-secondary').addClass('btn-outline-danger')
                        .html('<i class="fas fa-heart me-2"></i>Added to Wishlist');
                    showToast('Product added to wishlist!', 'success');
                    
                    setTimeout(() => {
                        $btn.removeClass('btn-outline-danger').addClass('btn-outline-secondary')
                            .html(originalHtml).prop('disabled', false);
                    }, 2000);
                } else {
                    showToast(response.message || 'Please login to add items to wishlist', 'error');
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = 'login.html';
                        }, 1500);
                    }
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                showToast('Something went wrong. Please try again.', 'error');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Review form submission
    $('#reviewForm').submit(function(e) {
        e.preventDefault();
        showToast('Thank you for your review! It will be published after moderation.', 'success');
        $('#reviewForm')[0].reset();
    });
}

function loadRelatedProducts(productId) {
    $.ajax({
        url: `api/products.php?action=related&id=${productId}`,
        type: 'GET',
        dataType: 'json',
        success: function(products) {
            if (products && products.length > 0) {
                renderRelatedProducts(products);
            } else {
                $('#relatedProducts').html(`
                    <div class="col-12 text-center py-4">
                        <p class="text-muted">No related products found.</p>
                    </div>
                `);
            }
        },
        error: function() {
            $('#relatedProducts').html(`
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Unable to load related products.</p>
                </div>
            `);
        }
    });
}

function renderRelatedProducts(products) {
    const $relatedProducts = $('#relatedProducts');
    $relatedProducts.empty();
    
    products.slice(0, 4).forEach(product => {
        const hasDiscount = product.discount_price && parseFloat(product.discount_price) < parseFloat(product.price);
        
        $relatedProducts.append(`
            <div class="col-lg-3 col-md-4 col-6">
                <div class="card related-product-card h-100">
                    <a href="product-detail.html?id=${product.id}">
                        <img src="assets/uploads/${product.image1}" 
                             class="card-img-top" 
                             alt="${product.name}"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                    </a>
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="product-detail.html?id=${product.id}" class="text-decoration-none text-dark">${product.name}</a>
                        </h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                ${hasDiscount 
                                    ? `<span class="fw-bold text-primary">Rs. ${parseFloat(product.discount_price).toFixed(2)}</span>
                                       <small class="text-muted text-decoration-line-through ms-2">Rs. ${parseFloat(product.price).toFixed(2)}</small>`
                                    : `<span class="fw-bold text-primary">Rs. ${parseFloat(product.price).toFixed(2)}</span>`
                                }
                            </div>
                            <button class="btn btn-sm btn-outline-primary related-add-to-cart" data-id="${product.id}">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });
    
    // Add to cart for related products
    $('.related-add-to-cart').click(function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="spinner"></span>');
        
        $.ajax({
            url: 'api/cart.php',
            type: 'POST',
            dataType: 'json',
            data: { 
                action: 'add', 
                product_id: productId,
                quantity: 1
            },
            success: function(response) {
                if (response.success) {
                    updateCartCount();
                    $btn.removeClass('btn-outline-primary').addClass('btn-success')
                        .html('<i class="fas fa-check"></i>');
                    showToast('Product added to cart!', 'success');
                    
                    setTimeout(() => {
                        $btn.removeClass('btn-success').addClass('btn-outline-primary')
                            .html(originalHtml).prop('disabled', false);
                    }, 2000);
                } else {
                    showToast(response.message || 'Please login to add items to cart', 'error');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                showToast('Something went wrong. Please try again.', 'error');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
}

function showProductNotFound() {
    $('#productDetailContainer').html(`
        <div class="col-12 text-center py-5">
            <i class="fas fa-exclamation-circle fa-4x mb-4 text-danger"></i>
            <h3>Product Not Found</h3>
            <p class="lead text-muted">The product you're looking for doesn't exist or has been removed.</p>
            <div class="mt-4">
                <a href="products.html" class="btn btn-primary me-2">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
                <a href="index.html" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
            </div>
        </div>
    `);
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

function updateCartCount() {
    $.ajax({
        url: 'api/cart.php?action=count',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#cartCount').text(response.count || 0);
            }
        },
        error: function() {
            $('#cartCount').text('0');
        }
    });
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