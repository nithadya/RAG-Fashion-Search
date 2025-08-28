/**
 * Enhanced RAG Search with User Preferences and Matching Percentages
 *
 * This script handles:
 * - Intelligent search with user preferences
 * - Matching percentage display
 * - Real-time preference adjustment
 * - Visual feedback for matches
 */

class EnhancedRAGSearch {
  constructor() {
    this.preferences = this.loadPreferences();
    this.searchHistory = [];
    this.init();
  }

  init() {
    this.setupSearchInterface();
    this.setupPreferencePanel();
    this.bindEvents();
  }

  setupSearchInterface() {
    // Create enhanced search container
    const searchContainer =
      document.querySelector(".search-container") ||
      this.createSearchContainer();

    // Add preference toggle button
    const preferenceToggle = document.createElement("button");
    preferenceToggle.className = "btn btn-outline-secondary preference-toggle";
    preferenceToggle.innerHTML = '<i class="fas fa-sliders-h"></i> Preferences';
    preferenceToggle.onclick = () => this.togglePreferencePanel();

    searchContainer.appendChild(preferenceToggle);

    // Add matching indicator
    const matchingIndicator = document.createElement("div");
    matchingIndicator.className = "matching-indicator";
    matchingIndicator.id = "matchingIndicator";
    matchingIndicator.style.display = "none";
    searchContainer.appendChild(matchingIndicator);
  }

  createSearchContainer() {
    const container = document.createElement("div");
    container.className = "enhanced-search-container mb-4";
    container.innerHTML = `
            <div class="search-wrapper">
                <div class="input-group">
                    <input type="text" id="enhancedSearchInput" class="form-control" 
                           placeholder="Describe what you're looking for... (e.g., 'casual blue shirt for office')">
                    <button class="btn btn-primary" id="enhancedSearchBtn">
                        <i class="fas fa-magic"></i> Smart Search
                    </button>
                </div>
            </div>
        `;

    // Insert before product grid
    const productGrid =
      document.querySelector(".product-grid") || document.querySelector(".row");
    if (productGrid) {
      productGrid.parentNode.insertBefore(container, productGrid);
    }

    return container;
  }

  setupPreferencePanel() {
    const panel = document.createElement("div");
    panel.className = "preference-panel";
    panel.id = "preferencePanel";
    panel.style.display = "none";

    panel.innerHTML = `
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-user-cog"></i> Your Style Preferences</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="ragSearch.resetPreferences()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="preference-group">
                                <label class="form-label">Style Preferences</label>
                                <div class="style-tags">
                                    ${this.createStyleTags()}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="preference-group">
                                <label class="form-label">Color Preferences</label>
                                <div class="color-tags">
                                    ${this.createColorTags()}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="preference-group">
                                <label class="form-label">Budget Range (Rs.)</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" id="budgetMin" 
                                               placeholder="Min" min="0" value="${
                                                 this.preferences.budget_min ||
                                                 ""
                                               }">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" id="budgetMax" 
                                               placeholder="Max" min="0" value="${
                                                 this.preferences.budget_max ||
                                                 ""
                                               }">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="preference-group">
                                <label class="form-label">Occasion</label>
                                <select class="form-select" id="occasionSelect">
                                    <option value="">Any Occasion</option>
                                    <option value="casual" ${
                                      this.preferences.occasion === "casual"
                                        ? "selected"
                                        : ""
                                    }>Casual</option>
                                    <option value="formal" ${
                                      this.preferences.occasion === "formal"
                                        ? "selected"
                                        : ""
                                    }>Formal</option>
                                    <option value="party" ${
                                      this.preferences.occasion === "party"
                                        ? "selected"
                                        : ""
                                    }>Party</option>
                                    <option value="office" ${
                                      this.preferences.occasion === "office"
                                        ? "selected"
                                        : ""
                                    }>Office</option>
                                    <option value="sports" ${
                                      this.preferences.occasion === "sports"
                                        ? "selected"
                                        : ""
                                    }>Sports</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <button class="btn btn-primary" onclick="ragSearch.savePreferences()">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </div>
                </div>
            </div>
        `;

    document.body.appendChild(panel);
  }

  createStyleTags() {
    const styles = [
      "casual",
      "formal",
      "party",
      "sporty",
      "ethnic",
      "western",
      "vintage",
      "modern",
      "bohemian",
      "minimalist",
    ];
    const userStyles = this.preferences.style_preferences || [];

    return styles
      .map(
        (style) => `
            <span class="preference-tag ${
              userStyles.includes(style) ? "selected" : ""
            }" 
                  data-type="style" data-value="${style}">
                ${style}
            </span>
        `
      )
      .join("");
  }

