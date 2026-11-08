<?php
/**
 * API: Knowledge Base Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

session_init();
header('Content-Type: application/json');

$user = require_auth(false);
if (!$user) json_error('Unauthorized', 401);
if ($user['role'] !== 'admin') json_error('Admin access required', 403);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = sanitize_int($_GET['id'] ?? 0, 1);

switch ($action) {
    case 'list':       listKnowledge(); break;
    case 'get':        getKnowledge($id); break;
    case 'create':     createKnowledge($user); break;
    case 'update':     updateKnowledge($id); break;
    case 'delete':     deleteKnowledge($id, $user); break;
    case 'categories': listCategories(); break;
    case 'stats':      getStats(); break;
    default:           json_error('Invalid action', 400);
}

function listKnowledge(): void {
    $search   = sanitize_string($_GET['search'] ?? '', 200);
    $category = sanitize_int($_GET['category'] ?? 0, 0);
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = 20;
    $offset   = ($page - 1) * $limit;

    $where  = "ki.is_active = 1";
    $params = [];

    if ($search) {
        $where   .= " AND MATCH(ki.title, ki.content, ki.tags) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $search . '*';
    }
    if ($category) {
        $where   .= " AND ki.category_id = ?";
        $params[] = $category;
    }

    $items = db_fetch_all(
        "SELECT ki.id, ki.title, ki.description, ki.tags, ki.source_type, ki.created_at, ki.updated_at,
                kc.name AS category_name, kc.color AS category_color,
                LEFT(ki.content, 200) AS content_preview,
                CHAR_LENGTH(ki.content) AS content_length
         FROM knowledge_items ki
         LEFT JOIN knowledge_categories kc ON ki.category_id = kc.id
         WHERE $where
         ORDER BY ki.updated_at DESC
         LIMIT $limit OFFSET $offset",
        $params
    );

    $countParams = $params;
    $total = db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_items ki WHERE $where", $countParams)['cnt'] ?? 0;

    json_response([
        'success' => true,
        'items'   => $items,
        'total'   => (int)$total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

function getKnowledge(int $id): void {
    if (!$id) json_error('ID required');
    $item = db_fetch_one(
        "SELECT ki.*, kc.name AS category_name FROM knowledge_items ki
         LEFT JOIN knowledge_categories kc ON ki.category_id = kc.id
         WHERE ki.id = ?",
        [$id]
    );
    if (!$item) json_error('Knowledge item not found', 404);
    json_response(['success' => true, 'item' => $item]);
}

function createKnowledge(array $user): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    $input       = json_decode(file_get_contents('php://input'), true);
    $title       = sanitize_string($input['title'] ?? '', 255);
    $content     = sanitize_string($input['content'] ?? '', 100000);
    $description = sanitize_string($input['description'] ?? '', 1000);
    $tags        = sanitize_string($input['tags'] ?? '', 500);
    $categoryId  = sanitize_int($input['category_id'] ?? 0, 0) ?: null;

    if (empty($title))   json_error('Title is required.');
    if (empty($content)) json_error('Content is required.');

    db_query(
        "INSERT INTO knowledge_items (title, description, content, tags, category_id, source_type, created_by) VALUES (?, ?, ?, ?, ?, 'manual', ?)",
        [$title, $description, $content, $tags, $categoryId, $user['id']]
    );
    $itemId = db_last_id();

    // Create chunks for this item too
    indexKnowledgeItem((int)$itemId, $content);
    log_audit($user['id'], 'create_knowledge', 'knowledge_items', (int)$itemId);

    json_response(['success' => true, 'id' => (int)$itemId, 'message' => 'Knowledge item created and indexed.']);
}

function updateKnowledge(int $id): void {
    if (!$id) json_error('ID required');
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') json_error('Method not allowed', 405);

    $item = db_fetch_one("SELECT id FROM knowledge_items WHERE id = ?", [$id]);
    if (!$item) json_error('Knowledge item not found', 404);

    $input       = json_decode(file_get_contents('php://input'), true);
    $title       = sanitize_string($input['title'] ?? '', 255);
    $content     = sanitize_string($input['content'] ?? '', 100000);
    $description = sanitize_string($input['description'] ?? '', 1000);
    $tags        = sanitize_string($input['tags'] ?? '', 500);
    $categoryId  = sanitize_int($input['category_id'] ?? 0, 0) ?: null;
    $isActive    = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;

    if (empty($title))   json_error('Title is required.');
    if (empty($content)) json_error('Content is required.');

    db_query(
        "UPDATE knowledge_items SET title=?, description=?, content=?, tags=?, category_id=?, is_active=? WHERE id=?",
        [$title, $description, $content, $tags, $categoryId, $isActive, $id]
    );

    // Re-index
    db_query("DELETE FROM knowledge_chunks WHERE item_id = ?", [$id]);
    indexKnowledgeItem($id, $content);

    json_response(['success' => true, 'message' => 'Knowledge item updated and re-indexed.']);
}

function deleteKnowledge(int $id, array $user): void {
    if (!$id) json_error('ID required');

    $item = db_fetch_one("SELECT id, source_type FROM knowledge_items WHERE id = ?", [$id]);
    if (!$item) json_error('Knowledge item not found', 404);
    if ($item['source_type'] === 'document') json_error('Document-sourced items are managed through the documents interface.');

    db_query("DELETE FROM knowledge_items WHERE id = ?", [$id]);
    log_audit($user['id'], 'delete_knowledge', 'knowledge_items', $id);

    json_response(['success' => true, 'message' => 'Knowledge item deleted.']);
}

function listCategories(): void {
    $categories = db_fetch_all(
        "SELECT kc.*, COUNT(ki.id) AS item_count
         FROM knowledge_categories kc
         LEFT JOIN knowledge_items ki ON kc.id = ki.category_id AND ki.is_active = 1
         GROUP BY kc.id
         ORDER BY kc.name ASC"
    );
    json_response(['success' => true, 'categories' => $categories]);
}

function getStats(): void {
    $stats = [
        'total_items'  => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_items WHERE is_active=1")['cnt'] ?? 0,
        'total_chunks' => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_chunks")['cnt'] ?? 0,
        'total_docs'   => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_documents")['cnt'] ?? 0,
        'indexed_docs' => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_documents WHERE status='indexed'")['cnt'] ?? 0,
        'categories'   => db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_categories")['cnt'] ?? 0,
    ];
    json_response(['success' => true, 'stats' => $stats]);
}

/**
 * Create knowledge chunks for a knowledge item
 */
function indexKnowledgeItem(int $itemId, string $content): void {
    require_once INCLUDES_DIR . 'document_parser.php';
    $parser = new DocumentParser();
    $chunks = $parser->chunkText($content);

    foreach ($chunks as $i => $chunk) {
        db_query(
            "INSERT INTO knowledge_chunks (document_id, item_id, chunk_index, content, char_count)
             VALUES (NULL, ?, ?, ?, ?)",
            [$itemId, $i, $chunk, strlen($chunk)]
        );
    }
}
