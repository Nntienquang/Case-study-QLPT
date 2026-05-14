<?php
/**
 * Fix Script: Import complete SQL file to database
 * This will recreate all necessary tables
 */

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   🔧 Importing Complete SQL Database\n";
echo "═══════════════════════════════════════════════════════════\n\n";

require_once 'config/database.php';

try {
    // Read SQL file
    $sqlFile = 'phongtro_db.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ Error: SQL file not found at $sqlFile\n";
        exit(1);
    }
    
    echo "📄 Reading SQL file: $sqlFile\n";
    $sqlContent = file_get_contents($sqlFile);
    
    // Split and execute each statement
    $statements = [];
    $currentStatement = '';
    
    // Remove BOM if present
    if (substr($sqlContent, 0, 3) == "\xEF\xBB\xBF") {
        $sqlContent = substr($sqlContent, 3);
    }
    
    // Split by semicolon but be careful with quoted strings
    $lines = explode("\n", $sqlContent);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (trim($line) === '' || strpos(trim($line), '--') === 0 || strpos(trim($line), '/*') === 0) {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        if (substr(trim($line), -1) === ';') {
            $statements[] = rtrim($currentStatement, ';');
            $currentStatement = '';
        }
    }
    
    $tableCount = 0;
    $totalStatements = count($statements);
    
    echo "⏳ Processing $totalStatements SQL statements...\n\n";
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        
        if (empty($statement)) {
            continue;
        }
        
        // Only execute CREATE TABLE statements
        if (stripos($statement, 'CREATE TABLE') === false && 
            stripos($statement, 'INSERT INTO') === false) {
            continue;
        }
        
        if (stripos($statement, 'CREATE TABLE') === 0) {
            // Extract table name
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                // Check if table exists
                $result = $conn->query("SHOW TABLES LIKE '$tableName'");
                if ($result && $result->num_rows > 0) {
                    echo "⏭️  Skipping existing table: $tableName\n";
                    continue;
                }
                
                if ($conn->query($statement)) {
                    echo "✅ Created table: $tableName\n";
                    $tableCount++;
                } else {
                    echo "❌ Error creating table $tableName: " . $conn->error . "\n";
                }
            }
        } elseif (stripos($statement, 'INSERT INTO') === 0) {
            // Only insert if table exists
            if (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                $result = $conn->query("SHOW TABLES LIKE '$tableName'");
                if ($result && $result->num_rows > 0) {
                    if ($conn->query($statement)) {
                        // Silent success for inserts
                    }
                }
            }
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "📊 Summary:\n";
    echo "   • Tables created: $tableCount\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    // List all tables
    echo "\n📋 Tables in database:\n";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    sort($tables);
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
    // Check for critical tables
    echo "\n🔍 Checking critical tables:\n";
    $criticalTables = ['users', 'motels', 'bookings', 'categories', 'districts'];
    foreach ($criticalTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "  ✅ $table\n";
        } else {
            echo "  ❌ $table (MISSING)\n";
        }
    }
    
    echo "\n✅ Database import completed!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
