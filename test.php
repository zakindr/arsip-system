<?php
// Simple test file to check for errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . phpversion() . "<br>";
echo "Session Status: " . session_status() . "<br>";

// Test basic includes
try {
    echo "Testing includes/security.php... ";
    require_once 'includes/security.php';
    echo "OK<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing includes/database.php... ";
    require_once 'includes/database.php';
    echo "OK<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

try {
    echo "Testing includes/auth.php... ";
    require_once 'includes/auth.php';
    echo "OK<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "All tests completed.";
?>
