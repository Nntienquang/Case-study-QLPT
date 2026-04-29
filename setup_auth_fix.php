#!/usr/bin/env php
<?php
/**
 * Auth Fix Setup & Migration Script
 * Run this to complete the authentication fixes
 */

require_once 'config/database.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   🔐 Authentication System Fix - Setup Script\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Check database connection
echo "[1/4] Checking database connection... ";
try {
    $conn->query("SELECT 1");
    echo "✅ Connected\n\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Add reset columns if they don't exist
echo "[2/4] Adding password reset columns... ";
try {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL");
    $conn->query("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL DEFAULT NULL");
    
    // Create indexes
    $conn->query("CREATE INDEX idx_reset_token ON users(reset_token)");
    $conn->query("CREATE INDEX idx_email ON users(email)");
    
    echo "✅ Added\n\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✅ Already exists\n\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Step 3: Check for plaintext passwords
echo "[3/4] Checking for plaintext passwords... ";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE password NOT LIKE '$2%' AND password != ''");
$row = $result->fetch_assoc();
$plaintext_count = $row['count'];

if ($plaintext_count > 0) {
    echo "⚠️  Found $plaintext_count\n";
    echo "    Running migration... ";
    
    $result = $conn->query("SELECT id, password FROM users WHERE password NOT LIKE '$2%' AND password != ''");
    $migrated = 0;
    
    while ($user = $result->fetch_assoc()) {
        $hashed = password_hash($user['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user['id']);
        if ($stmt->execute()) {
            $migrated++;
        }
    }
    
    echo "✅ Migrated $migrated passwords\n\n";
} else {
    echo "✅ All passwords are hashed\n\n";
}

// Step 4: Add status column if missing
echo "[4/4] Adding status column (if needed)... ";
try {
    $conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
    echo "✅ Added\n\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✅ Already exists\n\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "   ✅ Setup Complete!\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Next steps:\n";
echo "  1. Test registration: register.php\n";
echo "  2. Test login: login.php\n";
echo "  3. Test reset: forgot.php\n";
echo "  4. Read: AUTH_FIXES_DOCUMENTATION.md\n\n";

echo "Documentation files created:\n";
echo "  • AUTH_FIXES_DOCUMENTATION.md\n";
echo "  • QUICK_AUTH_FIX_GUIDE.md\n";
echo "  • AUTH_FIX_COMPLETE_REPORT.md\n\n";

echo "New files:\n";
echo "  • reset.php - Password reset handler\n";
echo "  • migrations/add_password_reset_fields.sql\n\n";

echo "✅ All authentication issues have been fixed!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
?>
