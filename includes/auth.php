<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Initialize session with security settings
 */
function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', SESSION_HTTP_ONLY ? '1' : '0');
        ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Get the currently authenticated user or null
 */
function get_current_user_data(): ?array {
    session_init();
    if (!isset($_SESSION['user_id'])) return null;

    // Validate session freshness
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_destroy();
        return null;
    }

    // Regenerate session periodically (every 30 min)
    if (!isset($_SESSION['last_regen']) || (time() - $_SESSION['last_regen']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }

    $_SESSION['last_activity'] = time();

    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'],
        'email'        => $_SESSION['email'],
        'display_name' => $_SESSION['display_name'],
        'role'         => $_SESSION['role'],
    ];
}

/**
 * Check if user is logged in. If not, redirect or return false.
 */
function require_auth(bool $redirect = true): ?array {
    $user = get_current_user_data();
    if (!$user) {
        if ($redirect) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
        return null;
    }
    return $user;
}

/**
 * Check if user is admin. Redirects or returns false.
 */
function require_admin(bool $redirect = true): ?array {
    $user = require_auth($redirect);
    if (!$user) return null;
    if ($user['role'] !== 'admin') {
        if ($redirect) {
            header('Location: ' . APP_URL . '/index.php?error=unauthorized');
            exit;
        }
        return null;
    }
    return $user;
}

/**
 * Log the user in and create session
 */
function login_user(array $user): void {
    session_init();
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regen']    = time();

    // Update last login
    db_query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    log_audit($user['id'], 'login', 'users', $user['id']);
}

/**
 * Log out the current user
 */
function logout_user(): void {
    session_init();
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) log_audit($userId, 'logout', 'users', $userId);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Register a new user
 */
function register_user(string $username, string $email, string $password, string $displayName, string $role = 'user'): array {
    // Validate
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Username must be 3-50 characters.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Username may only contain letters, numbers, and underscores.'];
    }

    // Check duplicates
    $existing = db_fetch_one("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username]);
    if ($existing) {
        return ['success' => false, 'error' => 'Email or username already registered.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    db_query(
        "INSERT INTO users (username, email, password, display_name, role) VALUES (?, ?, ?, ?, ?)",
        [$username, $email, $hash, $displayName ?: $username, $role]
    );
    $id = db_last_id();
    log_audit((int)$id, 'register', 'users', (int)$id);
    return ['success' => true, 'user_id' => $id];
}

/**
 * Attempt to authenticate a user by email/password
 */
function authenticate_user(string $emailOrUsername, string $password): ?array {
    $user = db_fetch_one(
        "SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1 LIMIT 1",
        [$emailOrUsername, $emailOrUsername]
    );
    if (!$user) return null;
    if (!password_verify($password, $user['password'])) return null;
    return $user;
}

/**
 * Log an audit event
 */
function log_audit(?int $userId, string $action, string $entity = null, int $entityId = null, array $details = []): void {
    try {
        db_query(
            "INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $action, $entity, $entityId, $details ? json_encode($details) : null, get_client_ip()]
        );
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/**
 * Get client IP address safely
 */
function get_client_ip(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return 'unknown';
}

/**
 * Check if admin account exists; used during setup
 */
function admin_exists(): bool {
    $result = db_fetch_one("SELECT COUNT(*) as cnt FROM users WHERE role='admin'");
    return ($result['cnt'] ?? 0) > 0;
}
