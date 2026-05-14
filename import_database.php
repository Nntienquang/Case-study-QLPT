<?php
/**
 * Simple Database Import Script
 * Imports phongtro_db.sql directly
 */

require_once 'config/database.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   📥 Database Import from SQL File\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Read SQL file
    $sqlFile = 'phongtro_db.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ SQL file not found: $sqlFile\n";
        exit(1);
    }
    
    echo "📄 Reading: $sqlFile\n";
    $sqlContent = file_get_contents($sqlFile);
    
    // Remove BOM
    if (substr($sqlContent, 0, 3) == "\xEF\xBB\xBF") {
        $sqlContent = substr($sqlContent, 3);
    }
    
    // Method: Execute using mysqli multi_query
    echo "⏳ Executing SQL statements...\n\n";
    
    // Disable foreign key checks for import
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // Execute all queries
    if ($conn->multi_query($sqlContent)) {
        $tableCount = 0;
        $statementCount = 0;
        
        do {
            // Count CREATE TABLE statements
            $result = $conn->store_result();
            if ($result) {
                $result->free();
            }
            $statementCount++;
            
            // Check if there are more results
            if (!$conn->more_results()) {
                break;
            }
        } while ($conn->next_result());
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        echo "✅ SQL file executed successfully\n";
        echo "   Statements processed: $statementCount\n\n";
        
        // List all tables
        echo "📋 Current tables:\n";
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
            echo "  ✓ " . $row[0] . "\n";
        }
        
        // Check critical tables
        echo "\n🔍 Critical tables:\n";
        $critical = ['users', 'motels', 'bookings', 'categories', 'districts', 'utilities'];
        $allFound = true;
        foreach ($critical as $table) {
            if (in_array($table, $tables)) {
                // Show columns
                $desc = $conn->query("DESCRIBE $table");
                $cols = [];
                $colCount = 0;
                while ($col = $desc->fetch_assoc()) {
                    $cols[] = $col['Field'];
                    $colCount++;
                }
                echo "  ✅ $table ($colCount columns)\n";
                
                // Show first few columns
                $displayed = array_slice($cols, 0, 3);
                echo "     • " . implode(", ", $displayed);
                if ($colCount > 3) echo ", ...";
                echo "\n";
            } else {
                echo "  ❌ $table\n";
                $allFound = false;
            }
        }
        
        echo "\n═══════════════════════════════════════════════════════════\n";
        if ($allFound && count($tables) >= 6) {
            echo "✅ Database imported successfully!\n";
            echo "   All critical tables are present and ready to use.\n";
        } else {
            echo "⚠️  Import completed but some tables may be missing.\n";
        }
        echo "═══════════════════════════════════════════════════════════\n\n";
        
    } else {
        echo "❌ Error executing SQL: " . $conn->error . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
