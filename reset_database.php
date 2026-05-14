<?php
/**
 * Complete Database Reset & Import Script
 * Drops all tables and imports from phongtro_db.sql with Vietnamese data prefixes
 */

require_once 'config/database.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   🔧 Complete Database Reset & Import\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Step 1: Drop all existing tables
    echo "⏳ Step 1: Dropping existing tables...\n";
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            if ($conn->query("DROP TABLE IF EXISTS `$table`")) {
                echo "  ✓ Dropped table: $table\n";
            }
        }
    } else {
        echo "  ℹ️  No tables to drop\n";
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Step 2: Read and parse SQL file
    echo "\n⏳ Step 2: Reading SQL file...\n";
    $sqlFile = 'phongtro_db.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ Error: SQL file not found at $sqlFile\n";
        exit(1);
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Remove UTF-8 BOM if present
    if (substr($sqlContent, 0, 3) == "\xEF\xBB\xBF") {
        $sqlContent = substr($sqlContent, 3);
    }
    
    echo "  ✓ SQL file read successfully\n";
    
    // Step 3: Execute SQL statements
    echo "\n⏳ Step 3: Creating tables and importing data...\n";
    
    // Split by semicolon
    $statements = array_filter(
        array_map('trim', preg_split('/;[\s]*\n/', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && 
                   strpos(trim($stmt), '--') !== 0 && 
                   strpos(trim($stmt), '/*') !== 0;
        }
    );
    
    $tableCount = 0;
    $insertCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Detect statement type
        if (stripos($statement, 'CREATE TABLE') === 0) {
            // Extract table name for display
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                if ($conn->query($statement)) {
                    echo "  ✓ Created table: $tableName\n";
                    $tableCount++;
                } else {
                    echo "  ❌ Error creating table: " . $conn->error . "\n";
                    $errorCount++;
                }
            }
        } elseif (stripos($statement, 'INSERT INTO') === 0) {
            if ($conn->query($statement)) {
                $insertCount++;
            } else {
                echo "  ❌ Error in INSERT: " . $conn->error . "\n";
                $errorCount++;
            }
        }
    }
    
    // Step 4: Verify and display results
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "📊 Summary:\n";
    echo "   • Tables created: $tableCount\n";
    echo "   • Data rows inserted: $insertCount\n";
    echo "   • Errors: $errorCount\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    // List all tables
    echo "\n📋 Tables in database:\n";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    sort($tables);
    
    $criticalTables = ['users', 'motels', 'bookings', 'categories', 'districts', 'utilities'];
    $allFound = true;
    
    foreach ($tables as $table) {
        $isCritical = in_array($table, $criticalTables);
        $marker = $isCritical ? '⭐' : '  ';
        echo "$marker $table\n";
    }
    
    echo "\n🔍 Critical tables status:\n";
    foreach ($criticalTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "  ✅ $table\n";
            
            // Show table structure
            $desc = $conn->query("DESCRIBE $table");
            $cols = [];
            while ($row = $desc->fetch_assoc()) {
                $cols[] = $row['Field'];
            }
            echo "     Columns: " . implode(', ', array_slice($cols, 0, 5)) . 
                 (count($cols) > 5 ? ", ..." : "") . "\n";
        } else {
            echo "  ❌ $table (MISSING)\n";
            $allFound = false;
        }
    }
    
    if ($errorCount === 0 && $allFound) {
        echo "\n✅ Database import completed successfully!\n";
        echo "📝 Note: Data includes Vietnamese characters with UTF-8 encoding\n";
    } else {
        echo "\n⚠️  Import completed with warnings. Please check errors above.\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
