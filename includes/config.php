<?php
/**
 * Configuration file for Sistem Arsip Unmul
 * Centralized configuration for easy maintenance
 */

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Settings
define('APP_NAME', 'Sistem Arsip Unmul');
define('APP_VERSION', '2.0.0');
define('APP_DEBUG', false); // Set to true for debugging

// Security Settings
define('SESSION_TIMEOUT', 7200); // 2 hours in seconds
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900); // 15 minutes
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// File Upload Settings
define('UPLOAD_MAX_SIZE', 10485760); // 10MB in bytes
define('UPLOAD_ALLOWED_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png',
    'image/gif',
    'text/plain',
    'text/csv'
]);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'arsip');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Pagination Settings
define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE', 100);

// Logging Settings
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 10485760); // 10MB
define('LOG_MAX_FILES', 5);

// Email Settings (for future notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@unmul.ac.id');
define('SMTP_FROM_NAME', 'Sistem Arsip Unmul');

// API Settings
define('API_RATE_LIMIT', 100); // requests per hour per IP
define('API_TOKEN_EXPIRY', 3600); // 1 hour

// UI Settings
define('THEME_COLOR_PRIMARY', '#667eea');
define('THEME_COLOR_SECONDARY', '#764ba2');
define('THEME_LOGO_URL', '');
define('THEME_FAVICON_URL', '');

// Feature Flags
define('FEATURE_FILE_UPLOAD', true);
define('FEATURE_API_ACCESS', true);
define('FEATURE_USER_REGISTRATION', false);
define('FEATURE_EMAIL_NOTIFICATIONS', false);
define('FEATURE_AUDIT_LOG', true);
define('FEATURE_BACKUP', false);

// Base URL configuration
$baseUrl = '';

// Function to get current page name
function getCurrentPage() {
    $currentFile = basename($_SERVER['PHP_SELF'], '.php');
    return $currentFile;
}

// Set current page
$currentPage = getCurrentPage();

// Utility functions
function config($key, $default = null) {
    $config = [
        'app_name' => APP_NAME,
        'app_version' => APP_VERSION,
        'app_debug' => APP_DEBUG,
        'session_timeout' => SESSION_TIMEOUT,
        'upload_max_size' => UPLOAD_MAX_SIZE,
        'upload_path' => UPLOAD_PATH,
        'default_page_size' => DEFAULT_PAGE_SIZE,
        'theme_primary' => THEME_COLOR_PRIMARY,
        'theme_secondary' => THEME_COLOR_SECONDARY
    ];
    
    return $config[$key] ?? $default;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function isDebugMode() {
    return APP_DEBUG;
}

function getUploadPath() {
    return UPLOAD_PATH;
}

function getAllowedFileTypes() {
    return UPLOAD_ALLOWED_TYPES;
}

function getMaxUploadSize() {
    return UPLOAD_MAX_SIZE;
}

// Error reporting based on debug mode
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Asia/Makassar');

// Create necessary directories if they don't exist
$directories = [
    LOG_PATH,
    UPLOAD_PATH,
    UPLOAD_PATH . date('Y/m')
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>