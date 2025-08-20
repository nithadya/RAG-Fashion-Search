<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '1488@@Mihisara');
define('DB_NAME', 'ecommerce_sl');

// Base URL
define('BASE_URL', 'http://localhost/ecommerce');

// OpenAI API Key (for AI features)
define('OPENAI_API_KEY', 'your-openai-api-key');

// JWT Secret Key
define('JWT_SECRET', 'your-secret-key-for-jwt');

// Application environment: read from ENV var when available, otherwise default to 'development'
// Set ENVIRONMENT to 'production' on production servers (e.g., via your web server or CI/CD pipeline)
if (!defined('ENVIRONMENT')) {
    $env = getenv('ENVIRONMENT');
    define('ENVIRONMENT', $env !== false && $env !== '' ? $env : 'development');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Colombo');
