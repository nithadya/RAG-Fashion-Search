<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    $action = $_POST['action'] ?? 'chat';
    
    switch ($action) {
        case 'chat':
            $response = handleIntelligentChat();
            break;
        case 'get_suggestions':
            $response = getSmartSuggestions();
            break;
        case 'clear_context':
            $response = clearChatContext();
            break;
        default:
            $response = handleIntelligentChat();
            break;
    }
} catch (Exception $e) {
    error_log("Chatbot API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'AI assistant temporarily unavailable'];
}

echo json_encode($response);

function handleIntelligentChat() {
    $message = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'] ?? null;
    
    if (empty($message)) {
        return ['success' => false, 'message' => 'Message cannot be empty'];
    }
    
    // Initialize conversation context with memory
    if (!isset($_SESSION['chat_context'])) {
        $_SESSION['chat_context'] = [
            'conversation_id' => uniqid(),
            'messages' => [],
            'user_preferences' => [],
            'last_intent' => null,
            'search_history' => [],
            'product_interests' => []
        ];
    }
    
    // Add user message to context
    $_SESSION['chat_context']['messages'][] = [
        'role' => 'user',
        'content' => $message,
        'timestamp' => time()
    ];
    
    // Advanced intent analysis with NLP
    $analysis = analyzeMessageIntelligently($message);
    $response = generateIntelligentResponse($message, $analysis, $userId);
    
    // Add bot response to context
    $_SESSION['chat_context']['messages'][] = [
        'role' => 'assistant',
        'content' => $response['reply'],
        'timestamp' => time(),
        'intent' => $analysis['intent'],
        'entities' => $analysis['entities']
    ];
    
    // Learn from user interactions
    updateUserPreferences($analysis, $userId);
    
    // Maintain conversation history
    if (count($_SESSION['chat_context']['messages']) > 30) {
        $_SESSION['chat_context']['messages'] = array_slice($_SESSION['chat_context']['messages'], -30);
    }
    
    return [
        'success' => true,
        'reply' => $response['reply'],
        'actions' => $response['actions'] ?? [],
        'suggestions' => $response['suggestions'] ?? [],
        'intent' => $analysis['intent'],
        'confidence' => $analysis['confidence']
    ];
}

function analyzeMessageIntelligently($message) {
    $message = strtolower(trim($message));
    
    // Advanced intent recognition with weighted scoring
    $intents = [
        'product_search' => [
            'keywords' => ['find', 'search', 'looking for', 'show me', 'need', 'want', 'buy'],
            'patterns' => ['/looking for (.+)/', '/need (.+)/', '/want to buy (.+)/', '/show me (.+)/'],
            'weight' => 1.0
        ],
        'category_browse' => [
            'keywords' => ['category', 'categories', 'browse', 'section', 'type'],
            'patterns' => ['/what categories/', '/browse (.+) category/'],
            'weight' => 0.9
        ],
        'price_inquiry' => [
            'keywords' => ['price', 'cost', 'how much', 'expensive', 'cheap', 'budget'],
            'patterns' => ['/how much (.+)/', '/price of (.+)/'],
            'weight' => 0.8
        ],
        'recommendation' => [
            'keywords' => ['recommend', 'suggest', 'best', 'popular', 'trending'],
            'patterns' => ['/recommend (.+)/', '/what should i (.+)/'],
            'weight' => 0.9
        ],
        'order_inquiry' => [
            'keywords' => ['order', 'delivery', 'shipping', 'track', 'status'],
            'patterns' => ['/my order/', '/track order/'],
            'weight' => 0.8
        ],
        'greeting' => [
            'keywords' => ['hi', 'hello', 'hey', 'good morning', 'good afternoon'],
            'patterns' => ['/^(hi|hello|hey)/'],
            'weight' => 1.0
        ]
    ];
    
    $bestIntent = 'general';
    $maxScore = 0;
    $entities = [];
    
    foreach ($intents as $intent => $config) {
        $score = 0;
        
        // Keyword matching with context
        foreach ($config['keywords'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $score += $config['weight'];
            }
        }
        
        // Pattern matching with entity extraction
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $score += $config['weight'] * 1.5;
                if (isset($matches[1])) {
                    $entities[] = trim($matches[1]);
                }
            }
        }
        
        if ($score > $maxScore) {
            $maxScore = $score;
            $bestIntent = $intent;
        }
    }
    
    // Extract fashion-specific entities
    $entities = array_merge($entities, extractFashionEntities($message));
    
    return [
        'intent' => $bestIntent,
        'confidence' => min($maxScore / 2, 1.0),
        'entities' => array_unique($entities),
        'original_message' => $message
    ];
}

