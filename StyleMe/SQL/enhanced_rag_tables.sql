-- Enhanced RAG Integration - User Preferences Tables

-- User preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    style_preferences JSON DEFAULT NULL COMMENT 'Array of preferred styles (casual, formal, etc.)',
    color_preferences JSON DEFAULT NULL COMMENT 'Array of preferred colors',
    budget_min INT DEFAULT 0 COMMENT 'Minimum budget in Rs.',
    budget_max INT DEFAULT 50000 COMMENT 'Maximum budget in Rs.',
    preferred_brands JSON DEFAULT NULL COMMENT 'Array of preferred brand names',
    occasion VARCHAR(50) DEFAULT 'casual' COMMENT 'Default occasion type',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id)
);

-- Search preferences log for machine learning
CREATE TABLE IF NOT EXISTS search_preferences_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    preferences JSON DEFAULT NULL,
    recommended_products JSON DEFAULT NULL COMMENT 'Array of product IDs recommended',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_search (user_id, created_at),
    INDEX idx_query_date (created_at)
);

-- Preference update log for analytics
CREATE TABLE IF NOT EXISTS preference_update_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preferences_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_updates (user_id, created_at)
);

-- Enhanced search logs with RAG metadata
CREATE TABLE IF NOT EXISTS enhanced_search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    query TEXT NOT NULL,
    enhanced_query TEXT DEFAULT NULL,
    preferences_applied JSON DEFAULT NULL,
    results_count INT DEFAULT 0,
    high_match_count INT DEFAULT 0,
    medium_match_count INT DEFAULT 0,
    processing_time DECIMAL(5,3) DEFAULT NULL,
    rag_processing_time DECIMAL(5,3) DEFAULT NULL,
    provider_used VARCHAR(20) DEFAULT NULL,
    service_version VARCHAR(50) DEFAULT NULL,
    fallback_mode BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_search (user_id, created_at),
    INDEX idx_query_performance (processing_time, created_at),
    INDEX idx_provider (provider_used, created_at)
);

-- Product interaction tracking for preference learning
CREATE TABLE IF NOT EXISTS product_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    interaction_type ENUM('view', 'cart', 'purchase', 'wishlist', 'search_click') NOT NULL,
    search_query TEXT DEFAULT NULL COMMENT 'Query that led to this interaction',
    matching_percentage INT DEFAULT NULL COMMENT 'Matching percentage when clicked',
    session_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user_interactions (user_id, interaction_type, created_at),
    INDEX idx_product_interactions (product_id, interaction_type, created_at),
    INDEX idx_search_interactions (search_query(100), created_at)
);

-- Style learning table for ML recommendations
CREATE TABLE IF NOT EXISTS style_learning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    style_vector JSON NOT NULL COMMENT 'User style preferences as vector',
    last_interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    interaction_count INT DEFAULT 1,
    preference_score DECIMAL(3,2) DEFAULT 0.5 COMMENT 'Confidence score 0-1',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_style (user_id)
);

-- Insert sample user preferences for testing (optional)
INSERT IGNORE INTO user_preferences (user_id, style_preferences, color_preferences, budget_min, budget_max, occasion) 
VALUES 
(1, '["casual", "modern"]', '["blue", "black", "white"]', 1000, 15000, 'casual'),
(2, '["formal", "western"]', '["black", "grey", "navy"]', 3000, 25000, 'office');

-- Add indexes for better performance
CREATE INDEX idx_preferences_budget ON user_preferences(budget_min, budget_max);
CREATE INDEX idx_preferences_occasion ON user_preferences(occasion);

-- Update existing search_logs table if it exists
ALTER TABLE search_logs 
ADD COLUMN IF NOT EXISTS enhanced_query TEXT DEFAULT NULL AFTER query,
ADD COLUMN IF NOT EXISTS preferences_applied JSON DEFAULT NULL AFTER enhanced_query,
ADD COLUMN IF NOT EXISTS matching_metadata JSON DEFAULT NULL AFTER preferences_applied;

COMMIT;
