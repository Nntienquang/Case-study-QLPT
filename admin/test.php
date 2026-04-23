<?php
echo "=== Testing Admin Panel ===\n\n";

// Test 1: Database connection
echo "1. Testing database connection...\n";
require_once 'config/database.php';
if (isset($conn)) {
    echo "✓ Database connected\n";
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch();
        echo "✓ Users table has " . $row['count'] . " records\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Database not connected\n";
}

// Test 2: Middleware
echo "\n2. Testing middleware...\n";
try {
    require_once 'includes/middleware.php';
    echo "✓ Middleware loaded\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Helpers
echo "\n3. Testing helpers...\n";
try {
    require_once 'includes/helpers.php';
    echo "✓ Helpers loaded\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: BaseModel
echo "\n4. Testing BaseModel...\n";
try {
    require_once 'models/BaseModel.php';
    echo "✓ BaseModel loaded\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Models
echo "\n5. Testing models...\n";
try {
    require_once 'models/User.php';
    $user = new User();
    $count = $user->count();
    echo "✓ User model works - " . $count . " users\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

try {
    require_once 'models/Category.php';
    $cat = new Category();
    $count = $cat->count();
    echo "✓ Category model works - " . $count . " categories\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 6: Controllers
echo "\n6. Testing controllers...\n";
try {
    require_once 'controllers/DashboardController.php';
    $dashboard = new DashboardController();
    echo "✓ DashboardController loaded\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
