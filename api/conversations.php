<?php
/**
 * API: Conversations (CRUD)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

session_init();
header('Content-Type: application/json');

$user = require_auth(false);
if (!$user) json_error('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = sanitize_int($_GET['id'] ?? 0, 1);

switch ($method) {
    case 'GET':
        if ($id) {
            getConversation($user, $id);
        } else {
            listConversations($user);
        }
        break;
    case 'POST':
        if ($action === 'create') createConversation($user);
        else json_error('Invalid action', 400);
        break;
    case 'PUT':
        if ($id) updateConversation($user, $id);
        else json_error('ID required', 400);
        break;
    case 'DELETE':
        if ($id) deleteConversation($user, $id);
        else json_error('ID required', 400);
        break;
    default:
        json_error('Method not allowed', 405);
}

function listConversations(array $user): void {
    $search = sanitize_string($_GET['search'] ?? '', 200);
    $page   = max(1, sanitize_int($_GET['page'] ?? 1, 1));
    $limit  = 30;
    $offset = ($page - 1) * $limit;

    $params = [$user['id']];
    $where  = "user_id = ?";

    if ($search) {
        $where    .= " AND title LIKE ?";
        $params[]  = "%$search%";
    }

    $conversations = db_fetch_all(
        "SELECT id, title, is_pinned, created_at, updated_at,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = conversations.id) AS message_count
         FROM conversations
         WHERE $where
         ORDER BY is_pinned DESC, updated_at DESC
         LIMIT $limit OFFSET $offset",
        $params
    );

    $total = db_fetch_one("SELECT COUNT(*) AS cnt FROM conversations WHERE $where", $params)['cnt'] ?? 0;

    json_response([
        'success'       => true,
        'conversations' => $conversations,
        'total'         => (int)$total,
        'page'          => $page,
        'pages'         => ceil($total / $limit),
    ]);
}

function getConversation(array $user, int $id): void {
    $conv = db_fetch_one(
        "SELECT * FROM conversations WHERE id = ? AND user_id = ?",
        [$id, $user['id']]
    );
    if (!$conv) json_error('Conversation not found', 404);

    $messages = db_fetch_all(
        "SELECT id, role, content, sources, tokens_used, model_used, created_at
         FROM messages
         WHERE conversation_id = ?
         ORDER BY created_at ASC",
        [$id]
    );

    // Decode JSON sources
    foreach ($messages as &$msg) {
        if ($msg['sources']) {
            $msg['sources'] = json_decode($msg['sources'], true);
        }
    }
    unset($msg);

    json_response([
        'success'      => true,
        'conversation' => $conv,
        'messages'     => $messages,
    ]);
}

function createConversation(array $user): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = sanitize_string($input['title'] ?? 'New Conversation', 255);

    db_query(
        "INSERT INTO conversations (user_id, title) VALUES (?, ?)",
        [$user['id'], $title]
    );
    $id = db_last_id();

    $conv = db_fetch_one("SELECT * FROM conversations WHERE id = ?", [$id]);
    json_response(['success' => true, 'conversation' => $conv]);
}

function updateConversation(array $user, int $id): void {
    $conv = db_fetch_one(
        "SELECT * FROM conversations WHERE id = ? AND user_id = ?",
        [$id, $user['id']]
    );
    if (!$conv) json_error('Conversation not found', 404);

    $input    = json_decode(file_get_contents('php://input'), true);
    $title    = isset($input['title'])    ? sanitize_string($input['title'], 255)   : null;
    $isPinned = isset($input['is_pinned']) ? (int)(bool)$input['is_pinned'] : null;

    $sets = [];
    $params = [];
    if ($title !== null)    { $sets[] = 'title = ?';     $params[] = $title; }
    if ($isPinned !== null) { $sets[] = 'is_pinned = ?'; $params[] = $isPinned; }

    if (empty($sets)) json_error('Nothing to update');

    $params[] = $id;
    db_query("UPDATE conversations SET " . implode(', ', $sets) . " WHERE id = ?", $params);

    json_response(['success' => true, 'message' => 'Conversation updated.']);
}

function deleteConversation(array $user, int $id): void {
    $conv = db_fetch_one(
        "SELECT id FROM conversations WHERE id = ? AND user_id = ?",
        [$id, $user['id']]
    );
    if (!$conv) json_error('Conversation not found', 404);

    db_query("DELETE FROM conversations WHERE id = ?", [$id]);
    log_audit($user['id'], 'delete_conversation', 'conversations', $id);

    json_response(['success' => true, 'message' => 'Conversation deleted.']);
}
