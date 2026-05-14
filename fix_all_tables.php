<?php
/**
 * Fix Script: Import all tables from SQL file
 * Run this to create missing tables in the database
 */

require_once 'config/database.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   🔧 Importing Database Tables from SQL File\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Read SQL file
    $sqlFile = 'phongtro_db.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ Error: SQL file not found at $sqlFile\n";
        exit(1);
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split SQL statements
    $statements = preg_split('/;[\s]*$/m', $sqlContent);
    
    $tableCount = 0;
    $errorCount = 0;
    
    echo "⏳ Executing SQL statements...\n\n";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip comments and empty statements
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
            continue;
        }
        
        // Only execute CREATE TABLE statements
        if (stripos($statement, 'CREATE TABLE') === false) {
            continue;
        }
        
        // Extract table name from CREATE TABLE statement
        if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
            $tableName = $matches[1];
            
            // Check if table already exists
            $result = $conn->query("SHOW TABLES LIKE '$tableName'");
            
            if ($result && $result->num_rows > 0) {
                echo "✅ Table '$tableName' already exists\n";
            } else {
                if ($conn->query($statement)) {
                    echo "✅ Created table '$tableName'\n";
                    $tableCount++;
                } else {
                    echo "❌ Error creating table '$tableName': " . $conn->error . "\n";
                    $errorCount++;
                }
            }
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "📊 Summary:\n";
    echo "   • Tables created: $tableCount\n";
    echo "   • Errors: $errorCount\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    // List all tables
    echo "\n📋 Current tables in database:\n";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    sort($tables);
    foreach ($tables as $table) {
        echo "  • $table\n";
    }
    
    if ($errorCount === 0) {
        echo "\n✅ All database tables are ready!\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