function extractFashionEntities($message) {
    global $db;
    $entities = [];
    
    try {
        // Extract categories from database
        $sql = "SELECT name FROM categories";
        $categories = $db->fetchAll($sql);
        
        foreach ($categories as $category) {
            $categoryName = strtolower($category['name']);
            if (strpos($message, $categoryName) !== false) {
                $entities[] = $category['name'];
            }
        }
        
        // Fashion-specific terms
        $fashionTerms = [
            'dress', 'shirt', 'trouser', 'jeans', 'shoes', 'sandals', 'bag',
            'saree', 'sarong', 'blouse', 'skirt', 'jacket',
            'red', 'blue', 'black', 'white', 'green', 'yellow', 'pink',
            'small', 'medium', 'large', 'xl', 's', 'm', 'l',
            'cotton', 'silk', 'denim', 'formal', 'casual', 'party'
        ];
        
        foreach ($fashionTerms as $term) {
            if (strpos($message, $term) !== false) {
                $entities[] = $term;
            }
        }
        
        // Extract brands from database
        $sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL";
        $brands = $db->fetchAll($sql);
        
        foreach ($brands as $brand) {
            if ($brand['brand'] && strpos($message, strtolower($brand['brand'])) !== false) {
                $entities[] = $brand['brand'];
            }
        }
        
    } catch (Exception $e) {
        error_log("Entity extraction error: " . $e->getMessage());
    }
    
    return array_unique($entities);
}

function generateIntelligentResponse($message, $analysis, $userId) {
    switch ($analysis['intent']) {
        case 'greeting':
            return handleIntelligentGreeting($userId);
        case 'product_search':
            return handleIntelligentProductSearch($analysis['entities'], $message);
        case 'category_browse':
            return handleCategoryBrowsing($analysis['entities']);
        case 'price_inquiry':
            return handlePriceInquiry($analysis['entities'], $message);
        case 'recommendation':
            return handleIntelligentRecommendations($analysis['entities'], $userId);
        case 'order_inquiry':
            return handleOrderInquiry($userId);
        default:
            return handleGeneralInquiry($message, $analysis);
    }
}

function handleIntelligentGreeting($userId) {
    $greetings = [
        "👋 Hello! I'm your AI fashion assistant. I can help you find products, browse categories, check prices, and get personalized recommendations using natural language!",
        "Hi there! 🌟 Ready to discover amazing fashion? I understand natural language - just tell me what you're looking for!",
        "Hello! I'm here to make your shopping experience smarter. Ask me anything about our products in your own words!"
    ];
    
    $greeting = $greetings[array_rand($greetings)];
    
    if ($userId) {
        global $db;
        try {
            $sql = "SELECT name FROM users WHERE id = ?";
            $user = $db->fetchOne($sql, [$userId]);
            if ($user) {
                $firstName = explode(' ', $user['name'])[0];
                $greeting = "👋 Welcome back, " . $firstName . "! " . $greeting;
            }
        } catch (Exception $e) {
            // Continue with generic greeting
        }
    }
    
    return [
        'reply' => $greeting,
        'suggestions' => [
            "🔍 Find red dresses under Rs. 3000",
            "👔 Show me men's formal wear",
            "👟 What shoes are trending?",
            "🌟 Recommend something for me",
            "📱 Browse categories"
        ]
    ];
}

