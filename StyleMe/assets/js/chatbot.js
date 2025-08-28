class StyleMeChatbot {
  constructor() {
    this.apiUrl = "api";
    this.ragServiceUrl = "http://localhost:5000";
    this.isOpen = false;
    this.messages = [];
    this.isTyping = false;
    this.init();
  }

  init() {
    this.createChatbotHTML();
    this.setupEventListeners();
    this.showWelcomeMessage();
  }

  createChatbotHTML() {
    const chatbotHTML = `
            <div class="chatbot-widget">
                <!-- Welcome tooltip -->
                <div class="chatbot-welcome" id="chatbotWelcome">
                    👋 Need help finding clothes?
                </div>
                
                <!-- Toggle button -->
                <button class="chatbot-toggle" id="chatbotToggle">
                    <i class="fas fa-comments"></i>
                </button>
                
                <!-- Main chatbot panel -->
                <div class="chatbot-panel" id="chatbotPanel">
                    <div class="chatbot-header">
                        <div class="d-flex align-items-center">
                            <div class="chatbot-avatar me-2">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h5>StyleMe Assistant</h5>
                        </div>
                        <button class="chatbot-close" id="chatbotClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="chatbot-body">
                        <div class="chatbot-messages" id="chatbotMessages">
                            <!-- Messages will be added here -->
                        </div>
                        
                        <div class="chatbot-input-area">
                            <div class="chatbot-input-group">
                                <input type="text" class="chatbot-input" id="chatbotInput" 
                                       placeholder="Ask me about fashion..." autocomplete="off">
                                <button class="chatbot-send" id="chatbotSend">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    $("body").append(chatbotHTML);
  }

  setupEventListeners() {
    // Toggle chatbot
    $("#chatbotToggle").click(() => {
      this.toggleChatbot();
    });

    // Close chatbot
    $("#chatbotClose").click(() => {
      this.closeChatbot();
    });

    // Send message on enter
    $("#chatbotInput").keypress((e) => {
      if (e.which === 13 && !this.isTyping) {
        this.sendMessage();
      }
    });

    // Send message on button click
    $("#chatbotSend").click(() => {
      if (!this.isTyping) {
        this.sendMessage();
      }
    });

    // Hide welcome message after some time
    setTimeout(() => {
      $("#chatbotWelcome").fadeOut();
    }, 5000);

    // Close chatbot when clicking outside
    $(document).click((e) => {
      if (this.isOpen && !$(e.target).closest(".chatbot-widget").length) {
        this.closeChatbot();
      }
    });
  }

  toggleChatbot() {
    if (this.isOpen) {
      this.closeChatbot();
    } else {
      this.openChatbot();
    }
  }

  openChatbot() {
    this.isOpen = true;
    $("#chatbotPanel").fadeIn(300);
    $("#chatbotToggle").addClass("active");
    $("#chatbotWelcome").hide();
    $("#chatbotInput").focus();

    // Add welcome message if no messages exist
    if (this.messages.length === 0) {
      this.addBotMessage(
        "👋 Hi! I'm your StyleMe fashion assistant. I can help you:\n\n" +
          "• Find clothes that match your style\n" +
          "• Get outfit recommendations\n" +
          "• Search for specific items\n" +
          "• Check prices and availability\n\n" +
          "What are you looking for today?"
      );
      this.showQuickSuggestions();
    }
  }

  closeChatbot() {
    this.isOpen = false;
    $("#chatbotPanel").fadeOut(300);
    $("#chatbotToggle").removeClass("active");
  }

  sendMessage() {
    const input = $("#chatbotInput");
    const message = input.val().trim();

    if (message === "") return;

    // Add user message
    this.addUserMessage(message);
    input.val("");

    // Process the message
    this.processMessage(message);
  }

  addUserMessage(message) {
    const messageHTML = `
            <div class="chatbot-message user">
                <div class="chatbot-message-content">
                    ${this.formatMessage(message)}
                </div>
            </div>
        `;

    $("#chatbotMessages").append(messageHTML);
    this.scrollToBottom();
    this.messages.push({ type: "user", content: message });
  }

  addBotMessage(message, showSuggestions = false) {
    const messageHTML = `
            <div class="chatbot-message bot">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    ${this.formatMessage(message)}
                </div>
            </div>
        `;

    $("#chatbotMessages").append(messageHTML);
    this.scrollToBottom();
    this.messages.push({ type: "bot", content: message });

    if (showSuggestions) {
      this.showQuickSuggestions();
    }
  }

  showTyping() {
    this.isTyping = true;
    $("#chatbotSend").prop("disabled", true);

    const typingHTML = `
            <div class="chatbot-message bot" id="chatbotTyping">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    <div class="chatbot-typing">
                        <div class="chatbot-typing-dot"></div>
                        <div class="chatbot-typing-dot"></div>
                        <div class="chatbot-typing-dot"></div>
                    </div>
                </div>
            </div>
        `;

    $("#chatbotMessages").append(typingHTML);
    this.scrollToBottom();
  }

  hideTyping() {
    this.isTyping = false;
    $("#chatbotSend").prop("disabled", false);
    $("#chatbotTyping").remove();
  }

  async processMessage(message) {
    this.showTyping();

    try {
      // Check if it's a product search query
      if (this.isProductSearchQuery(message)) {
        await this.handleProductSearch(message);
      } else {
        // Handle general questions
        await this.handleGeneralQuery(message);
      }
    } catch (error) {
      console.error("Chatbot error:", error);
      this.hideTyping();
      this.addBotMessage(
        "Sorry, I'm having trouble processing your request right now. Please try again in a moment. 😔"
      );
    }
  }

  isProductSearchQuery(message) {
    const productKeywords = [
      "shirt",
      "dress",
      "pants",
      "jeans",
      "shoes",
      "jacket",
      "skirt",
      "saree",
      "kurta",
      "find",
      "search",
      "show",
      "looking for",
      "need",
      "want to buy",
      "recommend",
      "casual",
      "formal",
      "party",
      "wedding",
      "office",
      "summer",
      "winter",
      "red",
      "blue",
      "black",
      "white",
      "green",
      "pink",
      "yellow",
      "purple",
      "cotton",
      "silk",
      "denim",
      "leather",
      "wool",
    ];

    return productKeywords.some((keyword) =>
      message.toLowerCase().includes(keyword)
    );
  }

  async handleProductSearch(message) {
    try {
      // Call the RAG search API
      const response = await fetch(`${this.apiUrl}/rag_search.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          query: message,
          limit: 3, // Limit results for chat
        }),
      });

      const data = await response.json();
      this.hideTyping();

      if (data.success && data.products && data.products.length > 0) {
        this.addBotMessage(
          `Great! I found ${data.products.length} items that match what you're looking for:`
        );

        // Show products
        data.products.forEach((product) => {
          this.addProductCard(product);
        });

        this.addBotMessage(
          "Would you like me to find something more specific? 🛍️",
          true
        );
      } else {
        this.addBotMessage(
          "I couldn't find exact matches for that, but let me suggest some alternatives:\n\n" +
            "• Try browsing our categories\n" +
            "• Check our latest arrivals\n" +
            "• Visit our products page\n\n" +
            "What specific type of clothing are you interested in?"
        );
        this.showQuickSuggestions();
      }
    } catch (error) {
      throw error;
    }
  }

  async handleGeneralQuery(message) {
    // Simulate AI response based on keywords
    let response = "";

    if (
      message.toLowerCase().includes("hello") ||
      message.toLowerCase().includes("hi")
    ) {
      response =
        "Hello! 👋 Welcome to StyleMe! I'm here to help you find the perfect outfit. What can I help you with today?";
    } else if (
      message.toLowerCase().includes("price") ||
      message.toLowerCase().includes("cost")
    ) {
      response =
        "Our prices range from affordable everyday wear to premium fashion pieces. You can filter by price range on our products page. What's your budget?";
    } else if (
      message.toLowerCase().includes("delivery") ||
      message.toLowerCase().includes("shipping")
    ) {
      response =
        "We offer fast delivery across Sri Lanka! 🚚\n\n• Colombo: 1-2 days\n• Other cities: 2-3 days\n• Free shipping on orders over Rs. 3,000\n\nWould you like to check delivery for a specific area?";
    } else if (
      message.toLowerCase().includes("size") ||
      message.toLowerCase().includes("fit")
    ) {
      response =
        "We have a comprehensive size guide! 📏\n\n• XS to XXL available\n• Size chart on each product\n• Easy returns if size doesn't fit\n\nNeed help with sizing for a specific item?";
    } else if (
      message.toLowerCase().includes("return") ||
      message.toLowerCase().includes("exchange")
    ) {
      response =
        "Our return policy is customer-friendly! ↩️\n\n• 7-day return window\n• Easy online returns\n• Free exchanges for different sizes\n• Full refund if not satisfied\n\nNeed help with a return?";
    } else {
      response =
        "I'm still learning! 🤖 For detailed questions, you can:\n\n• Contact our customer service\n• Check our FAQ section\n• Ask me about specific products\n\nIs there anything specific you're looking for today?";
    }

    // Add delay to simulate thinking
    await new Promise((resolve) =>
      setTimeout(resolve, 1000 + Math.random() * 2000)
    );

    this.hideTyping();
    this.addBotMessage(response, true);
  }

  addProductCard(product) {
    const imageUrl = product.image1
      ? `assets/uploads/${product.image1}`
      : "assets/images/placeholder-product.jpg";
    const price =
      product.discount_price && parseFloat(product.discount_price) > 0
        ? `Rs. ${parseFloat(product.discount_price).toLocaleString()}`
        : `Rs. ${parseFloat(product.price).toLocaleString()}`;

    const productHTML = `
            <div class="chatbot-message bot">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    <div class="chatbot-product-card" onclick="window.open('product-detail.html?id=${
                      product.id
                    }', '_blank')">
                        <img src="${imageUrl}" alt="${
      product.name
    }" class="chatbot-product-image" 
                             onerror="this.src='assets/images/placeholder-product.jpg'">
                        <div class="chatbot-product-info">
                            <h6>${product.name}</h6>
                            <p>${product.category_name || "Fashion"} ${
      product.brand ? "• " + product.brand : ""
    }</p>
                            <div class="chatbot-product-price">${price}</div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        `;

    $("#chatbotMessages").append(productHTML);
    this.scrollToBottom();
  }

  showQuickSuggestions() {
    const suggestions = [
      "Show me casual shirts",
      "Find formal dresses",
      "Latest arrivals",
      "Sale items",
      "Size guide",
      "Delivery info",
    ];

    const suggestionsHTML = `
            <div class="chatbot-message bot">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    Quick suggestions:
                    <div class="chatbot-suggestions">
                        ${suggestions
                          .map(
                            (suggestion) =>
                              `<div class="chatbot-suggestion" onclick="chatbot.handleSuggestionClick('${suggestion}')">${suggestion}</div>`
                          )
                          .join("")}
                    </div>
                </div>
            </div>
        `;

    $("#chatbotMessages").append(suggestionsHTML);
    this.scrollToBottom();
  }

  handleSuggestionClick(suggestion) {
    $("#chatbotInput").val(suggestion);
    this.sendMessage();
  }

  formatMessage(message) {
    // Convert newlines to <br> and format basic text
    return message
      .replace(/\n/g, "<br>")
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>");
  }

  scrollToBottom() {
    const messages = $("#chatbotMessages");
    messages.scrollTop(messages[0].scrollHeight);
  }

  showWelcomeMessage() {
    // Show welcome tooltip after a delay
    setTimeout(() => {
      $("#chatbotWelcome").fadeIn(500);
    }, 2000);
  }
}

// Initialize chatbot when document is ready
let chatbot;
$(document).ready(function () {
  chatbot = new StyleMeChatbot();
  console.log("🤖 StyleMe Chatbot initialized");
});
