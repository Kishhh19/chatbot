<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Generate a CSRF token and store in session
 */
function csrf_token(): string {
    session_init();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from request
 */
function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sanitize string input
 */
function sanitize_string(string $input, int $maxLen = 1000): string {
    return substr(trim(strip_tags($input)), 0, $maxLen);
}

/**
 * Sanitize HTML (allow limited markdown-safe tags)
 */
function sanitize_html(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate and sanitize integer
 */
function sanitize_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int {
    $val = filter_var($value, FILTER_VALIDATE_INT);
    if ($val === false) return null;
    if ($val < $min || $val > $max) return null;
    return $val;
}

/**
 * Escape output for HTML
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Check rate limit. Returns true if allowed, false if limit exceeded.
 */
function check_rate_limit(string $identifier, string $action, int $limit = RATE_LIMIT_REQUESTS, int $window = RATE_LIMIT_WINDOW): bool {
    try {
        $windowStart = date('Y-m-d H:i:s', time() - $window);

        // Clean up old entries
        db_query(
            "DELETE FROM rate_limits WHERE identifier = ? AND action = ? AND window_start < ?",
            [$identifier, $action, $windowStart]
        );

        $row = db_fetch_one(
            "SELECT count FROM rate_limits WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );

        if (!$row) {
            db_query(
                "INSERT INTO rate_limits (identifier, action, count, window_start) VALUES (?, ?, 1, NOW())",
                [$identifier, $action]
            );
            return true;
        }

        if ($row['count'] >= $limit) return false;

        db_query(
            "UPDATE rate_limits SET count = count + 1 WHERE identifier = ? AND action = ?",
            [$identifier, $action]
        );
        return true;
    } catch (Exception $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return true; // Fail open to avoid blocking legit users on DB errors
    }
}

/**
 * Send a JSON response and terminate
 */
function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a JSON error response
 */
function json_error(string $message, int $status = 400, array $extra = []): void {
    json_response(array_merge(['success' => false, 'error' => $message], $extra), $status);
}

/**
 * Log application event to file
 */
function app_log(string $level, string $message, array $context = []): void {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0750, true);
    }
    $logFile = LOG_DIR . date('Y-m-d') . '.log';
    $contextStr = $context ? ' ' . json_encode($context) : '';
    $line = sprintf("[%s] [%s] %s%s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, $contextStr);
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Apply security headers to all responses
 */
function apply_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'");
}

/**
 * Validate uploaded file
 */
function validate_upload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload blocked by server extension.',
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['valid' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    // MIME type check
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        return ['valid' => false, 'error' => 'File MIME type not allowed: ' . $mime];
    }

    return ['valid' => true, 'ext' => $ext, 'mime' => $mime];
}

/**
 * Generate a secure stored filename
 */
function secure_filename(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
}