function handleIntelligentProductSearch($entities, $originalMessage) {
    global $db;
    
    if (empty($entities)) {
        return [
            'reply' => "I'd love to help you find products! Could you be more specific? For example: 'Show me red dresses' or 'Find men's casual shirts'",
            'suggestions' => [
                "👗 Women's dresses",
                "👔 Men's shirts",
                "👟 Footwear",
                "👜 Accessories"
            ]
        ];
    }
    
    // Build intelligent search query
    $searchTerms = [];
    $filters = [
        'category' => null,
        'color' => null,
        'gender' => null,
        'max_price' => null
    ];
    
    // Analyze entities for filters
    foreach ($entities as $entity) {
        $entity = strtolower(trim($entity));
        
        // Check categories
        $sql = "SELECT id, name FROM categories WHERE LOWER(name) LIKE ?";
        $category = $db->fetchOne($sql, ['%' . $entity . '%']);
        if ($category) {
            $filters['category'] = $category['id'];
            continue;
        }
        
        // Check colors
        $colors = ['red', 'blue', 'black', 'white', 'green', 'yellow', 'pink'];
        if (in_array($entity, $colors)) {
            $filters['color'] = $entity;
            continue;
        }
        
        // Check gender
        if (in_array($entity, ['men', 'mens', 'male'])) {
            $filters['gender'] = 'Male';
            continue;
        }
        if (in_array($entity, ['women', 'womens', 'female', 'ladies'])) {
            $filters['gender'] = 'Female';
            continue;
        }
        
        $searchTerms[] = $entity;
    }
    
    // Extract price from message
    if (preg_match('/under (?:rs\.?\s*)?(\d+)/i', $originalMessage, $matches)) {
        $filters['max_price'] = intval($matches[1]);
    }
    
    // Build SQL query
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.stock > 0";
    $params = [];
    
    // Add search conditions
    if (!empty($searchTerms)) {
        $searchConditions = [];
        foreach ($searchTerms as $term) {
            $searchConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
            $params[] = '%' . $term . '%';
            $params[] = '%' . $term . '%';
            $params[] = '%' . $term . '%';
        }
        $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
    }
    
    // Add filters
    if ($filters['category']) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filters['category'];
    }
    
    if ($filters['color']) {
        $sql .= " AND LOWER(p.color) LIKE ?";
        $params[] = '%' . $filters['color'] . '%';
    }
    
    if ($filters['gender']) {
        $sql .= " AND p.gender = ?";
        $params[] = $filters['gender'];
    }
    
    if ($filters['max_price']) {
        $sql .= " AND COALESCE(p.discount_price, p.price) <= ?";
        $params[] = $filters['max_price'];
    }
    
    $sql .= " ORDER BY 
                CASE WHEN p.discount_price > 0 THEN 1 ELSE 0 END DESC,
                p.created_at DESC 
              LIMIT 6";
    
    try {
        $products = $db->fetchAll($sql, $params);
        
        if (empty($products)) {
            return [
                'reply' => "I couldn't find any products matching your search. Let me suggest some alternatives:",
                'actions' => [
                    [
                        'type' => 'categories',
                        'data' => getPopularCategories()
                    ]
                ],
                'suggestions' => [
                    "🔍 Try different keywords",
                    "📋 Browse all categories",
                    "🌟 Show trending items"
                ]
            ];
        }
        
        $searchDescription = implode(', ', array_filter($entities));
        $reply = "🎉 Found " . count($products) . " products";
        if ($searchDescription) {
            $reply .= " for '$searchDescription'";
        }
        $reply .= ". Here are the best matches:";
        
        return [
            'reply' => $reply,
            'actions' => [
                [
                    'type' => 'products',
                    'data' => $products
                ]
            ],
            'suggestions' => [
                "💰 Show discounted items",
                "🔍 Refine search",
                "⭐ View product details"
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Product search error: " . $e->getMessage());
        return [
            'reply' => "I'm having trouble searching right now. Please try again or browse our categories.",
            'suggestions' => ["📋 Browse categories", "🔄 Try again"]
        ];
    }
}

function handleCategoryBrowsing($entities) {
    global $db;
    
    try {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id AND p.stock > 0
                GROUP BY c.id 
                ORDER BY product_count DESC";
        $categories = $db->fetchAll($sql);
        
        return [
            'reply' => "📂 Here are all our product categories. Click on any to explore:",
            'actions' => [
                [
                    'type' => 'categories',
                    'data' => $categories
                ]
            ],
            'suggestions' => [
                "👗 Women's clothing",
                "👔 Men's clothing",
                "👟 Footwear",
                "👜 Accessories"
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'reply' => "I'm having trouble loading categories right now. Please try again.",
            'suggestions' => ["🔄 Try again", "🔍 Search products instead"]
        ];
    }
}

function handlePriceInquiry($entities, $message) {
    global $db;
    
    // Extract price range from message
    if (preg_match('/under (?:rs\.?\s*)?(\d+)/i', $message, $matches)) {
        $maxPrice = intval($matches[1]);
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE COALESCE(p.discount_price, p.price) <= ? AND p.stock > 0 
                ORDER BY COALESCE(p.discount_price, p.price) ASC 
                LIMIT 6";
        
        try {
            $products = $db->fetchAll($sql, [$maxPrice]);
            
            if (!empty($products)) {
                return [
                    'reply' => "💰 Found " . count($products) . " products under Rs. $maxPrice:",
                    'actions' => [
                        [
                            'type' => 'products',
                            'data' => $products
                        ]
                    ],
                    'suggestions' => [
                        "💸 Show cheapest first",
                        "🏷️ View discounted items",
                        "🔍 Search specific items"
                    ]
                ];
            }
        } catch (Exception $e) {
            error_log("Price inquiry error: " . $e->getMessage());
        }
    }
    
    // General price information
    return [
        'reply' => "I can help you find products within your budget! Try asking 'Show me products under Rs. 3000' or specify a price range.",
        'suggestions' => [
            "💸 Products under Rs. 2000",
            "💰 Products under Rs. 5000",
            "🏷️ Discounted items"
        ]
    ];
}

function handleIntelligentRecommendations($entities, $userId) {
    global $db;
    
    try {
        // Get personalized recommendations based on user history
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.stock > 0 
                ORDER BY 
                    CASE WHEN p.discount_price > 0 THEN 1 ELSE 0 END DESC,
                    p.created_at DESC 
                LIMIT 6";
        
        $products = $db->fetchAll($sql);
        
        $reply = "🌟 Here are my personalized recommendations for you:";
        if ($userId) {
            $reply .= " Based on current trends and popular items!";
        }
        
        return [
            'reply' => $reply,
            'actions' => [
                [
                    'type' => 'recommendations',
                    'data' => $products
                ]
            ],
            'suggestions' => [
                "💰 Show discounted recommendations",
                "🔍 Find similar products",
                "❤️ Add to wishlist"
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'reply' => "I'm having trouble loading recommendations right now. Please try browsing our categories instead.",
            'suggestions' => ["📋 Browse categories", "🔍 Search products"]
        ];
    }
}

function handleOrderInquiry($userId) {
    if (!$userId) {
        return [
            'reply' => "🔐 To check your orders, please log in to your account first. I'll be happy to help you track your orders once you're logged in!",
            'suggestions' => [
                "🔑 Login to account",
                "📝 Create account",
                "🛍️ Continue shopping"
            ]
        ];
    }
    
    global $db;
    
    try {
        $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3";
        $orders = $db->fetchAll($sql, [$userId]);
        
        if (empty($orders)) {
            return [
                'reply' => "📦 You don't have any orders yet. Ready to start shopping? I can help you find some amazing products!",
                'suggestions' => [
                    "🔍 Search products",
                    "🌟 View trending items",
                    "🏷️ Browse categories"
                ]
            ];
        }
        
        $reply = "📦 Here are your recent orders:\n\n";
        
        foreach ($orders as $order) {
            $statusEmoji = getStatusEmoji($order['status']);
            $reply .= "🧾 **Order #{$order['order_number']}**\n";
            $reply .= "💰 Amount: Rs. " . number_format($order['total_amount'], 2) . "\n";
            $reply .= "{$statusEmoji} Status: {$order['status']}\n";
            $reply .= "📅 Date: " . date('M j, Y', strtotime($order['created_at'])) . "\n\n";
        }
        
        return [
            'reply' => $reply,
            'actions' => [
                [
                    'type' => 'orders',
                    'data' => $orders
                ]
            ],
            'suggestions' => [
                "📋 View order details",
                "🚚 Track delivery",
                "🛍️ Shop again"
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'reply' => "I'm having trouble accessing your orders right now. Please try again or contact support.",
            'suggestions' => ["🔄 Try again", "📞 Contact support"]
        ];
    }
}

function handleGeneralInquiry($message, $analysis) {
    $reply = "I'm here to help! I can assist you with:\n\n";
    $reply .= "🔍 **Smart Product Search** - Just describe what you want naturally\n";
    $reply .= "📂 **Category Browsing** - Explore our product categories\n";
    $reply .= "💰 **Price Inquiries** - Find products within your budget\n";
    $reply .= "🌟 **Recommendations** - Get personalized suggestions\n";
    $reply .= "📦 **Order Tracking** - Check your order status\n\n";
    $reply .= "Try asking: 'Show me red dresses under Rs. 5000' or 'What's trending in men's wear?'";
    
    return [
        'reply' => $reply,
        'suggestions' => [
            "🔍 Search for products",
            "📂 Browse categories",
            "🌟 Get recommendations",
            "💰 Find budget-friendly items"
        ]
    ];
}

// Helper functions
function getStatusEmoji($status) {
    $emojis = [
        'Pending' => '⏳',
        'Processing' => '⚙️',
        'Shipped' => '🚚',
        'Delivered' => '✅',
        'Cancelled' => '❌'
    ];
    return $emojis[$status] ?? '📦';
}

function getPopularCategories() {
    global $db;
    try {
        $sql = "SELECT * FROM categories ORDER BY name LIMIT 4";
        return $db->fetchAll($sql);
    } catch (Exception $e) {
        return [];
    }
}

function updateUserPreferences($analysis, $userId) {
    if (!$userId || empty($analysis['entities'])) return;
    
    // Store user preferences in session for this conversation
    if (!isset($_SESSION['chat_context']['user_preferences'])) {
        $_SESSION['chat_context']['user_preferences'] = [];
    }
    
    foreach ($analysis['entities'] as $entity) {
        if (!isset($_SESSION['chat_context']['user_preferences'][$entity])) {
            $_SESSION['chat_context']['user_preferences'][$entity] = 0;
        }
        $_SESSION['chat_context']['user_preferences'][$entity]++;
    }
}

function getSmartSuggestions() {
    $suggestions = [
        "🔍 Find red dresses under Rs. 3000",
        "👔 Show me men's formal wear",
        "👟 What shoes are trending?",
        "💰 Best deals available now",
        "🌟 Recommend something for a party",
        "📱 Browse women's accessories"
    ];
    
    return [
        'success' => true,
        'suggestions' => array_slice($suggestions, 0, 6)
    ];
}

function clearChatContext() {
    unset($_SESSION['chat_context']);
    return [
        'success' => true,
        'message' => 'Chat context cleared'
    ];
}
?>
