-- Create missing search_preferences_log table for RAG system
USE ecommerce_sl;

CREATE TABLE IF NOT EXISTS search_preferences_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    results_count INT DEFAULT 0,
    processing_time DECIMAL(10,3) DEFAULT 0.000,
    search_result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    preferences JSON,
    provider_used VARCHAR(20) DEFAULT 'GROQ',
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Also create search_history table if it doesn't exist
CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    results_count INT DEFAULT 0,
    processing_time DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    provider_used VARCHAR(20) DEFAULT 'GROQ',
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

SELECT 'Tables created successfully!' as status;