  createColorTags() {
    const colors = [
      "red",
      "blue",
      "green",
      "black",
      "white",
      "pink",
      "yellow",
      "orange",
      "purple",
      "brown",
      "grey",
      "navy",
    ];
    const userColors = this.preferences.color_preferences || [];

    return colors
      .map(
        (color) => `
            <span class="preference-tag color-tag ${
              userColors.includes(color) ? "selected" : ""
            }" 
                  data-type="color" data-value="${color}" style="background-color: ${color}; ${
          color === "white" ? "border: 1px solid #ccc;" : ""
        }">
                ${color}
            </span>
        `
      )
      .join("");
  }

  bindEvents() {
    // Enhanced search button
    document.addEventListener("click", (e) => {
      if (
        e.target.id === "enhancedSearchBtn" ||
        e.target.closest("#enhancedSearchBtn")
      ) {
        this.performEnhancedSearch();
      }
    });

    // Enhanced search input enter key
    document.addEventListener("keypress", (e) => {
      if (e.target.id === "enhancedSearchInput" && e.key === "Enter") {
        this.performEnhancedSearch();
      }
    });

    // Preference tag selection
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("preference-tag")) {
        e.target.classList.toggle("selected");
        this.updatePreferences();
      }
    });

    // Preference inputs
    document.addEventListener("change", (e) => {
      if (["budgetMin", "budgetMax", "occasionSelect"].includes(e.target.id)) {
        this.updatePreferences();
      }
    });
  }

  async performEnhancedSearch() {
    const query =
      document.querySelector("#enhancedSearchInput")?.value?.trim() ||
      document.querySelector("#searchInput")?.value?.trim();

    if (!query) {
      this.showError("Please enter a search query");
      return;
    }

    this.showLoading(true);
    this.updateMatchingIndicator("Analyzing your preferences...", "info");

    try {
      const response = await fetch("api/rag_search.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          query: query,
          user_id: this.getCurrentUserId(),
          preferences: this.preferences,
        }),
      });

      const data = await response.json();

      if (data.success) {
        this.displayEnhancedResults(data);
        this.updateSearchHistory(query, data);
        this.updateMatchingIndicator(
          `Found ${data.results.length} matches with ${data.recommendations.high_match_count} high-confidence matches`,
          "success"
        );
      } else {
        throw new Error(data.message || "Search failed");
      }
    } catch (error) {
      console.error("Enhanced search error:", error);
      this.showError("Search failed: " + error.message);
      this.updateMatchingIndicator("Search failed", "error");
    } finally {
      this.showLoading(false);
    }
  }

  displayEnhancedResults(data) {
    const { results, recommendations, rag_metadata } = data;

    // Display search statistics
    this.displaySearchStats(data);

    // Display products with matching percentages
    this.displayProductsWithMatching(results);

    // Display recommendations
    this.displayRecommendations(recommendations);
  }

  displaySearchStats(data) {
    const statsContainer =
      document.querySelector("#searchStats") || this.createSearchStats();

    statsContainer.innerHTML = `
            <div class="search-stats-card">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-number">${data.results.length}</div>
                        <div class="stat-label">Total Matches</div>
                    </div>
                    <div class="col-3">
                        <div class="stat-number text-success">${data.recommendations.high_match_count}</div>
                        <div class="stat-label">High Match (80%+)</div>
                    </div>
                    <div class="col-3">
                        <div class="stat-number text-warning">${data.recommendations.medium_match_count}</div>
                        <div class="stat-label">Medium Match (60-79%)</div>
                    </div>
                    <div class="col-3">
                        <div class="stat-number">${data.rag_metadata.processing_time}s</div>
                        <div class="stat-label">Processing Time</div>
                    </div>
                </div>
            </div>
        `;
  }

  createSearchStats() {
    const container = document.createElement("div");
    container.id = "searchStats";
    container.className = "search-stats mb-4";

    const productGrid =
      document.querySelector(".product-grid") || document.querySelector(".row");
    if (productGrid) {
      productGrid.parentNode.insertBefore(container, productGrid);
    }

    return container;
  }

  displayProductsWithMatching(products) {
    const productGrid =
      document.querySelector(".product-grid") || document.querySelector(".row");

    if (!productGrid) return;

    productGrid.innerHTML = products
      .map((product) => this.createProductCard(product))
      .join("");
  }

  createProductCard(product) {
    const matchingPercentage = product.matching_percentage || 50;
    const matchClass = this.getMatchClass(matchingPercentage);
    const matchReasons = product.match_reasons || [];

    return `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="product-card ${matchClass}">
                    <div class="product-image">
                        <img src="${
                          product.image ||
                          "assets/images/placeholder-product.jpg"
                        }" 
                             alt="${product.name}" class="card-img-top">
                        <div class="matching-badge">
                            <div class="match-percentage">${matchingPercentage}%</div>
                            <div class="match-label">Match</div>
                        </div>
                        ${
                          product.discount_percentage > 0
                            ? `<div class="discount-badge">${product.discount_percentage}% OFF</div>`
                            : ""
                        }
                    </div>
                    
                    <div class="card-body">
                        <h6 class="product-title">${product.name}</h6>
                        <p class="product-category">${
                          product.category_name || ""
                        }</p>
                        
                        <div class="price-section">
                            ${
                              product.discount_price > 0
                                ? `<span class="current-price">Rs. ${product.discount_price}</span>
                               <span class="original-price">Rs. ${product.price}</span>`
                                : `<span class="current-price">Rs. ${product.price}</span>`
                            }
                        </div>
                        
                        ${
                          matchReasons.length > 0
                            ? `
                            <div class="match-reasons">
                                <small class="text-muted">
                                    <i class="fas fa-check-circle text-success"></i>
                                    ${matchReasons.slice(0, 2).join(", ")}
                                </small>
                            </div>
                        `
                            : ""
                        }
                        
                        <div class="product-actions">
                            <button class="btn btn-primary btn-sm" onclick="addToCart(${
                              product.id
                            })">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="viewProduct(${
                              product.id
                            })">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  getMatchClass(percentage) {
    if (percentage >= 80) return "high-match";
    if (percentage >= 60) return "medium-match";
    return "low-match";
  }

  displayRecommendations(recommendations) {
    if (
      !recommendations.suggestions ||
      recommendations.suggestions.length === 0
    )
      return;

    const container =
      document.querySelector("#recommendationsContainer") ||
      this.createRecommendationsContainer();

    container.innerHTML = `
            <div class="recommendations-card">
                <h6><i class="fas fa-lightbulb text-warning"></i> Style Suggestions</h6>
                <ul class="suggestion-list">
                    ${recommendations.suggestions
                      .map(
                        (suggestion) =>
                          `<li><i class="fas fa-arrow-right"></i> ${suggestion}</li>`
                      )
                      .join("")}
                </ul>
            </div>
        `;
  }

  createRecommendationsContainer() {
    const container = document.createElement("div");
    container.id = "recommendationsContainer";
    container.className = "recommendations mb-4";

    const searchStats = document.querySelector("#searchStats");
    if (searchStats) {
      searchStats.parentNode.insertBefore(container, searchStats.nextSibling);
    }

    return container;
  }

  togglePreferencePanel() {
    const panel = document.querySelector("#preferencePanel");
    if (panel.style.display === "none") {
      panel.style.display = "block";
      panel.scrollIntoView({ behavior: "smooth" });
    } else {
      panel.style.display = "none";
    }
  }

  updatePreferences() {
    // Update style preferences
    const styleTags = document.querySelectorAll(
      '.preference-tag[data-type="style"].selected'
    );
    this.preferences.style_preferences = Array.from(styleTags).map(
      (tag) => tag.dataset.value
    );

    // Update color preferences
    const colorTags = document.querySelectorAll(
      '.preference-tag[data-type="color"].selected'
    );
    this.preferences.color_preferences = Array.from(colorTags).map(
      (tag) => tag.dataset.value
    );

    // Update budget
    this.preferences.budget_min =
      parseInt(document.querySelector("#budgetMin")?.value) || 0;
    this.preferences.budget_max =
      parseInt(document.querySelector("#budgetMax")?.value) || 50000;

    // Update occasion
    this.preferences.occasion =
      document.querySelector("#occasionSelect")?.value || "";
  }

  savePreferences() {
    this.updatePreferences();
    localStorage.setItem("stylePreferences", JSON.stringify(this.preferences));

    // Save to server if user is logged in
    if (this.getCurrentUserId() > 0) {
      this.savePreferencesToServer();
    }

    this.showSuccess("Preferences saved successfully!");
  }

  async savePreferencesToServer() {
    try {
      await fetch("api/save_preferences.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: this.getCurrentUserId(),
          preferences: this.preferences,
        }),
      });
    } catch (error) {
      console.error("Failed to save preferences to server:", error);
    }
  }

  resetPreferences() {
    this.preferences = {};
    localStorage.removeItem("stylePreferences");
    this.setupPreferencePanel();
    this.showSuccess("Preferences reset successfully!");
  }

  loadPreferences() {
    const stored = localStorage.getItem("stylePreferences");
    return stored ? JSON.parse(stored) : {};
  }

  getCurrentUserId() {
    // This should be implemented based on your authentication system
    return window.currentUserId || 0;
  }

  updateMatchingIndicator(message, type) {
    const indicator = document.querySelector("#matchingIndicator");
    if (!indicator) return;

    indicator.className = `matching-indicator ${type}`;
    indicator.textContent = message;
    indicator.style.display = "block";

    if (type === "success" || type === "error") {
      setTimeout(() => {
        indicator.style.display = "none";
      }, 5000);
    }
  }

  showLoading(show) {
    const btn = document.querySelector("#enhancedSearchBtn");
    if (btn) {
      if (show) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        btn.disabled = true;
      } else {
        btn.innerHTML = '<i class="fas fa-magic"></i> Smart Search';
        btn.disabled = false;
      }
    }
  }

  showError(message) {
    console.error(message);
    // Implement your error display logic
  }

  showSuccess(message) {
    console.log(message);
    // Implement your success display logic
  }

  updateSearchHistory(query, data) {
    this.searchHistory.unshift({
      query,
      timestamp: new Date(),
      resultCount: data.results.length,
      highMatches: data.recommendations.high_match_count,
    });

    // Keep only last 10 searches
    this.searchHistory = this.searchHistory.slice(0, 10);
  }
}

// Initialize the enhanced RAG search system
let ragSearch;
document.addEventListener("DOMContentLoaded", () => {
  ragSearch = new EnhancedRAGSearch();
});

// Export for global access
window.ragSearch = ragSearch;

/**
 * Global functions for RAG search product interactions
 */

// Global function to add product to cart from RAG search results
function addToCart(productId, quantity = 1) {
  if (!productId) {
    showToast('Invalid product ID', 'error');
    return;
  }

  // Find the button that was clicked to show loading state
  const button = event?.target || document.querySelector(`button[onclick*="addToCart(${productId})"]`);
  const originalHtml = button?.innerHTML || '';
  
  if (button) {
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
  }

  $.ajax({
    url: 'api/cart.php',
    type: 'POST',
    dataType: 'json',
    data: { 
      action: 'add', 
      product_id: productId,
      quantity: quantity
    },
    success: function(response) {
      if (response.success) {
        updateCartCount();
        if (button) {
          button.classList.remove('btn-primary');
          button.classList.add('btn-success');
          button.innerHTML = '<i class="fas fa-check"></i> Added!';
        }
        showToast('Product added to cart successfully!', 'success');
        
        // Reset button after 2 seconds
        setTimeout(() => {
          if (button) {
            button.classList.remove('btn-success');
            button.classList.add('btn-primary');
            button.innerHTML = originalHtml;
            button.disabled = false;
          }
        }, 2000);
      } else {
        showToast(response.message || 'Please login to add items to cart', 'error');
        if (response.redirect) {
          setTimeout(() => {
            window.location.href = 'login.html';
          }, 1500);
        }
        if (button) {
          button.innerHTML = originalHtml;
          button.disabled = false;
        }
      }
    },
    error: function() {
      showToast('Failed to add product to cart. Please try again.', 'error');
      if (button) {
        button.innerHTML = originalHtml;
        button.disabled = false;
      }
    }
  });
}

// Global function to view product details from RAG search results
function viewProduct(productId) {
  if (!productId) {
    showToast('Invalid product ID', 'error');
    return;
  }
  
  // Redirect to product detail page
  window.location.href = `product-detail.html?id=${productId}`;
}

// Helper function to update cart count (if not already defined)
function updateCartCount() {
  if (typeof window.updateCartCount === 'function') {
    window.updateCartCount();
    return;
  }

  // Fallback implementation
  $.ajax({
    url: 'api/cart.php?action=count',
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        const cartCount = response.count || 0;
        $('.cart-count, #cartCount').text(cartCount);
        
        // Update badge visibility
        if (cartCount > 0) {
          $('.cart-count, #cartCount').show();
        } else {
          $('.cart-count, #cartCount').hide();
        }
      }
    },
    error: function() {
      console.error('Failed to update cart count');
    }
  });
}

// Helper function to show toast notifications (if not already defined)
function showToast(message, type = 'info') {
  if (typeof window.showToast === 'function') {
    window.showToast(message, type);
    return;
  }

  // Fallback implementation
  console.log(`Toast (${type}): ${message}`);
  
  // Create a simple toast notification
  const toast = document.createElement('div');
  toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} toast-notification`;
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    padding: 15px;
    border-radius: 5px;
    animation: slideInRight 0.3s ease-out;
  `;
  toast.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
      <span>${message}</span>
      <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
    }
  }, 5000);
}
