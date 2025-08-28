-- Update existing feedback table to support enhanced feedback system
-- This script will modify the current feedback table structure

-- First, let's backup the existing feedback data
CREATE TABLE IF NOT EXISTS feedback_backup AS SELECT * FROM feedback;

-- Add new columns to the existing feedback table
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS name VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS subject VARCHAR(500) NOT NULL DEFAULT '';

-- Update the type enum to include our new types
ALTER TABLE feedback MODIFY COLUMN type ENUM('Search','Chatbot','General','contact','feedback','suggestion','complaint') DEFAULT 'General';

-- Add new columns for enhanced functionality
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS status ENUM('new', 'read', 'in_progress', 'resolved', 'closed') DEFAULT 'new';
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS admin_reply TEXT NULL;
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS admin_user_id INT NULL;
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE feedback ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL;

-- Add foreign key constraint for admin_user_id
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_admin_user 
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_feedback_email ON feedback(email);
CREATE INDEX IF NOT EXISTS idx_feedback_type ON feedback(type);
CREATE INDEX IF NOT EXISTS idx_feedback_status ON feedback(status);
CREATE INDEX IF NOT EXISTS idx_feedback_created_at ON feedback(created_at);

-- Update existing records to have default values for new required fields
UPDATE feedback 
SET 
    name = 'Anonymous User',
    email = 'noreply@styleme.com',
    subject = 'Legacy Feedback'
WHERE name = '' OR name IS NULL;

-- Create a view for recent feedback (useful for admin dashboard)
CREATE OR REPLACE VIEW recent_feedback AS
SELECT 
    f.id,
    f.name,
    f.email,
    f.subject,
    LEFT(f.message, 100) as message_preview,
    f.type,
    f.status,
    f.rating,
    f.created_at,
    f.updated_at,
    u.name as user_name,
    a.name as admin_name,
    CASE 
        WHEN f.admin_reply IS NOT NULL THEN 'Replied'
        WHEN f.status = 'resolved' THEN 'Resolved'
        WHEN f.status = 'in_progress' THEN 'In Progress'
        WHEN f.status = 'read' THEN 'Read'
        ELSE 'New'
    END as display_status
FROM feedback f
LEFT JOIN users u ON f.user_id = u.id
LEFT JOIN users a ON f.admin_user_id = a.id
ORDER BY f.created_at DESC;

-- Create a procedure to update feedback status
DELIMITER //
CREATE OR REPLACE PROCEDURE UpdateFeedbackStatus(
    IN feedback_id INT,
    IN new_status ENUM('new', 'read', 'in_progress', 'resolved', 'closed'),
    IN admin_id INT,
    IN reply_message TEXT
)
BEGIN
    UPDATE feedback 
    SET 
        status = new_status,
        admin_user_id = admin_id,
        admin_reply = CASE WHEN reply_message IS NOT NULL THEN reply_message ELSE admin_reply END,
        resolved_at = CASE WHEN new_status = 'resolved' THEN NOW() ELSE resolved_at END,
        updated_at = NOW()
    WHERE id = feedback_id;
    
    SELECT ROW_COUNT() as affected_rows;
END //
DELIMITER ;

-- Insert some sample data to test the new structure
INSERT INTO feedback (user_id, name, email, subject, message, type, status) 
VALUES 
(1, 'John Doe', 'john@example.com', 'Great Service!', 'I love shopping on StyleMe. The products are amazing!', 'feedback', 'new'),
(1, 'Jane Smith', 'jane@example.com', 'Suggestion for Improvement', 'Could you add more color options for the shirts?', 'suggestion', 'new'),
(NULL, 'Anonymous User', 'contact@example.com', 'Product Inquiry', 'Do you have this shirt in size XL?', 'contact', 'new')
ON DUPLICATE KEY UPDATE id=id;

-- Show the updated table structure
DESCRIBE feedback;

-- Show current feedback statistics
SELECT 
    COUNT(*) as total_feedback,
    COUNT(CASE WHEN type IN ('contact', 'feedback', 'suggestion', 'complaint') THEN 1 END) as new_system_feedback,
    COUNT(CASE WHEN type IN ('Search', 'Chatbot', 'General') THEN 1 END) as legacy_feedback,
    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_items,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_items,
    COUNT(CASE WHEN admin_reply IS NOT NULL THEN 1 END) as replied_items
FROM feedback;

-- Show recent feedback
SELECT * FROM recent_feedback LIMIT 10;
