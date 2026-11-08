<?php
/**
 * API: Settings Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

session_init();
header('Content-Type: application/json');

$user = require_auth(false);
if (!$user) json_error('Unauthorized', 401);

$action = $_GET['action'] ?? '';

// Public settings any user can read
if ($action === 'public') {
    getPublicSettings();
}

// Admin-only beyond this point
if ($user['role'] !== 'admin') json_error('Admin access required', 403);

switch ($action) {
    case 'get':        getAllSettings(); break;
    case 'update':     updateSettings(); break;
    case 'api':        getApiSettings(); break;
    case 'update_api': updateApiSettings($user); break;
    case 'test_api':   testApiConnection(); break;
    case 'dashboard':  getDashboardStats(); break;
    default:           json_error('Invalid action', 400);
}

function getPublicSettings(): void {
    $publicKeys = ['bot_name', 'bot_description', 'bot_personality', 'welcome_message', 'show_sources'];
    $rows = db_fetch_all(
        "SELECT setting_key, setting_value FROM chatbot_settings WHERE setting_key IN ('" . implode("','", $publicKeys) . "')"
    );
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    json_response(['success' => true, 'settings' => $settings]);
}

function getAllSettings(): void {
    $rows = db_fetch_all("SELECT setting_key, setting_value, setting_type, description FROM chatbot_settings ORDER BY setting_key");
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = [
            'value'       => $row['setting_value'],
            'type'        => $row['setting_type'],
            'description' => $row['description'],
        ];
    }
    json_response(['success' => true, 'settings' => $settings]);
}

function updateSettings(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) json_error('Invalid input');

    $allowedKeys = ['bot_name','bot_description','bot_personality','system_instructions',
                    'knowledge_mode','temperature','max_tokens','context_chunks',
                    'welcome_message','show_sources','allow_regenerate'];

    foreach ($input as $key => $value) {
        if (!in_array($key, $allowedKeys)) continue;

        $value = is_string($value) ? sanitize_string($value, 10000) : $value;
        db_query(
            "INSERT INTO chatbot_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
    }

    json_response(['success' => true, 'message' => 'Settings saved.']);
}

function getApiSettings(): void {
    $row = db_fetch_one("SELECT provider, api_url, model, is_active FROM api_settings WHERE is_active=1 ORDER BY id DESC LIMIT 1");
    $hasDbKey = (bool)db_fetch_one("SELECT id FROM api_settings WHERE is_active=1 AND api_key != '' ORDER BY id DESC LIMIT 1");
    $envKey = getenv('AI_API_KEY') ?: '';
    $hasKey = $hasDbKey || (!empty($envKey) && $envKey !== 'your-api-key-here');

    json_response([
        'success'  => true,
        'settings' => $row ?? ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'api_url' => 'https://api.openai.com/v1'],
        'has_key'  => $hasKey,
    ]);
}

function updateApiSettings(array $user): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    $input    = json_decode(file_get_contents('php://input'), true);
    $provider = sanitize_string($input['provider'] ?? 'openai', 50);
    $apiKey   = trim($input['api_key'] ?? '');
    $apiUrl   = sanitize_string($input['api_url'] ?? '', 500);
    $model    = sanitize_string($input['model'] ?? 'gpt-4o-mini', 100);

    if (empty($model)) json_error('Model name is required.');

    // Load existing saved key
    $existingRow = db_fetch_one("SELECT id, api_key FROM api_settings WHERE is_active=1 ORDER BY id DESC LIMIT 1");

    // Use newly provided key, or fall back to existing DB key, or env key
    $keyToStore = !empty($apiKey)
        ? $apiKey
        : ($existingRow['api_key'] ?? getenv('AI_API_KEY') ?: '');

    if (empty($keyToStore) || $keyToStore === 'your-api-key-here') {
        json_error('A valid API key is required.');
    }

    if ($existingRow) {
        db_query(
            "UPDATE api_settings SET provider=?, api_key=?, api_url=?, model=?, updated_at=NOW() WHERE id=?",
            [$provider, $keyToStore, $apiUrl, $model, $existingRow['id']]
        );
    } else {
        db_query(
            "INSERT INTO api_settings (provider, api_key, api_url, model) VALUES (?, ?, ?, ?)",
            [$provider, $keyToStore, $apiUrl, $model]
        );
    }

    log_audit($user['id'], 'update_api_settings', 'api_settings', null);
    json_response(['success' => true, 'message' => 'API settings saved successfully.']);
}

function testApiConnection(): void {
    require_once INCLUDES_DIR . 'ai_client.php';

    $ai = new AIClient();
    $result = $ai->chat([
        ['role' => 'user', 'content' => 'Reply with exactly: "Connection successful"'],
    ], ['max_tokens' => 20, 'temperature' => 0]);

    if ($result['success']) {
        json_response(['success' => true, 'message' => 'API connection successful!', 'response' => $result['content']]);
    } else {
        json_response(['success' => false, 'error' => $result['error']], 503);
    }
}

function getDashboardStats(): void {
    $stats = [
        'users'         => db_fetch_one("SELECT COUNT(*) AS cnt FROM users")['cnt'] ?? 0,
        'conversations' => db_fetch_one("SELECT COUNT(*) AS cnt FROM conversations")['cnt'] ?? 0,
        'messages'      => db_fetch_one("SELECT COUNT(*) AS cnt FROM messages")['cnt'] ?? 0,
        'knowledge'     => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_items WHERE is_active=1")['cnt'] ?? 0,
        'documents'     => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_documents")['cnt'] ?? 0,
        'chunks'        => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_chunks")['cnt'] ?? 0,
        'indexed_docs'  => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_documents WHERE status='indexed'")['cnt'] ?? 0,
    ];

    $apiRow = db_fetch_one("SELECT provider, model FROM api_settings WHERE is_active=1 ORDER BY id DESC LIMIT 1");
    $stats['ai_provider'] = $apiRow['provider'] ?? (getenv('AI_PROVIDER') ?: 'openai');
    $stats['ai_model']    = $apiRow['model']    ?? (getenv('AI_MODEL')    ?: 'gpt-4o-mini');

    $recentMessages = db_fetch_all(
        "SELECT m.role, LEFT(m.content, 100) AS content_preview, m.created_at, c.title AS conversation_title
         FROM messages m
         JOIN conversations c ON m.conversation_id = c.id
         ORDER BY m.created_at DESC LIMIT 10"
    );

    $recentUsers = db_fetch_all(
        "SELECT username, display_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5"
    );

    json_response([
        'success'         => true,
        'stats'           => $stats,
        'recent_messages' => $recentMessages,
        'recent_users'    => $recentUsers,
    ]);
}
