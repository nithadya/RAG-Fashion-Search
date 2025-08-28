<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "Connecting to database..." . PHP_EOL;

try {
    // Test connection
    $testQuery = $db->fetchOne('SELECT COUNT(*) as count FROM feedback');
    echo "Current feedback records: " . $testQuery['count'] . PHP_EOL;
    
    // Add new columns one by one
    echo "Adding name column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT ""');
        echo "Name column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Name column already exists" . PHP_EOL;
        } else {
            echo "Error adding name column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Adding email column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT ""');
        echo "Email column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Email column already exists" . PHP_EOL;
        } else {
            echo "Error adding email column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Adding subject column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN subject VARCHAR(500) NOT NULL DEFAULT ""');
        echo "Subject column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Subject column already exists" . PHP_EOL;
        } else {
            echo "Error adding subject column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Updating type enum..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback MODIFY COLUMN type ENUM("Search","Chatbot","General","contact","feedback","suggestion","complaint") DEFAULT "General"');
        echo "Type enum updated successfully" . PHP_EOL;
    } catch (Exception $e) {
        echo "Error updating type enum: " . $e->getMessage() . PHP_EOL;
    }
    
    echo "Adding status column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN status ENUM("new", "read", "in_progress", "resolved", "closed") DEFAULT "new"');
        echo "Status column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Status column already exists" . PHP_EOL;
        } else {
            echo "Error adding status column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Adding admin_reply column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN admin_reply TEXT NULL');
        echo "Admin_reply column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Admin_reply column already exists" . PHP_EOL;
        } else {
            echo "Error adding admin_reply column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Adding admin_user_id column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN admin_user_id INT NULL');
        echo "Admin_user_id column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Admin_user_id column already exists" . PHP_EOL;
        } else {
            echo "Error adding admin_user_id column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Adding updated_at column..." . PHP_EOL;
    try {
        $db->query('ALTER TABLE feedback ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        echo "Updated_at column added successfully" . PHP_EOL;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Updated_at column already exists" . PHP_EOL;
        } else {
            echo "Error adding updated_at column: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo "Database update completed!" . PHP_EOL;
    
    // Show updated structure
    echo PHP_EOL . "Updated table structure:" . PHP_EOL;
    $result = $db->query('DESCRIBE feedback');
    foreach ($result as $row) {
        echo $row['Field'] . " - " . $row['Type'] . PHP_EOL;
    }
    
    // Insert test data
    echo PHP_EOL . "Inserting test data..." . PHP_EOL;
    try {
        $db->query('INSERT INTO feedback (name, email, subject, message, type, status) VALUES ("Test User", "test@example.com", "Test Feedback", "This is a test feedback message", "feedback", "new")');
        echo "Test data inserted successfully" . PHP_EOL;
    } catch (Exception $e) {
        echo "Error inserting test data: " . $e->getMessage() . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . PHP_EOL;
}
?>
