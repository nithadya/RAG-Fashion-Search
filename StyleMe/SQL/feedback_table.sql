-- Create feedback table for contact form submissions and user feedback
-- This script adds the feedback table if it doesn't exist

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User ID if logged in, NULL for anonymous',
  `name` varchar(255) NOT NULL COMMENT 'Name of person submitting feedback',
  `email` varchar(255) NOT NULL COMMENT 'Email of person submitting feedback',
  `subject` varchar(500) NOT NULL COMMENT 'Subject/title of feedback',
  `message` text NOT NULL COMMENT 'Feedback message content',
  `type` enum('contact','feedback','complaint','suggestion') NOT NULL DEFAULT 'contact' COMMENT 'Type of feedback',
  `status` enum('new','read','replied','resolved') NOT NULL DEFAULT 'new' COMMENT 'Status of feedback',
  `admin_notes` text DEFAULT NULL COMMENT 'Internal admin notes',
  `reply_message` text DEFAULT NULL COMMENT 'Admin reply message',
  `replied_at` timestamp NULL DEFAULT NULL COMMENT 'When admin replied',
  `replied_by` int(11) DEFAULT NULL COMMENT 'Admin who replied',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`replied_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User feedback and contact form submissions';

-- Insert some sample data for testing (optional)
-- You can remove this section if you don't want sample data
INSERT IGNORE INTO `feedback` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `type`, `status`, `created_at`) VALUES
(1, NULL, 'John Doe', 'john@example.com', 'Website Feedback', 'Great website! Love the RAG search feature.', 'feedback', 'new', NOW()),
(2, NULL, 'Jane Smith', 'jane@example.com', 'Product Question', 'Do you have more colors for the casual shirts?', 'contact', 'new', NOW()),
(3, NULL, 'Bob Wilson', 'bob@example.com', 'Suggestion', 'Could you add size filters to the search?', 'suggestion', 'new', NOW());

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_feedback_email` ON `feedback` (`email`);
CREATE INDEX IF NOT EXISTS `idx_feedback_type_status` ON `feedback` (`type`, `status`);
