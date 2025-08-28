/**
 * Enhanced RAG Search Integration for StyleMe
 * Complete frontend integration with intelligent search, preferences, and matching
 */

class StyleMeRAGSearch {
  constructor() {
    this.ragServiceUrl = "http://localhost:5000";
    this.apiUrl = "api";
    this.currentUserId = this.getCurrentUserId();
    this.userPreferences = {};
    this.isRAGEnabled = true;
    this.searchHistory = [];
    this.init();
  }

  init() {
    this.createEnhancedSearchInterface();
    this.setupEventListeners();
    this.loadUserPreferences();
    this.checkRAGServiceStatus();
  }

  createEnhancedSearchInterface() {
    // Try to find the specific RAG search container first, fallback to first container
    let searchContainer = $("#ragSearchContainer");
    if (searchContainer.length === 0) {
      searchContainer = $(".container").first();
    }

    console.log(
      "🎨 Injecting RAG search interface into:",
      searchContainer.length ? "found container" : "no container found"
    );
    const enhancedSearchHTML = `
            <!-- Enhanced RAG Search Interface -->
            <div id="enhanced-rag-search" class="enhanced-search-container mb-4">
                <!-- Main Search Bar -->
                <div class="intelligent-search-bar">
                    <div class="search-input-group">
                        <div class="search-input-wrapper">
                            <input type="text" 
                                   id="intelligentSearchInput" 
                                   class="form-control intelligent-search-input" 
                                   placeholder="🤖 Describe what you're looking for... (e.g., 'casual blue shirt for office meetings under 5000')"
                                   autocomplete="off">
                            <div class="search-suggestions" id="searchSuggestions" style="display: none;"></div>
                        </div>
                        <button class="btn btn-primary intelligent-search-btn" id="intelligentSearchBtn">
                            <i class="fas fa-brain"></i> AI Search
                        </button>
                        <button class="btn btn-outline-secondary preferences-toggle" id="preferencesToggle" title="Adjust Preferences">
                            <i class="fas fa-sliders-h"></i>
                        </button>
                    </div>
                </div>

                <!-- Preferences Panel -->
                <div id="preferencesPanel" class="preferences-panel" style="display: none;">
                    <div class="preferences-grid">
                        <!-- Style Preferences -->
                        <div class="preference-group">
                            <label class="preference-label">
                                <i class="fas fa-tshirt"></i> Style Preferences
                            </label>
                            <div class="preference-tags" id="stylePreferences">
                                <span class="preference-tag" data-value="casual">Casual</span>
                                <span class="preference-tag" data-value="formal">Formal</span>
                                <span class="preference-tag" data-value="business">Business</span>
                                <span class="preference-tag" data-value="party">Party</span>
                                <span class="preference-tag" data-value="western">Western</span>
                                <span class="preference-tag" data-value="ethnic">Ethnic</span>
                                <span class="preference-tag" data-value="sports">Sports</span>
                                <span class="preference-tag" data-value="trendy">Trendy</span>
                            </div>
                        </div>

                        <!-- Color Preferences -->
                        <div class="preference-group">
                            <label class="preference-label">
                                <i class="fas fa-palette"></i> Color Preferences
                            </label>
                            <div class="preference-tags" id="colorPreferences">
                                <span class="preference-tag color-tag" data-value="black" style="background-color: #000; color: white;">Black</span>
                                <span class="preference-tag color-tag" data-value="white" style="background-color: #fff; color: black; border: 1px solid #ddd;">White</span>
                                <span class="preference-tag color-tag" data-value="blue" style="background-color: #007bff; color: white;">Blue</span>
                                <span class="preference-tag color-tag" data-value="red" style="background-color: #dc3545; color: white;">Red</span>
                                <span class="preference-tag color-tag" data-value="green" style="background-color: #28a745; color: white;">Green</span>
                                <span class="preference-tag color-tag" data-value="grey" style="background-color: #6c757d; color: white;">Grey</span>
                                <span class="preference-tag color-tag" data-value="navy" style="background-color: #000080; color: white;">Navy</span>
                                <span class="preference-tag color-tag" data-value="brown" style="background-color: #8B4513; color: white;">Brown</span>
                            </div>
                        </div>

                        <!-- Budget Range -->
                        <div class="preference-group">
                            <label class="preference-label">
                                <i class="fas fa-rupee-sign"></i> Budget Range
                            </label>
                            <div class="budget-range">
                                <input type="range" id="budgetMinSlider" min="500" max="50000" value="1000" step="500" class="budget-slider">
                                <input type="range" id="budgetMaxSlider" min="500" max="50000" value="10000" step="500" class="budget-slider">
                                <div class="budget-display">
                                    <span id="budgetDisplay">Rs.1,000 - Rs.10,000</span>
                                </div>
                            </div>
                        </div>

                        <!-- Occasion -->
                        <div class="preference-group">
                            <label class="preference-label">
                                <i class="fas fa-calendar-alt"></i> Occasion
                            </label>
                            <div class="preference-tags" id="occasionPreferences">
                                <span class="preference-tag" data-value="office">Office</span>
                                <span class="preference-tag" data-value="casual">Daily Casual</span>
                                <span class="preference-tag" data-value="party">Party</span>
                                <span class="preference-tag" data-value="wedding">Wedding</span>
                                <span class="preference-tag" data-value="sports">Sports</span>
                                <span class="preference-tag" data-value="travel">Travel</span>
                                <span class="preference-tag" data-value="date">Date</span>
                                <span class="preference-tag" data-value="meeting">Business Meeting</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="preference-actions">
                            <button class="btn btn-sm btn-success" id="savePreferences">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="resetPreferences">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Status -->
                <div id="searchStatus" class="search-status" style="display: none;">
                    <div class="status-content">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="status-text">AI is finding perfect matches for you...</span>
                    </div>
                </div>

                <!-- Search Results Summary -->
                <div id="searchResultsSummary" class="search-results-summary" style="display: none;">
                    <div class="summary-content">
                        <div class="summary-stats">
                            <span class="stat-item">
                                <i class="fas fa-search"></i>
                                <span id="resultsCount">0</span> Results
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span id="processingTime">0</span>s
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-brain"></i>
                                AI Enhanced
                            </span>
                        </div>
                        <div class="summary-query">
                            <strong>Enhanced Query:</strong> <span id="enhancedQuery"></span>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Insert the search interface into the designated container
    searchContainer.prepend(enhancedSearchHTML);
    console.log("✅ RAG search interface injected successfully");
  }

  setupEventListeners() {
    const searchInput = $("#intelligentSearchInput");

    // Intelligent search button
    $("#intelligentSearchBtn").on("click", () => {
      this.performIntelligentSearch();
    });

    // Search input enter key
    searchInput.on("keypress", (e) => {
      if (e.which === 13) {
        this.performIntelligentSearch();
      }
    });

    // Auto-search as user types (debounced)
    let searchTimeout;
    searchInput.on("input", (e) => {
      clearTimeout(searchTimeout);
      const query = e.target.value.trim();

      console.log("🔤 Search input changed:", query); // Debug log

      if (query.length > 2) {
        // Show loading indicator immediately
        $("#productList").html(
          '<div class="col-12 text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2">Searching products...</small></div>'
        );
        $("#searchResultsSummary").hide();

        // Debounced search after 600ms of no typing
        searchTimeout = setTimeout(() => {
          console.log("🚀 Triggering async search for:", query); // Debug log
          this.performIntelligentSearch(query);
        }, 600);
      } else if (query.length === 0) {
        // Clear results when search is empty
        $("#productList").empty();
        $("#searchResultsSummary").hide();
        console.log("🧹 Search cleared"); // Debug log
      } else {
        // Show suggestion for more characters
        $("#productList").html(
          '<div class="col-12 text-center text-muted py-4"><i class="fas fa-keyboard"></i><br>Type at least 3 characters to search...</div>'
        );
      }

      this.handleSearchSuggestions(query);
    });

    // Preferences toggle
    $("#preferencesToggle").on("click", () => {
      $("#preferencesPanel").slideToggle(300);
    });

    // Preference tags selection
    $(".preference-tag").on("click", (e) => {
      const tag = $(e.target);
      tag.toggleClass("active");
      this.updateUserPreferences();
    });

    // Budget sliders
    $("#budgetMinSlider, #budgetMaxSlider").on("input", () => {
      this.updateBudgetDisplay();
      this.updateUserPreferences();
    });

    // Save preferences
    $("#savePreferences").on("click", () => {
      this.saveUserPreferences();
    });

    // Reset preferences
    $("#resetPreferences").on("click", () => {
      this.resetPreferences();
    });

    // Initialize filter tokens
    this.initializeFilterTokens();

    // Initialize voice search if available
    this.initializeVoiceSearch();
  }

  initializeFilterTokens() {
    // Add filter tokens container after search input
    const filterTokensHTML = `
      <div id="filterTokensContainer" class="filter-tokens-container mt-3" style="display: none;">
        <!-- Quick Filters -->
        <div class="quick-filters">
          <div class="filter-category">
            <span class="filter-label">Style:</span>
            <div class="filter-tokens" id="styleTokens">
              <span class="filter-token" data-type="style" data-value="casual">Casual</span>
              <span class="filter-token" data-type="style" data-value="formal">Formal</span>
              <span class="filter-token" data-type="style" data-value="ethnic">Ethnic</span>
              <span class="filter-token" data-type="style" data-value="western">Western</span>
              <span class="filter-token" data-type="style" data-value="party">Party</span>
            </div>
          </div>
          
          <div class="filter-category">
            <span class="filter-label">Color:</span>
            <div class="filter-tokens" id="colorTokens">
              <span class="filter-token" data-type="color" data-value="black">Black</span>
              <span class="filter-token" data-type="color" data-value="white">White</span>
              <span class="filter-token" data-type="color" data-value="blue">Blue</span>
              <span class="filter-token" data-type="color" data-value="red">Red</span>
              <span class="filter-token" data-type="color" data-value="green">Green</span>
            </div>
          </div>
          
          <div class="filter-category">
            <span class="filter-label">Price Range:</span>
            <div class="filter-tokens" id="priceTokens">
              <span class="filter-token" data-type="price" data-value="under-1000">Under ₹1,000</span>
              <span class="filter-token" data-type="price" data-value="1000-5000">₹1,000 - ₹5,000</span>
              <span class="filter-token" data-type="price" data-value="5000-10000">₹5,000 - ₹10,000</span>
              <span class="filter-token" data-type="price" data-value="above-10000">Above ₹10,000</span>
            </div>
          </div>
          
          <div class="filter-category">
            <span class="filter-label">Occasion:</span>
            <div class="filter-tokens" id="occasionTokens">
              <span class="filter-token" data-type="occasion" data-value="office">Office</span>
              <span class="filter-token" data-type="occasion" data-value="party">Party</span>
              <span class="filter-token" data-type="occasion" data-value="wedding">Wedding</span>
              <span class="filter-token" data-type="occasion" data-value="casual">Daily Wear</span>
            </div>
          </div>
        </div>
        
        <!-- Active Filters -->
        <div id="activeFilters" class="active-filters mt-2" style="display: none;">
          <span class="active-filters-label">Active Filters:</span>
          <div class="active-filters-container" id="activeFiltersContainer"></div>
          <button class="btn btn-sm btn-outline-secondary clear-filters-btn" id="clearFilters">
            Clear All
          </button>
        </div>
        
        <!-- Toggle Filters Button -->
        <div class="text-center mt-2">
          <button class="btn btn-sm btn-outline-primary" id="toggleFilters">
            <i class="fas fa-filter"></i> Show Filters
          </button>
        </div>
      </div>
    `;

    $("#enhanced-rag-search").append(filterTokensHTML);

    // Setup filter token event listeners
    this.setupFilterTokenEvents();
  }

  setupFilterTokenEvents() {
    // Toggle filters visibility
    $("#toggleFilters").on("click", () => {
      const container = $(".quick-filters");
      const btn = $("#toggleFilters");

      if (container.is(":visible")) {
        container.slideUp();
        btn.html('<i class="fas fa-filter"></i> Show Filters');
      } else {
        container.slideDown();
        btn.html('<i class="fas fa-filter"></i> Hide Filters');
      }
    });

    // Filter token selection
    $(document).on("click", ".filter-token", (e) => {
      const token = $(e.target);
      const type = token.data("type");
      const value = token.data("value");

      // Toggle token state
      token.toggleClass("active");

      // Update active filters display
      this.updateActiveFilters();

      // Auto-update search input
      this.updateSearchInputWithFilters();
    });

    // Clear all filters
    $("#clearFilters").on("click", () => {
      $(".filter-token").removeClass("active");
      this.updateActiveFilters();
      this.updateSearchInputWithFilters();
    });
  }

  updateActiveFilters() {
    const activeTokens = $(".filter-token.active");
    const activeFiltersContainer = $("#activeFiltersContainer");
    const activeFiltersDiv = $("#activeFilters");

    if (activeTokens.length === 0) {
      activeFiltersDiv.hide();
      return;
    }

    activeFiltersDiv.show();
    activeFiltersContainer.empty();

    activeTokens.each((index, token) => {
      const $token = $(token);
      const type = $token.data("type");
      const value = $token.data("value");
      const text = $token.text();

      const activeFilter = $(`
        <span class="active-filter" data-type="${type}" data-value="${value}">
          ${text}
          <i class="fas fa-times" data-remove="${type}-${value}"></i>
        </span>
      `);

      activeFiltersContainer.append(activeFilter);
    });

    // Handle individual filter removal
    $(".active-filter i").on("click", (e) => {
      const removeData = $(e.target).data("remove").split("-");
      const type = removeData[0];
      const value = removeData.slice(1).join("-");

      $(
        `.filter-token[data-type="${type}"][data-value="${value}"]`
      ).removeClass("active");
      this.updateActiveFilters();
      this.updateSearchInputWithFilters();
    });
  }

  updateSearchInputWithFilters() {
    const activeTokens = $(".filter-token.active");
    const currentInput = $("#intelligentSearchInput").val();
    const baseQuery = currentInput
      .replace(/\s*(style|color|price|occasion):\s*\w+/gi, "")
      .trim();

    let filterParts = [];

    activeTokens.each((index, token) => {
      const $token = $(token);
      const type = $token.data("type");
      const value = $token.data("value");

      // Convert filter tokens to natural language
      switch (type) {
        case "style":
          filterParts.push(value);
          break;
        case "color":
          filterParts.push(value + " color");
          break;
        case "price":
          switch (value) {
            case "under-1000":
              filterParts.push("under 1000 rupees");
              break;
            case "1000-5000":
              filterParts.push("between 1000 to 5000 rupees");
              break;
            case "5000-10000":
              filterParts.push("between 5000 to 10000 rupees");
              break;
            case "above-10000":
              filterParts.push("above 10000 rupees");
              break;
          }
          break;
        case "occasion":
          filterParts.push("for " + value);
          break;
      }
    });

    let enhancedQuery = baseQuery;
    if (filterParts.length > 0) {
      enhancedQuery = baseQuery + " " + filterParts.join(" ");
    }

    $("#intelligentSearchInput").val(enhancedQuery.trim());
  }

  initializeVoiceSearch() {
    // Add voice search button if Web Speech API is supported
    if ("webkitSpeechRecognition" in window || "SpeechRecognition" in window) {
      const voiceButton = `
        <button class="btn btn-outline-info" id="voiceSearchBtn" title="Voice Search">
          <i class="fas fa-microphone"></i>
        </button>
      `;

      $(".search-input-group .intelligent-search-btn").before(voiceButton);

      // Initialize speech recognition
      this.initSpeechRecognition();
    }
  }

  initSpeechRecognition() {
    const SpeechRecognition =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
      this.recognition = new SpeechRecognition();
      this.recognition.continuous = false;
      this.recognition.interimResults = false;
      this.recognition.lang = "en-US";

      this.recognition.onstart = () => {
        $("#voiceSearchBtn").addClass("recording");
        $("#voiceSearchBtn i").removeClass("fa-microphone").addClass("fa-stop");
        this.showToast("Listening... Speak your search query", "info");
      };

      this.recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        $("#intelligentSearchInput").val(transcript);

        // Show what was heard
        this.showToast(`Heard: "${transcript}"`, "success");

        // Auto-perform search after a short delay
        setTimeout(() => {
          this.performIntelligentSearch();
        }, 1000);
      };

      this.recognition.onerror = (event) => {
        console.error("Speech recognition error:", event.error);
        this.showToast("Voice search error. Please try again.", "error");
        this.resetVoiceButton();
      };

      this.recognition.onend = () => {
        this.resetVoiceButton();
      };

      // Voice search button click handler
      $("#voiceSearchBtn").on("click", () => {
        if ($("#voiceSearchBtn").hasClass("recording")) {
          this.recognition.stop();
        } else {
          this.recognition.start();
        }
      });
    }
  }

  resetVoiceButton() {
    $("#voiceSearchBtn").removeClass("recording");
    $("#voiceSearchBtn i").removeClass("fa-stop").addClass("fa-microphone");
  }

  showToast(message, type = "info") {
    // Create toast if it doesn't exist
    if ($("#searchToast").length === 0) {
      const toastHTML = `
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
          <div id="searchToast" class="toast" role="alert">
            <div class="toast-header">
              <strong class="me-auto">StyleMe Search</strong>
              <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastBody"></div>
          </div>
        </div>
      `;
      $("body").append(toastHTML);
    }

    // Update toast content and show
    $("#toastBody").text(message);
    const toast = new bootstrap.Toast($("#searchToast")[0]);
    toast.show();
  }

  async performIntelligentSearch(searchQuery = null) {
    const query = searchQuery || $("#intelligentSearchInput").val().trim();

    if (!query) {
      this.showAlert("Please enter what you're looking for!", "warning");
      return;
    }

    // Update input field if query was passed as parameter
    if (searchQuery) {
      $("#intelligentSearchInput").val(searchQuery);
    }

    // Show loading status
    this.showSearchStatus(true);

    try {
      let results;

      if (this.isRAGEnabled) {
        // Try RAG search first
        results = await this.performRAGSearch(query);
      } else {
        // Fallback to regular search
        results = await this.performRegularSearch(query);
      }

      if (results) {
        this.displaySearchResults(results);
        this.saveToSearchHistory(query, results);
      }
    } catch (error) {
      console.error("Search error:", error);

      // Fallback to regular search if RAG fails
      if (this.isRAGEnabled) {
        console.log("RAG search failed, falling back to regular search");
        this.isRAGEnabled = false;
        try {
          const fallbackResults = await this.performRegularSearch(query);
          this.displaySearchResults(fallbackResults, true);
        } catch (fallbackError) {
          this.showAlert(
            "Search service temporarily unavailable. Please try again later.",
            "error"
          );
        }
      } else {
        this.showAlert("Search failed. Please try again.", "error");
      }
    } finally {
      this.showSearchStatus(false);
    }
  }

  async performRAGSearch(query) {
    // Clear previous results
    $("#productList").empty();
    $("#searchResultsSummary").hide();
    $(".fallback-indicator").remove();

    const preferences = this.getUserPreferencesData();

    const payload = {
      query: query,
      preferences: preferences,
      user_id: this.currentUserId,
      search_type: "enhanced",
    };

    console.log("🔍 Sending RAG search request:", payload);

    // Call the PHP API endpoint instead of RAG service directly
    const response = await fetch(`${this.apiUrl}/rag_search.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
      timeout: 15000,
    });

    console.log("📡 RAG API response status:", response.status);

    // Always try to parse the response as JSON first
    let data;
    try {
      data = await response.json();
      console.log("📦 RAG API response data:", data);
    } catch (jsonError) {
      console.error("JSON parse error:", jsonError);
      throw new Error(
        `Invalid JSON response from RAG API: ${jsonError.message}`
      );
    }

    // Handle error responses from the server
    if (!response.ok || !data.success) {
      const errorMessage = data.message || `RAG API error: ${response.status}`;
      console.error("RAG API error:", errorMessage);
      throw new Error(errorMessage);
    }

    // Handle successful responses from the PHP API
    console.log(
      "✅ RAG search successful, products found:",
      data.products?.length || 0
    );

    return {
      products: data.products || [],
      matching_scores: data.matching_scores || [],
      enhanced_query: data.processed_query || query,
      processing_time: data.processing_time || 0,
      ai_response: data.ai_response || "",
      total: data.results_count || 0,
      source: "rag",
      search_type: data.search_type || "rag",
    };
  }

  async performRegularSearch(query) {
    // Get user preferences to enhance regular search
    const preferences = this.getUserPreferencesData();

    // Build search URL with preferences
    let searchUrl = `${this.apiUrl}/products.php?search=${encodeURIComponent(
      query
    )}&limit=20`;

    // Add budget filter if specified
    if (preferences.budget_min > 500 || preferences.budget_max < 50000) {
      searchUrl += `&budget_min=${preferences.budget_min}&budget_max=${preferences.budget_max}`;
    }

    // Add category filter based on style preferences
    if (preferences.style_preferences.length > 0) {
      searchUrl += `&styles=${preferences.style_preferences.join(",")}`;
    }

    // Add color filter if specified
    if (preferences.color_preferences.length > 0) {
      searchUrl += `&colors=${preferences.color_preferences.join(",")}`;
    }

    // Add occasion if specified
    if (preferences.occasion && preferences.occasion !== "casual") {
      searchUrl += `&occasion=${preferences.occasion}`;
    }

    const response = await fetch(searchUrl, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`Regular search error: ${response.status}`);
    }

    const data = await response.json();

    return {
      products: data.products || [],
      matching_scores: [], // No matching scores for regular search
      enhanced_query: query,
      processing_time: 0,
      ai_response: "",
      total: data.total || 0,
      source: "regular",
    };
  }

  async getProductDetails(productIds) {
    if (!productIds || productIds.length === 0) {
      return [];
    }

    try {
      const response = await fetch(
        `${this.apiUrl}/products.php?ids=${productIds.join(",")}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (!response.ok) {
        throw new Error(`Product details error: ${response.status}`);
      }

      const data = await response.json();
      return data.products || [];
    } catch (error) {
      console.error("Error fetching product details:", error);
      return [];
    }
  }

  displaySearchResults(results, isFallback = false) {
    const {
      products,
      matching_scores,
      enhanced_query,
      processing_time,
      source,
    } = results;

    // Update results summary
    this.showSearchResultsSummary({
      count: products.length,
      processingTime: processing_time,
      enhancedQuery: enhanced_query,
      isFallback,
      source,
    });

    // Render products with matching scores
    this.renderEnhancedProducts(products, matching_scores, source);

    // Update URL
    this.updateURLWithSearch($("#intelligentSearchInput").val());
  }

  renderEnhancedProducts(products, matchingScores = [], source = "regular") {
    const productContainer = $("#productList");
    productContainer.empty();

    if (products.length === 0) {
      productContainer.html(`
                <div class="col-12">
                    <div class="no-products text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try different keywords or adjust your preferences</p>
                        <button class="btn btn-primary" id="adjustPreferencesBtn">
                            <i class="fas fa-sliders-h"></i> Adjust Preferences
                        </button>
                    </div>
                </div>
            `);
      return;
    }

    // Show results summary
    const totalScore = products.reduce(
      (sum, p) => sum + (p.similarity_score || 0),
      0
    );
    const avgScore = totalScore / products.length;
    const maxScore = Math.max(...products.map((p) => p.similarity_score || 0));
    const avgPercentage = Math.round((avgScore / 50) * 100); // 50 is estimated max score

    let summaryHTML = `
      <div class="alert alert-info">
        <div class="row align-items-center">
          <div class="col-md-8">
            <i class="fas fa-info-circle"></i> Found ${products.length} products
            ${source === "rag" ? " using AI-powered search" : ""}
          </div>
          ${
            source === "rag"
              ? `
          <div class="col-md-4">
            <div class="text-end">
              <small class="text-muted">Average Match:</small>
              <strong class="ms-1" style="color: ${
                avgPercentage >= 70
                  ? "#198754"
                  : avgPercentage >= 50
                  ? "#0dcaf0"
                  : "#ffc107"
              }">
                ${avgPercentage}%
              </strong>
              <div class="progress mt-1" style="height: 6px;">
                <div class="progress-bar" role="progressbar" 
                     style="width: ${avgPercentage}%; background-color: ${
                  avgPercentage >= 70
                    ? "#198754"
                    : avgPercentage >= 50
                    ? "#0dcaf0"
                    : "#ffc107"
                }"
                     aria-valuenow="${avgPercentage}" aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>
          `
              : ""
          }
        </div>
      </div>
    `;

    $("#searchResultsSummary").html(summaryHTML).show();

    products.forEach((product, index) => {
      // Use the similarity_score from backend or fallback to matchingScores array
      const matchingScore =
        product.similarity_score || matchingScores[index] || 0;

      console.log(
        `🔍 Product ${index}: "${
          product.name
        }", Raw Score: ${matchingScore}, From: ${
          product.similarity_score ? "product property" : "matchingScores array"
        }`
      );

      // Convert raw score to percentage (normalize based on max possible score)
      // Backend scoring: name matches (10 points each) + color (8) + category (6) + brand (5) + preferences (7)
      const maxPossibleScore = 50; // Estimated maximum for normalization
      const normalizedScore = Math.min(matchingScore / maxPossibleScore, 1.0);
      const matchPercentage = Math.round(normalizedScore * 100);

      console.log(
        `📊 Normalized Score: ${normalizedScore.toFixed(
          2
        )}, Percentage: ${matchPercentage}%`
      );

      // Enhanced match badge for RAG results with more granular scoring
      let matchBadge = "";
      let matchClass = "";
      let matchIcon = "";

      if (source === "rag" && matchingScore > 0) {
        console.log(
          `🎯 Creating match badge for RAG result with score ${matchingScore}`
        );

        if (matchPercentage >= 80) {
          matchBadge = `<div class="match-badge badge bg-success mb-2"><i class="fas fa-star"></i> ${matchPercentage}% Perfect Match</div>`;
          matchClass = "border-success shadow-sm";
          matchIcon = "fas fa-star text-success";
        } else if (matchPercentage >= 60) {
          matchBadge = `<div class="match-badge badge bg-info mb-2"><i class="fas fa-thumbs-up"></i> ${matchPercentage}% Great Match</div>`;
          matchClass = "border-info shadow-sm";
          matchIcon = "fas fa-thumbs-up text-info";
        } else if (matchPercentage >= 40) {
          matchBadge = `<div class="match-badge badge bg-warning mb-2"><i class="fas fa-check"></i> ${matchPercentage}% Good Match</div>`;
          matchClass = "border-warning shadow-sm";
          matchIcon = "fas fa-check text-warning";
        } else if (matchPercentage >= 20) {
          matchBadge = `<div class="match-badge badge bg-secondary mb-2"><i class="fas fa-search"></i> ${matchPercentage}% Partial Match</div>`;
          matchClass = "border-secondary";
          matchIcon = "fas fa-search text-secondary";
        } else if (matchPercentage >= 10) {
          matchBadge = `<div class="match-badge badge bg-light text-dark mb-2"><i class="fas fa-question"></i> ${matchPercentage}% Related</div>`;
          matchClass = "border-light";
          matchIcon = "fas fa-question text-muted";
        } else {
          matchBadge = `<div class="match-badge badge bg-light text-dark mb-2"><i class="fas fa-info"></i> ${matchPercentage}% Found</div>`;
          matchClass = "border-light";
          matchIcon = "fas fa-info text-muted";
        }

        console.log(
          `✨ Match badge created: ${matchBadge.substring(0, 50)}...`
        );
      } else {
        console.log(
          `❌ No match badge - Source: ${source}, Score: ${matchingScore}`
        );
      }

      // Discount badge
      const discountPrice = parseFloat(product.discount_price || 0);
      const originalPrice = parseFloat(product.price || 0);
      const hasDiscount = discountPrice > 0 && discountPrice < originalPrice;
      const discountPercentage = hasDiscount
        ? Math.round(((originalPrice - discountPrice) / originalPrice) * 100)
        : 0;

      const discountBadge = hasDiscount
        ? `<div class="discount-badge badge bg-danger">${discountPercentage}% OFF</div>`
        : "";

      // Handle images with proper database paths
      let imageUrl = "assets/images/placeholder-product.jpg"; // Default fallback

      if (product.image1) {
        // Check if image1 exists in uploads folder
        imageUrl = `assets/uploads/${product.image1}`;
      } else if (product.image2) {
        imageUrl = `assets/uploads/${product.image2}`;
      } else if (product.image3) {
        imageUrl = `assets/uploads/${product.image3}`;
      }

      const imageTag = `<img src="${imageUrl}" alt="${product.name}" class="card-img-top product-image" 
                            style="height: 200px; object-fit: cover;" 
                            onerror="this.src='assets/images/placeholder-product.jpg'">`;

      // Price display
      const currentPrice = hasDiscount ? discountPrice : originalPrice;
      const priceDisplay = hasDiscount
        ? `<span class="text-danger fw-bold">Rs.${currentPrice.toLocaleString()}</span>
           <span class="text-decoration-line-through text-muted ms-2">Rs.${originalPrice.toLocaleString()}</span>`
        : `<span class="fw-bold">Rs.${currentPrice.toLocaleString()}</span>`;

      // Clean product card with essential data only
      const productCard = `
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
          <div class="card h-100 ${matchClass}" data-product-id="${product.id}">
            <div class="position-relative">
              ${imageTag}
              ${matchBadge}
              ${discountBadge}
            </div>
            <div class="card-body d-flex flex-column">
              <div class="mb-2">
                <small class="text-muted">${
                  product.category_name || "Fashion"
                }</small>
                ${
                  product.brand
                    ? `<small class="text-muted"> • ${product.brand}</small>`
                    : ""
                }
              </div>
              <h6 class="card-title">${product.name}</h6>
              <div class="price mb-2">
                ${priceDisplay}
              </div>
              
              ${
                source === "rag" && matchingScore > 0
                  ? `
              <div class="match-info mb-2">
                <div class="d-flex align-items-center justify-content-between">
                  <small class="text-muted">
                    <i class="${matchIcon}"></i> AI Match Score
                  </small>
                  <small class="fw-bold" style="color: ${
                    matchPercentage >= 70
                      ? "#198754"
                      : matchPercentage >= 50
                      ? "#0dcaf0"
                      : matchPercentage >= 30
                      ? "#ffc107"
                      : "#6c757d"
                  }">
                    ${matchPercentage}%
                  </small>
                </div>
                <div class="progress progress-sm mt-1" style="height: 4px;">
                  <div class="progress-bar" role="progressbar" 
                       style="width: ${matchPercentage}%; background-color: ${
                      matchPercentage >= 70
                        ? "#198754"
                        : matchPercentage >= 50
                        ? "#0dcaf0"
                        : matchPercentage >= 30
                        ? "#ffc107"
                        : "#6c757d"
                    }"
                       aria-valuenow="${matchPercentage}" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
                ${
                  product.match_explanation
                    ? `
                <div class="match-details mt-1">
                  <small class="text-muted" style="font-size: 0.7rem; line-height: 1.2;">
                    <i class="fas fa-info-circle"></i> ${product.match_explanation}
                  </small>
                </div>
                `
                    : ""
                }
              </div>
              `
                  : ""
              }
              
              <div class="mt-auto">
                <div class="d-grid gap-2">
                  <button class="btn btn-primary btn-sm add-to-cart-btn" data-product-id="${
                    product.id
                  }">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                  </button>
                  <div class="d-flex gap-1">
                    <button class="btn btn-outline-secondary btn-sm flex-fill add-to-wishlist-btn" data-product-id="${
                      product.id
                    }">
                      <i class="far fa-heart"></i>
                    </button>
                    <a href="product-detail.html?id=${
                      product.id
                    }" class="btn btn-outline-primary btn-sm flex-fill">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;

      productContainer.append(productCard);
    });

    // Setup product action event listeners
    this.setupProductActions();
  }

  showSearchResultsSummary(data) {
    const { count, processingTime, enhancedQuery, isFallback, source } = data;

    // Update page title with search results
    const query = $("#intelligentSearchInput").val().trim();
    if (query) {
      $("#productListingTitle").html(
        `🔍 AI Search Results for: <em>"${query}"</em> (${count} items)`
      );
    }

    $("#resultsCount").text(count);
    $("#processingTime").text(processingTime.toFixed(2));
    $("#enhancedQuery").text(enhancedQuery);

    // Add fallback indicator
    if (isFallback) {
      $(".summary-stats").append(
        '<span class="stat-item fallback-indicator"><i class="fas fa-exclamation-triangle text-warning"></i> Fallback Mode</span>'
      );
    }

    // Add source indicator
    const sourceIcon = source === "rag" ? "fas fa-brain" : "fas fa-search";
    const sourceText = source === "rag" ? "AI Enhanced" : "Regular Search";
    $(".summary-stats .stat-item:last").html(
      `<i class="${sourceIcon}"></i> ${sourceText}`
    );

    $("#searchResultsSummary").slideDown(300);
  }

  showSearchStatus(show) {
    if (show) {
      $("#searchStatus").slideDown(200);
    } else {
      $("#searchStatus").slideUp(200);
    }
  }

  getUserPreferencesData() {
    const preferences = {
      style_preferences: [],
      color_preferences: [],
      budget_min: parseInt($("#budgetMinSlider").val()),
      budget_max: parseInt($("#budgetMaxSlider").val()),
      occasion: "casual",
    };

    // Collect active style preferences
    $("#stylePreferences .preference-tag.active").each(function () {
      preferences.style_preferences.push($(this).data("value"));
    });

    // Collect active color preferences
    $("#colorPreferences .preference-tag.active").each(function () {
      preferences.color_preferences.push($(this).data("value"));
    });

    // Get occasion (single selection)
    const activeOccasion = $("#occasionPreferences .preference-tag.active");
    if (activeOccasion.length > 0) {
      preferences.occasion = activeOccasion.first().data("value");
    }

    return preferences;
  }

  async loadUserPreferences() {
    // Only load preferences if user is logged in
    if (!this.currentUserId || this.currentUserId <= 0) {
      console.log("No valid user ID, skipping preferences load");
      return;
    }

    try {
      const response = await fetch(
        `${this.apiUrl}/get_user_preferences.php?user_id=${this.currentUserId}`
      );

      if (!response.ok) {
        console.log("HTTP error:", response.status, response.statusText);
        return;
      }

      const responseText = await response.text();

      // Check if response is empty
      if (!responseText.trim()) {
        console.log("Empty response from preferences API");
        return;
      }

      // Try to parse JSON
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error("JSON parse error:", parseError);
        console.error("Response text:", responseText);
        return;
      }

      if (data.success && data.preferences) {
        this.applyPreferencesToUI(data.preferences);
        console.log("User preferences loaded successfully");
      } else {
        console.log("API returned unsuccessful response:", data);
      }
    } catch (error) {
      console.log("Could not load user preferences:", error);
    }
  }

  applyPreferencesToUI(preferences) {
    // Apply style preferences
    if (preferences.style_preferences) {
      preferences.style_preferences.forEach((style) => {
        $(`#stylePreferences .preference-tag[data-value="${style}"]`).addClass(
          "active"
        );
      });
    }

    // Apply color preferences
    if (preferences.color_preferences) {
      preferences.color_preferences.forEach((color) => {
        $(`#colorPreferences .preference-tag[data-value="${color}"]`).addClass(
          "active"
        );
      });
    }

    // Apply budget range
    if (preferences.budget_min && preferences.budget_max) {
      $("#budgetMinSlider").val(preferences.budget_min);
      $("#budgetMaxSlider").val(preferences.budget_max);
      this.updateBudgetDisplay();
    }

    // Apply occasion
    if (preferences.occasion) {
      $(
        `#occasionPreferences .preference-tag[data-value="${preferences.occasion}"]`
      ).addClass("active");
    }

    // Apply season
    if (preferences.season) {
      $(
        `#seasonPreferences .preference-tag[data-value="${preferences.season}"]`
      ).addClass("active");
    }
  }

  async saveUserPreferences() {
    // Skip saving for guest users (those without valid user ID)
    if (!this.currentUserId || this.currentUserId <= 0) {
      console.log("Guest user - preferences not saved to database");
      return;
    }

    const preferences = this.getUserPreferencesData();

    try {
      const response = await fetch(`${this.apiUrl}/save_preferences.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          user_id: userId,
          preferences: preferences,
        }),
      });

      const data = await response.json();

      if (data.success) {
        this.showAlert("Preferences saved successfully!", "success");
        this.userPreferences = preferences;
      } else {
        console.error("Save preferences error:", data);
        this.showAlert(
          `Failed to save preferences: ${data.message || "Unknown error"}`,
          "error"
        );
      }
    } catch (error) {
      console.error("Error saving preferences:", error);
      this.showAlert("Error saving preferences", "error");
    }
  }

  updateUserPreferences() {
    this.userPreferences = this.getUserPreferencesData();
  }

  updateBudgetDisplay() {
    const min = parseInt($("#budgetMinSlider").val());
    const max = parseInt($("#budgetMaxSlider").val());

    // Ensure min is not greater than max
    if (min > max) {
      $("#budgetMinSlider").val(max);
    }

    $("#budgetDisplay").text(
      `Rs.${min.toLocaleString()} - Rs.${max.toLocaleString()}`
    );
  }

  resetPreferences() {
    $(".preference-tag").removeClass("active");
    $("#budgetMinSlider").val(1000);
    $("#budgetMaxSlider").val(10000);
    this.updateBudgetDisplay();
    this.updateUserPreferences();
  }

  async checkRAGServiceStatus() {
    try {
      const response = await fetch(`${this.ragServiceUrl}/`, { timeout: 5000 });
      if (response.ok) {
        const data = await response.json();
        this.isRAGEnabled = data.rag_system === "initialized";

        if (this.isRAGEnabled) {
          this.showServiceStatus("🤖 AI Search Active", "success");
        } else {
          this.showServiceStatus("🔍 Basic Search Mode", "info");
        }
      }
    } catch (error) {
      console.log("RAG service not available, using fallback search");
      this.isRAGEnabled = false;
      this.showServiceStatus("🔍 Basic Search Mode", "info");
    }
  }

  showServiceStatus(message, type) {
    const statusHtml = `
            <div class="service-status ${type}" style="position: fixed; top: 10px; right: 10px; z-index: 1050; padding: 8px 12px; border-radius: 4px; font-size: 12px;">
                ${message}
            </div>
        `;
    $("body").append(statusHtml);

    setTimeout(() => {
      $(".service-status").fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  showAlert(message, type) {
    const alertClass =
      {
        success: "alert-success",
        error: "alert-danger",
        warning: "alert-warning",
        info: "alert-info",
      }[type] || "alert-info";

    const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show search-alert" 
                 style="position: fixed; top: 70px; right: 10px; z-index: 1050; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

    $("body").append(alertHtml);

    setTimeout(() => {
      $(".search-alert").alert("close");
    }, 5000);
  }

  getCurrentUserId() {
    // Try to get user ID from various sources
    const user = JSON.parse(
      localStorage.getItem("user") || sessionStorage.getItem("user") || "null"
    );
    return user && user.user_id ? parseInt(user.user_id) : null; // Return null for guests instead of random ID
  }

  generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

    let html = "";

    // Full stars
    for (let i = 0; i < fullStars; i++) {
      html += '<i class="fas fa-star text-warning"></i>';
    }

    // Half star
    if (hasHalfStar) {
      html += '<i class="fas fa-star-half-alt text-warning"></i>';
    }

    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
      html += '<i class="far fa-star text-warning"></i>';
    }

    return html;
  }

  setupProductActions() {
    // Add to cart functionality
    $(".add-to-cart-btn")
      .off("click")
      .on("click", async function (e) {
        e.preventDefault();
        const productId = $(this).data("product-id");
        const $btn = $(this);
        const originalHtml = $btn.html();

        if (!productId) {
          alert("Error: Invalid product ID");
          return;
        }

        // Show loading state
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

        try {
          const response = await fetch("api/cart.php", {
            method: "POST",
            headers: { 
              "Content-Type": "application/json",
              "Accept": "application/json"
            },
            body: JSON.stringify({
              action: "add",
              product_id: productId,
              quantity: 1,
            }),
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();

          if (data.success) {
            $btn
              .html('<i class="fas fa-check"></i> Added!')
              .addClass("btn-success")
              .removeClass("btn-primary");
            
            // Update cart count if function exists
            if (typeof updateCartCount === 'function') {
              updateCartCount();
            }
            
            // Show success message
            if (typeof showToast === 'function') {
              showToast('Product added to cart successfully!', 'success');
            }

            // Reset button after 2 seconds
            setTimeout(() => {
              $btn
                .removeClass("btn-success")
                .addClass("btn-primary")
                .html(originalHtml)
                .prop('disabled', false);
            }, 2000);
            
          } else {
            // Handle specific error cases
            if (data.redirect) {
              if (typeof showToast === 'function') {
                showToast('Please login to add items to cart', 'error');
              } else {
                alert('Please login to add items to cart');
              }
              setTimeout(() => {
                window.location.href = 'login.html';
              }, 1500);
            } else {
              const errorMsg = data.message || "Error adding to cart";
              if (typeof showToast === 'function') {
                showToast(errorMsg, 'error');
              } else {
                alert(errorMsg);
              }
            }
            $btn.html(originalHtml).prop('disabled', false);
          }
        } catch (error) {
          console.error("Cart error:", error);
          const errorMsg = `Error adding to cart: ${error.message}`;
          if (typeof showToast === 'function') {
            showToast(errorMsg, 'error');
          } else {
            alert(errorMsg);
          }
          $btn.html(originalHtml).prop('disabled', false);
        }
      });

    // Add to wishlist functionality
    $(".add-to-wishlist-btn")
      .off("click")
      .on("click", async function (e) {
        e.preventDefault();
        const productId = $(this).data("product-id");
        const icon = $(this).find("i");

        try {
          const response = await fetch("api/wishlist.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "toggle",
              product_id: productId,
            }),
          });

          const data = await response.json();

          if (data.success) {
            if (data.action === "added") {
              icon.removeClass("far").addClass("fas text-danger");
            } else {
              icon.removeClass("fas text-danger").addClass("far");
            }
          }
        } catch (error) {
          console.error("Wishlist error:", error);
        }
      });
  }

  updateURLWithSearch(query) {
    const url = new URL(window.location);
    url.searchParams.set("search", query);
    window.history.pushState({}, "", url);
  }

  handleSearchSuggestions(query) {
    if (query.length < 3) {
      $("#searchSuggestions").hide();
      return;
    }

    const suggestions = [
      "casual blue shirt for office meetings",
      "party dress for evening events",
      "sports wear for gym workouts",
      "formal blazer for business meetings",
      "comfortable jeans for daily wear",
      "ethnic wear for festivals",
      "winter jacket for cold weather",
      "running shoes for jogging",
    ];

    const filteredSuggestions = suggestions
      .filter((s) => s.toLowerCase().includes(query.toLowerCase()))
      .slice(0, 5);

    if (filteredSuggestions.length > 0) {
      const suggestionHtml = filteredSuggestions
        .map(
          (suggestion) =>
            `<div class="suggestion-item" data-suggestion="${suggestion}">${suggestion}</div>`
        )
        .join("");

      $("#searchSuggestions").html(suggestionHtml).show();

      // Handle suggestion clicks
      $(".suggestion-item")
        .off("click")
        .on("click", function () {
          const suggestion = $(this).data("suggestion");
          $("#intelligentSearchInput").val(suggestion);
          $("#searchSuggestions").hide();
          // Auto-perform search
          setTimeout(() => ragSearch.performIntelligentSearch(), 100);
        });
    } else {
      $("#searchSuggestions").hide();
    }
  }

  saveToSearchHistory(query, results) {
    this.searchHistory.unshift({
      query,
      timestamp: new Date().toISOString(),
      results_count: results.total || 0,
      source: results.source,
    });

    // Keep only last 10 searches
    this.searchHistory = this.searchHistory.slice(0, 10);

    // Save to localStorage
    localStorage.setItem("search_history", JSON.stringify(this.searchHistory));
  }
}

// Initialize RAG Search when document is ready
let ragSearch;
$(document).ready(function () {
  console.log("🔄 Document ready, initializing StyleMeRAGSearch...");

  try {
    ragSearch = new StyleMeRAGSearch();
    console.log("🚀 StyleMeRAGSearch initialized successfully");

    // Force a test to see if our code is running
    console.log("🧪 Testing search functionality...");

    // Override any existing search handlers
    setTimeout(() => {
      console.log("🔍 Setting up search override...");

      // Remove any existing handlers and add ours
      $("#intelligentSearchInput")
        .off("input keyup")
        .on("input", function () {
          const query = $(this).val().trim();
          console.log("🎯 Search input detected:", query);
          if (query.length > 2) {
            console.log("🚀 Triggering RAG search...");
            ragSearch.performIntelligentSearch(query);
          } else if (query.length === 0) {
            // Clear results when search is empty
            $("#productList").empty();
            console.log("🧹 Search cleared");
          }
        });

      // Also ensure the search button works
      $("#intelligentSearchBtn")
        .off("click")
        .on("click", function (e) {
          e.preventDefault();
          const query = $("#intelligentSearchInput").val().trim();
          if (query.length > 0) {
            console.log("🔘 Search button clicked, query:", query);
            ragSearch.performIntelligentSearch(query);
          }
        });

      console.log("✅ Search handlers configured successfully");
    }, 1000);
  } catch (error) {
    console.error("❌ Failed to initialize StyleMeRAGSearch:", error);
  }

  // Handle adjust preferences button in no results
  $(document).on("click", "#adjustPreferencesBtn", function () {
    $("#preferencesPanel").slideDown(300);
    $("html, body").animate(
      {
        scrollTop: $("#preferencesPanel").offset().top - 100,
      },
      500
    );
  });
});
