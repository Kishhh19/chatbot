<?php
/**
 * Application Configuration
 * Sensitive values are loaded from .env file
 */

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Application
define('APP_NAME', getenv('APP_NAME') ?: 'AI Chatbot Platform');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/chatbot');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true' || APP_ENV === 'development');

// Database
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'chatbot_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'chatbot_session');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('SESSION_SECURE', false); // Set true with HTTPS
define('SESSION_HTTP_ONLY', true);

// File Upload
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['txt', 'csv', 'pdf', 'docx']);
define('ALLOWED_MIME_TYPES', [
    'text/plain',
    'text/csv',
    'application/csv',
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword',
]);

// Knowledge Base
define('CHUNK_SIZE', 1000);         // Characters per chunk
define('CHUNK_OVERLAP', 200);       // Overlap between chunks
define('MAX_CONTEXT_CHUNKS', 5);    // Max chunks to include in AI context
define('MAX_CONVERSATION_HISTORY', 10); // Max messages to send as history

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 30);  // Max requests
define('RATE_LIMIT_WINDOW', 60);    // Per N seconds

// Security
define('CSRF_TOKEN_LENGTH', 32);
define('BCRYPT_COST', 12);

// Logging
define('LOG_DIR', dirname(__DIR__) . '/logs/');
define('LOG_LEVEL', DEBUG_MODE ? 'debug' : 'error');

// Paths
define('BASE_DIR', dirname(__DIR__));
define('INCLUDES_DIR', dirname(__DIR__) . '/includes/');
define('CONFIG_DIR', __DIR__);
