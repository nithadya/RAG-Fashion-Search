// Global filters object
window.filters = {};

$(document).ready(function () {
  // Check user authentication status
  checkAuthStatus();

  // Load categories for navbar dropdown
  loadCategories();

  // Update cart count
  updateCartCount();

  // Parse URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const searchQuery = urlParams.get("search");
  const categoryId = urlParams.get("category");
  const filter = urlParams.get("filter");

  // Set initial filters based on URL - make it global
  window.filters = {
    search: searchQuery || "",
    category: categoryId || "",
    min_price: "",
    max_price: "",
    brand: [],
    size: [],
    color: [],
    occasion: [],
    gender: "",
    sort: "newest",
    page: 1,
    view: "grid",
  };

  if (filter === "discount") {
    window.filters.min_discount = 1;
  }

  // Load filter options first, then products
  loadFilterOptions().then(() => {
    loadProducts(window.filters);
    setupFilterEventListeners();
  });

  // Enhanced RAG Search functionality
  $("#searchBtn").click(function () {
    const query = $("#searchInput").val().trim();
    if (query !== "") {
      performRAGSearch(query, window.filters);
    }
  });

  $("#searchInput").keypress(function (e) {
    if (e.which === 13) {
      const query = $(this).val().trim();
      if (query !== "") {
        performRAGSearch(query, window.filters);
      }
    }
  });

  // Sort functionality
  $(document).on("click", ".sort-option", function (e) {
    e.preventDefault();
    window.filters.sort = $(this).data("sort");
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // View toggle functionality
  $(document).on("click", ".view-option", function () {
    $(".view-option").removeClass("active");
    $(this).addClass("active");
    window.filters.view = $(this).data("view");
    renderProducts(window.filters.view, currentProducts);
  });

  // Reset filters functionality
  $(document).on("click", "#resetFilters", function () {
    resetAllFilters();
  });

  // Logout functionality
  $("#logoutBtn").click(function (e) {
    e.preventDefault();
    logoutUser();
  });
});

let currentProducts = [];
let currentPagination = {};

function setupFilterEventListeners() {
  // Category filter - use event delegation
  $(document).on("change", ".category-filter", function () {
    window.filters.category = $(this).val();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Price filter
  $(document).on("click", "#applyPriceFilter", function () {
    window.filters.min_price = $("#minPrice").val();
    window.filters.max_price = $("#maxPrice").val();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Brand filter
  $(document).on("change", ".brand-filter", function () {
    window.filters.brand = $(".brand-filter:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Size filter
  $(document).on("change", ".size-filter", function () {
    window.filters.size = $(".size-filter:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Color filter
  $(document).on("change", ".color-filter", function () {
    window.filters.color = $(".color-filter:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Occasion filter
  $(document).on("change", ".occasion-filter", function () {
    window.filters.occasion = $(".occasion-filter:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });

  // Gender filter
  $(document).on("change", ".gender-filter", function () {
    window.filters.gender = $(this).val();
    window.filters.page = 1;
    loadProducts(window.filters);
    updateURL(window.filters);
  });
}

function loadFilterOptions() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: "api/products.php?action=filter_options",
      type: "GET",
      dataType: "json",
      success: function (options) {
        if (!options || typeof options !== "object") {
          console.error("Invalid filter options response");
          resolve();
          return;
        }

        // Categories
        const $categoryFilters = $("#categoryFilters");
        $categoryFilters.empty();
        $categoryFilters.append(`
                    <div class="form-check">
                        <input class="form-check-input category-filter" type="radio" name="category" id="categoryAll" value="" checked>
                        <label class="form-check-label" for="categoryAll">All Categories</label>
                    </div>
                `);

        if (options.categories && Array.isArray(options.categories)) {
          options.categories.forEach((category) => {
            $categoryFilters.append(`
                            <div class="form-check">
                                <input class="form-check-input category-filter" type="radio" name="category" id="category${
                                  category.id
                                }" value="${category.id}">
                                <label class="form-check-label" for="category${
                                  category.id
                                }">${category.name} (${
              category.count || 0
            })</label>
                            </div>
                        `);
          });
        }

        // Brands
        const $brandFilters = $("#brandFilters");
        $brandFilters.empty();
        if (options.brands && Array.isArray(options.brands)) {
          options.brands.forEach((brand) => {
            $brandFilters.append(`
                            <div class="form-check">
                                <input class="form-check-input brand-filter" type="checkbox" id="brand${
                                  brand.brand
                                }" value="${brand.brand}">
                                <label class="form-check-label" for="brand${
                                  brand.brand
                                }">${brand.brand} (${brand.count || 0})</label>
                            </div>
                        `);
          });
        }

        // Sizes
        const $sizeFilters = $("#sizeFilters");
        $sizeFilters.empty();
        if (options.sizes && Array.isArray(options.sizes)) {
          options.sizes.forEach((size) => {
            $sizeFilters.append(`
                            <div class="form-check">
                                <input class="form-check-input size-filter" type="checkbox" id="size${
                                  size.size
                                }" value="${size.size}">
                                <label class="form-check-label" for="size${
                                  size.size
                                }">${size.size} (${size.count || 0})</label>
                            </div>
                        `);
          });
        }

        // Colors
        const $colorFilters = $("#colorFilters");
        $colorFilters.empty();
        if (options.colors && Array.isArray(options.colors)) {
          options.colors.forEach((color) => {
            $colorFilters.append(`
                            <div class="form-check">
                                <input class="form-check-input color-filter" type="checkbox" id="color${
                                  color.color
                                }" value="${color.color}">
                                <label class="form-check-label" for="color${
                                  color.color
                                }">${color.color} (${color.count || 0})</label>
                            </div>
                        `);
          });
        }

        // Occasions
        const $occasionFilters = $("#occasionFilters");
        $occasionFilters.empty();
        if (options.occasions && Array.isArray(options.occasions)) {
          options.occasions.forEach((occasion) => {
            $occasionFilters.append(`
                            <div class="form-check">
                                <input class="form-check-input occasion-filter" type="checkbox" id="occasion${
                                  occasion.occasion
                                }" value="${occasion.occasion}">
                                <label class="form-check-label" for="occasion${
                                  occasion.occasion
                                }">${occasion.occasion} (${
              occasion.count || 0
            })</label>
                            </div>
                        `);
          });
        }

        // Set initial filters from URL
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get("category");
        const filterType = urlParams.get("filter");

        if (categoryId) {
          $(`#category${categoryId}`).prop("checked", true);
          $("#categoryAll").prop("checked", false);
        }

        if (filterType === "discount") {
          $("#productListingTitle").text("Special Offers");
        } else if (categoryId && options.categories) {
          const categoryName = options.categories.find(
            (c) => c.id == categoryId
          )?.name;
          if (categoryName) {
            $("#productListingTitle").text(categoryName);
          }
        }

        resolve();
      },
      error: function (xhr, status, error) {
        console.error("Failed to load filter options:", error);
        resolve(); // Still resolve to continue with loading products
      },
    });
  });
}

function loadProducts(filters) {
  // Show loading state
  $("#productList").html(`
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading products...</p>
        </div>
    `);

  const queryString = new URLSearchParams();

  // Add action parameter
  queryString.append("action", "search");

  for (const key in filters) {
    if (Array.isArray(filters[key])) {
      filters[key].forEach((value) => {
        if (value) queryString.append(`${key}[]`, value);
      });
    } else if (filters[key]) {
      queryString.append(key, filters[key]);
    }
  }

  $.ajax({
    url: `api/products.php?${queryString.toString()}`,
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response && response.products) {
        currentProducts = response.products;
        currentPagination = response.pagination || {};

        renderProducts(filters.view, currentProducts);
        renderPagination(currentPagination, filters.page);

        // Update page title based on search
        if (filters.search) {
          $("#productListingTitle").html(
            `Search Results for: <em>"${filters.search}"</em> (${
              response.pagination?.total_items || 0
            } items)`
          );
        } else {
          $("#productListingTitle").text(
            `All Products (${response.pagination?.total_items || 0} items)`
          );
        }
      } else {
        showNoResults();
      }
    },
    error: function (xhr, status, error) {
      console.error("Failed to load products:", error);
      showError();
    },
  });
}

function renderPagination(pagination, currentPage) {
  const $pagination = $("#pagination");
  $pagination.empty();

  if (!pagination || pagination.total_pages <= 1) return;

  const totalPages = pagination.total_pages;

  // Previous button
  $pagination.append(`
        <li class="page-item ${currentPage <= 1 ? "disabled" : ""}">
            <a class="page-link pagination-link" href="#" data-page="${
              currentPage - 1
            }">Previous</a>
        </li>
    `);

  // Page numbers
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }

  if (startPage > 1) {
    $pagination.append(`
            <li class="page-item">
                <a class="page-link pagination-link" href="#" data-page="1">1</a>
            </li>
            ${
              startPage > 2
                ? '<li class="page-item disabled"><span class="page-link">...</span></li>'
                : ""
            }
        `);
  }

  for (let i = startPage; i <= endPage; i++) {
    $pagination.append(`
            <li class="page-item ${i == currentPage ? "active" : ""}">
                <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
  }

  if (endPage < totalPages) {
    $pagination.append(`
            ${
              endPage < totalPages - 1
                ? '<li class="page-item disabled"><span class="page-link">...</span></li>'
                : ""
            }
            <li class="page-item">
                <a class="page-link pagination-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>
        `);
  }

  // Next button
  $pagination.append(`
        <li class="page-item ${currentPage >= totalPages ? "disabled" : ""}">
            <a class="page-link pagination-link" href="#" data-page="${
              currentPage + 1
            }">Next</a>
        </li>
    `);

  // Pagination click event
  $(document).on("click", ".pagination-link", function (e) {
    e.preventDefault();
    const page = parseInt($(this).data("page"));
    if (page && page !== currentPage) {
      window.filters.page = page;
      loadProducts(window.filters);
      updateURL(window.filters);

      // Scroll to top
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  });
}

function resetAllFilters() {
  // Reset all filter values
  $('input[type="checkbox"]').prop("checked", false);
  $('input[type="radio"][name="gender"]').prop("checked", function () {
    return $(this).val() === "";
  });
  $('input[type="radio"][name="category"]').prop("checked", function () {
    return $(this).val() === "";
  });
  $("#minPrice, #maxPrice").val("");

  // Reset filters object
  window.filters.search = "";
  window.filters.category = "";
  window.filters.min_price = "";
  window.filters.max_price = "";
  window.filters.brand = [];
  window.filters.size = [];
  window.filters.color = [];
  window.filters.occasion = [];
  window.filters.gender = "";
  window.filters.sort = "newest";
  window.filters.page = 1;
  window.filters.view = "grid";

  // Update URL without reload
  const newUrl = window.location.pathname;
  window.history.pushState({}, "", newUrl);

  // Reload products
  loadProducts(window.filters);

  // Reset page title
  $("#productListingTitle").text("All Products");
}

function updateURL(filters) {
  const params = new URLSearchParams();

  for (const key in filters) {
    if (Array.isArray(filters[key])) {
      filters[key].forEach((value) => {
        if (value) params.append(`${key}[]`, value);
      });
    } else if (filters[key] && key !== "view" && key !== "page") {
      params.append(key, filters[key]);
    }
  }

  const newUrl = params.toString()
    ? `${window.location.pathname}?${params.toString()}`
    : window.location.pathname;

  window.history.pushState({}, "", newUrl);
}

function showNoResults() {
  $("#productList").html(`
        <div class="col-12 text-center py-5">
            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
            <h4>No products found</h4>
            <p>Try adjusting your search or filter criteria</p>
            <button class="btn btn-primary" onclick="resetAllFilters(filters)">Reset All Filters</button>
        </div>
    `);
}

function showError() {
  $("#productList").html(`
        <div class="col-12 text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i>
            <h4>Error loading products</h4>
            <p>Please try again later</p>
            <button class="btn btn-primary" onclick="location.reload()">Retry</button>
        </div>
    `);
}

// Keep existing functions for auth, cart, etc.
function checkAuthStatus() {
  $.ajax({
    url: "api/auth.php?action=check",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success && response.loggedIn) {
        $("#authLinks").hide();
        $("#userLinks").show();
        $("#userDropdown i").after(
          '<span class="d-none d-lg-inline"> ' + response.user.name + "</span>"
        );
      } else {
        $("#authLinks").show();
        $("#userLinks").hide();
      }
    },
    error: function () {
      console.log("Auth check failed");
      $("#authLinks").show();
      $("#userLinks").hide();
    },
  });
}

function loadCategories() {
  $.ajax({
    url: "api/products.php?action=get_categories",
    type: "GET",
    dataType: "json",
    success: function (categories) {
      const $menu = $("#categoriesMenu");
      $menu.empty();

      if (categories && Array.isArray(categories)) {
        categories.forEach((category) => {
          $menu.append(`
                        <li>
                            <a class="dropdown-item" href="products.html?category=${category.id}">${category.name}</a>
                        </li>
                    `);
        });

        $menu.append('<li><hr class="dropdown-divider"></li>');
        $menu.append(
          '<li><a class="dropdown-item" href="products.html">All Categories</a></li>'
        );
      }
    },
    error: function () {
      console.log("Failed to load categories");
    },
  });
}

function renderProducts(view, products) {
  const $productList = $("#productList");
  $productList.empty();

  if (!products || products.length === 0) {
    showNoResults();
    return;
  }

  if (view === "grid") {
    $productList.addClass("row").removeClass("list-view");

    products.forEach((product) => {
      const price = parseFloat(product.price) || 0;
      const discountPrice = parseFloat(product.discount_price) || 0;

      const hasDiscount = discountPrice > 0 && discountPrice < price;
      const discountPercentage = hasDiscount
        ? Math.round(((price - discountPrice) / price) * 100)
        : 0;

      // Create unique image ID for error handling
      const imageId = `product-img-${product.id}`;

      $productList.append(`
                <div class="col-lg-4 col-md-6 col-6 mb-4">
                    <div class="card product-card h-100">
                        ${
                          hasDiscount
                            ? `<span class="badge badge-sale">${discountPercentage}% OFF</span>`
                            : ""
                        }
                        <a href="product-detail.html?id=${product.id}">
                            <img id="${imageId}" 
                                 src="assets/uploads/${product.image1}" 
                                 class="card-img-top product-img" 
                                 alt="${product.name}"
                                 data-original-src="assets/uploads/${
                                   product.image1
                                 }"
                                 data-error-handled="false">
                        </a>
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="product-detail.html?id=${
                                  product.id
                                }" class="text-decoration-none text-dark">${
        product.name
      }</a>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    ${
                                      hasDiscount
                                        ? `<span class="price">Rs. ${discountPrice.toFixed(
                                            2
                                          )}</span>
                                           <small class="discount-price ms-2">Rs. ${price.toFixed(
                                             2
                                           )}</small>`
                                        : `<span class="price">Rs. ${price.toFixed(
                                            2
                                          )}</span>`
                                    }
                                </div>
                                <button class="btn btn-sm btn-outline-primary add-to-cart" data-id="${
                                  product.id
                                }">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
    });
  } else {
    $productList.removeClass("row").addClass("list-view");

    products.forEach((product) => {
      const price = parseFloat(product.price) || 0;
      const discountPrice = parseFloat(product.discount_price) || 0;

      const hasDiscount = discountPrice > 0 && discountPrice < price;
      const discountPercentage = hasDiscount
        ? Math.round(((price - discountPrice) / price) * 100)
        : 0;

      const imageId = `product-list-img-${product.id}`;

      $productList.append(`
                <div class="product-list-item mb-4">
                    <div class="card product-card">
                        <div class="row g-0">
                            <div class="col-md-3">
                                <a href="product-detail.html?id=${product.id}">
                                    <img id="${imageId}" 
                                         src="assets/uploads/${product.image1}" 
                                         class="img-fluid rounded-start h-100" 
                                         alt="${product.name}" 
                                         style="object-fit: cover;"
                                         data-original-src="assets/uploads/${
                                           product.image1
                                         }"
                                         data-error-handled="false">
                                </a>
                            </div>
                            <div class="col-md-6">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="product-detail.html?id=${
                                          product.id
                                        }" class="text-decoration-none">${
        product.name
      }</a>
                                    </h5>
                                    <p class="card-text text-muted">${(
                                      product.description || ""
                                    ).substring(0, 150)}...</p>
                                    <div class="d-flex gap-2 mb-2">
                                        ${
                                          product.size
                                            ? `<span class="badge bg-light text-dark">${product.size}</span>`
                                            : ""
                                        }
                                        ${
                                          product.color
                                            ? `<span class="badge bg-light text-dark">${product.color}</span>`
                                            : ""
                                        }
                                        ${
                                          product.brand
                                            ? `<span class="badge bg-light text-dark">${product.brand}</span>`
                                            : ""
                                        }
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-body h-100 d-flex flex-column justify-content-between">
                                    <div class="text-end">
                                        ${
                                          hasDiscount
                                            ? `<span class="price">Rs. ${discountPrice.toFixed(
                                                2
                                              )}</span>
                                               <small class="discount-price ms-2">Rs. ${price.toFixed(
                                                 2
                                               )}</small>`
                                            : `<span class="price">Rs. ${price.toFixed(
                                                2
                                              )}</span>`
                                        }
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-outline-secondary">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary add-to-cart" data-id="${
                                          product.id
                                        }">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
    });
  }

  // Setup image error handling after DOM insertion
  setupImageErrorHandling();
}

function setupImageErrorHandling() {
  // Handle image loading errors with fallback
  $('img[data-error-handled="false"]').each(function () {
    const $img = $(this);
    const originalSrc = $img.data("original-src");

    $img.on("error", function () {
      // Check if error has already been handled to prevent infinite loop
      if ($img.data("error-handled") === "true") {
        return;
      }

      // Mark as error handled
      $img.data("error-handled", "true");

      // Try alternative image paths
      const alternatives = [
        originalSrc.replace("assets/uploads/", "assets/images/products/"),
        "assets/images/placeholder-product.jpg",
        "assets/images/no-image.png",
        "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==",
      ];

      tryAlternativeImage($img, alternatives, 0);
    });

    // Mark as initialized
    $img.data("error-handled", "false");
  });
}

function tryAlternativeImage($img, alternatives, index) {
  if (index >= alternatives.length) {
    // All alternatives failed, show final fallback
    $img.attr(
      "src",
      createPlaceholderDataURL($img.attr("alt") || "Product Image")
    );
    return;
  }

  const testImg = new Image();
  testImg.onload = function () {
    $img.attr("src", alternatives[index]);
  };
  testImg.onerror = function () {
    tryAlternativeImage($img, alternatives, index + 1);
  };
  testImg.src = alternatives[index];
}

function createPlaceholderDataURL(text) {
  // Create a simple SVG placeholder
  const svg = `
        <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#f8f9fa"/>
            <rect x="20" y="20" width="160" height="160" fill="none" stroke="#dee2e6" stroke-width="2" stroke-dasharray="5,5"/>
            <text x="50%" y="45%" font-family="Arial" font-size="12" fill="#6c757d" text-anchor="middle" dy=".3em">No Image</text>
            <text x="50%" y="55%" font-family="Arial" font-size="10" fill="#adb5bd" text-anchor="middle" dy=".3em">Available</text>
        </svg>
    `;
  return "data:image/svg+xml;base64," + btoa(svg);
}

function updateCartCount() {
  $.ajax({
    url: "api/cart.php?action=count",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#cartCount").text(response.count || 0);
      } else {
        $("#cartCount").text("0");
      }
    },
    error: function () {
      $("#cartCount").text("0");
    },
  });
}

function logoutUser() {
  $.ajax({
    url: "api/auth.php?action=logout",
    type: "POST",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        window.location.href = "index.html";
      }
    },
  });
}

// Event delegation for add to cart buttons
$(document).on("click", ".add-to-cart", function () {
  const productId = $(this).data("id");
  const $btn = $(this);

  const originalHtml = $btn.html();
  $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

  $.ajax({
    url: "api/cart.php",
    type: "POST",
    dataType: "json",
    data: { action: "add", product_id: productId },
    success: function (response) {
      if (response.success) {
        updateCartCount();
        $btn
          .removeClass("btn-outline-primary")
          .addClass("btn-success")
          .html('<i class="fas fa-check"></i> Added');

        showToast("Product added to cart!", "success");

        setTimeout(() => {
          $btn
            .removeClass("btn-success")
            .addClass("btn-outline-primary")
            .html(originalHtml)
            .prop("disabled", false);
        }, 2000);
      } else {
        showToast(
          response.message || "Please login to add items to cart",
          "error"
        );

        if (response.redirect) {
          setTimeout(() => {
            window.location.href = "login.html";
          }, 1500);
        }

        $btn.html(originalHtml).prop("disabled", false);
      }
    },
    error: function (xhr, status, error) {
      console.error("Add to cart error:", error);
      showToast("Something went wrong. Please try again.", "error");
      $btn.html(originalHtml).prop("disabled", false);
    },
  });
});

// RAG Search Integration
async function performRAGSearch(query, filters) {
  try {
    // Show enhanced loading state for RAG search
    $("#productList").html(`
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">AI Search in progress...</span>
                </div>
                <p class="mt-3">🤖 AI is finding the best matches for "${query}"...</p>
                <small class="text-muted">Using intelligent search to understand your preferences</small>
            </div>
        `);

    // Get user ID if logged in
    const userId = getCurrentUserId() || 0;

    // Call RAG search endpoint
    const ragResponse = await fetch("http://localhost:5000/search", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        query: query,
        user_id: userId,
      }),
    });

    if (!ragResponse.ok) {
      throw new Error(`RAG search failed: ${ragResponse.status}`);
    }

    const ragData = await ragResponse.json();

    if (!ragData.success) {
      throw new Error(ragData.message || "RAG search failed");
    }

    // Show RAG search results info
    showToast(
      `AI found ${ragData.results_count} matching products in ${ragData.processing_time}s`,
      "success"
    );

    if (ragData.product_ids && ragData.product_ids.length > 0) {
      // Load specific products by IDs from RAG
      await loadProductsByIds(ragData.product_ids, query);
    } else {
      // Fallback to regular search if no RAG results
      console.log("No RAG results, falling back to regular search");
      filters.search = query;
      filters.page = 1;
      loadProducts(filters);
    }

    // Update URL
    const newUrl = new URL(window.location);
    newUrl.searchParams.set("search", query);
    window.history.pushState({}, "", newUrl);
  } catch (error) {
    console.error("RAG search error:", error);
    showToast("AI search unavailable, using standard search", "warning");

    // Fallback to regular search
    filters.search = query;
    filters.page = 1;
    loadProducts(filters);
    updateURL(filters);
  }
}

// Load specific products by their IDs (from RAG results)
async function loadProductsByIds(productIds, originalQuery) {
  try {
    const response = await fetch("api/products.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "get_by_ids",
        product_ids: productIds,
      }),
    });

    const data = await response.json();

    if (data.success && data.products) {
      currentProducts = data.products;
      currentPagination = {
        total_items: data.products.length,
        items_per_page: data.products.length,
        current_page: 1,
        total_pages: 1,
      };

      renderProducts("grid", currentProducts);

      // Update title with RAG results
      $("#productListingTitle").html(`
                🤖 AI Search Results for: <em>"${originalQuery}"</em> 
                (${data.products.length} intelligent matches)
            `);

      // Clear pagination since we're showing all RAG results
      $("#pagination").html("");
    } else {
      showNoResults();
    }
  } catch (error) {
    console.error("Error loading RAG products:", error);
    showNoResults();
  }
}

// Helper function to get current user ID
function getCurrentUserId() {
  // Check if user is logged in and get ID from localStorage or session
  const userData = localStorage.getItem("user_data");
  if (userData) {
    try {
      return JSON.parse(userData).id;
    } catch (e) {
      return null;
    }
  }
  return null;
}

function showToast(message, type = "info") {
  const toastId = "toast-" + Date.now();
  const iconMap = {
    success: "fas fa-check-circle text-success",
    error: "fas fa-exclamation-circle text-danger",
    warning: "fas fa-exclamation-triangle text-warning",
    info: "fas fa-info-circle text-info",
  };

  const toast = `
        <div class="toast align-items-center border-0" id="${toastId}" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="${iconMap[type]} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

  $("body").append(toast);
  const toastElement = new bootstrap.Toast(document.getElementById(toastId));
  toastElement.show();

  setTimeout(() => {
    $(`#${toastId}`).remove();
  }, 5000);
}
