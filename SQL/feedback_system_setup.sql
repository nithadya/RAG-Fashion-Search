-- feedback_system_setup.sql
-- Complete feedback system database setup

-- Create feedback table if it doesn't exist
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('contact', 'feedback', 'suggestion', 'complaint') DEFAULT 'feedback',
    status ENUM('new', 'read', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    admin_reply TEXT NULL,
    admin_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Update existing feedback table if it already exists but missing columns
-- Add type column if it doesn't exist
SET @sql = CONCAT('ALTER TABLE feedback ADD COLUMN IF NOT EXISTS type ENUM(\'contact\', \'feedback\', \'suggestion\', \'complaint\') DEFAULT \'feedback\' AFTER message');
SET @check_column = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'type');

SET @sql = IF(@check_column = 0, 
              'ALTER TABLE feedback ADD COLUMN type ENUM(\'contact\', \'feedback\', \'suggestion\', \'complaint\') DEFAULT \'feedback\' AFTER message',
              'SELECT "Column type already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add admin_user_id column if it doesn't exist
SET @check_column = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'admin_user_id');

SET @sql = IF(@check_column = 0, 
              'ALTER TABLE feedback ADD COLUMN admin_user_id INT NULL AFTER admin_reply',
              'SELECT "Column admin_user_id already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add resolved_at column if it doesn't exist
SET @check_column = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'resolved_at');

SET @sql = IF(@check_column = 0, 
              'ALTER TABLE feedback ADD COLUMN resolved_at TIMESTAMP NULL AFTER updated_at',
              'SELECT "Column resolved_at already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include all values if needed
ALTER TABLE feedback MODIFY COLUMN status ENUM('new', 'read', 'in_progress', 'resolved', 'closed') DEFAULT 'new';

-- Create indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_user_id ON feedback(user_id);
CREATE INDEX IF NOT EXISTS idx_email ON feedback(email);
CREATE INDEX IF NOT EXISTS idx_type ON feedback(type);
CREATE INDEX IF NOT EXISTS idx_status ON feedback(status);
CREATE INDEX IF NOT EXISTS idx_created_at ON feedback(created_at);

-- Insert sample data for testing (only if table is empty)
INSERT INTO feedback (name, email, subject, message, type, status) 
SELECT 'John Doe', 'john@example.com', 'Test Feedback', 'This is a test feedback message for system validation.', 'feedback', 'new'
WHERE NOT EXISTS (SELECT 1 FROM feedback LIMIT 1);

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
    f.created_at,
    u.name as user_name,
    CASE 
        WHEN f.admin_reply IS NOT NULL THEN 'Replied'
        WHEN f.status = 'resolved' THEN 'Resolved'
        WHEN f.status = 'in_progress' THEN 'In Progress'
        WHEN f.status = 'read' THEN 'Read'
        ELSE 'New'
    END as display_status
FROM feedback f
LEFT JOIN users u ON f.user_id = u.id
ORDER BY f.created_at DESC;

-- Create a procedure to update feedback status
DELIMITER //
CREATE OR REPLACE PROCEDURE UpdateFeedbackStatus(
    IN feedback_id INT,
    IN new_status ENUM('new', 'read', 'in_progress', 'resolved', 'closed'),
    IN admin_id INT
)
BEGIN
    UPDATE feedback 
    SET 
        status = new_status,
        admin_user_id = admin_id,
        resolved_at = CASE WHEN new_status = 'resolved' THEN NOW() ELSE resolved_at END,
        updated_at = NOW()
    WHERE id = feedback_id;
    
    -- Log the status change
    INSERT INTO feedback_log (feedback_id, old_status, new_status, admin_user_id, created_at)
    SELECT feedback_id, 'unknown', new_status, admin_id, NOW()
    WHERE NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'feedback_log');
END //
DELIMITER ;

-- Optional: Create feedback_log table for audit trail
CREATE TABLE IF NOT EXISTS feedback_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    admin_user_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_feedback_id (feedback_id),
    INDEX idx_created_at (created_at)
);

-- Show current feedback table structure
DESCRIBE feedback;

-- Show feedback statistics
SELECT 
    COUNT(*) as total_feedback,
    COUNT(CASE WHEN type = 'contact' THEN 1 END) as contact_forms,
    COUNT(CASE WHEN type = 'feedback' THEN 1 END) as feedback_forms,
    COUNT(CASE WHEN type = 'suggestion' THEN 1 END) as suggestions,
    COUNT(CASE WHEN type = 'complaint' THEN 1 END) as complaints,
    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_items,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_items
FROM feedback;
