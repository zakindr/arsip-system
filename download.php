<?php
require_once 'includes/auth.php';
require_once 'includes/security.php';

$auth = new Auth();
$auth->requireLogin();

// Get file parameters
$filepath = $_GET['file'] ?? '';
$filename = $_GET['name'] ?? '';

if (empty($filepath) || empty($filename)) {
    http_response_code(400);
    die('Invalid download request');
}

// Sanitize file path
$filepath = Security::sanitizeInput($filepath, 'string');
$filename = Security::sanitizeInput($filename, 'string');

// Security check: ensure file is within upload directory
$uploadDir = realpath(__DIR__ . '/uploads/');
$realFilePath = realpath($filepath);

if (!$realFilePath || strpos($realFilePath, $uploadDir) !== 0) {
    Security::logSecurityEvent('download_security_violation', [
        'requested_file' => $filepath,
        'user_id' => $_SESSION['user_id']
    ]);
    
    http_response_code(403);
    die('Access denied');
}

// Check if file exists
if (!file_exists($realFilePath)) {
    http_response_code(404);
    die('File not found');
}

// Log download
Security::logSecurityEvent('file_download', [
    'filename' => $filename,
    'filepath' => $realFilePath,
    'user_id' => $_SESSION['user_id']
]);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($realFilePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($realFilePath);
exit;
?>
