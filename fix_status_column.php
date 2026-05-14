<?php
/**
 * Fix Script: Add missing 'status' column to users table
 * Run this to fix the "Unknown column 'status'" error
 */

require_once 'config/database.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   🔧 Fixing Missing 'status' Column\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Check current table structure
    echo "📋 Current users table structure:\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['Field']}: {$row['Type']}\n";
    }
    echo "\n";
    
    // Check if status column already exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    
    if ($result->num_rows === 0) {
        echo "⏳ Adding 'status' column to users table...\n";
        
        // Add the status column
        $conn->query("ALTER TABLE users ADD COLUMN status varchar(20) DEFAULT 'pending' AFTER role");
        
        echo "✅ Successfully added 'status' column\n\n";
        
    } else {
        echo "✅ 'status' column already exists in users table\n\n";
    }
    
    // Display updated structure
    echo "📋 Updated users table structure:\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['Field']}: {$row['Type']}\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "   ✅ Fix completed! Status column is ready.\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
