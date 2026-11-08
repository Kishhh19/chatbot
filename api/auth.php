<?php
/**
 * API: Authentication (login, register, logout)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

session_init();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleMe();
        break;
    default:
        json_error('Invalid action', 400);
}

function handleLogin(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    $ip = get_client_ip();
    if (!check_rate_limit($ip, 'login', 10, 300)) {
        json_error('Too many login attempts. Please wait 5 minutes.', 429);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $credential = sanitize_string($input['credential'] ?? '', 255);
    $password   = $input['password'] ?? '';

    if (empty($credential) || empty($password)) {
        json_error('Email/username and password are required.');
    }

    $user = authenticate_user($credential, $password);
    if (!$user) {
        app_log('warning', 'Failed login attempt', ['credential' => $credential, 'ip' => $ip]);
        json_error('Invalid credentials. Please check your email/username and password.');
    }

    if (!$user['is_active']) {
        json_error('Your account has been deactivated. Please contact an administrator.');
    }

    login_user($user);

    json_response([
        'success'      => true,
        'user'         => [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'email'        => $user['email'],
            'display_name' => $user['display_name'],
            'role'         => $user['role'],
        ],
        'redirect'     => APP_URL . '/index.php',
    ]);
}

function handleRegister(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    $ip = get_client_ip();
    if (!check_rate_limit($ip, 'register', 5, 3600)) {
        json_error('Too many registration attempts. Please try again later.', 429);
    }

    $input       = json_decode(file_get_contents('php://input'), true);
    $username    = sanitize_string($input['username'] ?? '', 50);
    $email       = sanitize_string($input['email'] ?? '', 255);
    $password    = $input['password'] ?? '';
    $displayName = sanitize_string($input['display_name'] ?? $username, 100);

    // Check if first user (make admin)
    $isFirst = !admin_exists();
    $role    = $isFirst ? 'admin' : 'user';

    $result = register_user($username, $email, $password, $displayName, $role);
    if (!$result['success']) {
        json_error($result['error']);
    }

    json_response([
        'success' => true,
        'message' => $isFirst
            ? 'Admin account created successfully! Please log in.'
            : 'Account created successfully! Please log in.',
        'is_admin' => $isFirst,
    ]);
}

function handleLogout(): void {
    logout_user();
    json_response(['success' => true, 'redirect' => APP_URL . '/login.php']);
}

function handleMe(): void {
    $user = get_current_user_data();
    if (!$user) json_error('Not authenticated', 401);
    json_response(['success' => true, 'user' => $user]);
}
