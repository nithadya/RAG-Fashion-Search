-- SQL script to upgrade database for LangChain RAG implementation
-- Run this script to prepare the database for the new RAG service

-- Create user_search_history table for tracking user searches
CREATE TABLE IF NOT EXISTS `user_search_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `search_query` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Optional: Remove the description_embedding column if it exists (no longer needed with FAISS)
-- Uncomment the following line if you had embeddings stored in the products table
-- ALTER TABLE `products` DROP COLUMN IF EXISTS `description_embedding`;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_user_search_history_user_id` ON `user_search_history` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_user_search_history_created_at` ON `user_search_history` (`created_at`);

-- Add any additional columns that might be useful for the RAG system
ALTER TABLE `search_logs` ADD COLUMN IF NOT EXISTS `enhanced_query` TEXT DEFAULT NULL;
ALTER TABLE `search_logs` ADD COLUMN IF NOT EXISTS `processing_time` DECIMAL(5,3) DEFAULT NULL;
