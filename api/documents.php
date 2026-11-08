<?php
/**
 * API: Document Upload and Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/document_parser.php';

session_init();
header('Content-Type: application/json');

$user = require_auth(false);
if (!$user) json_error('Unauthorized', 401);
if ($user['role'] !== 'admin') json_error('Admin access required', 403);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':  uploadDocument($user); break;
    case 'list':    listDocuments(); break;
    case 'get':     getDocument(sanitize_int($_GET['id'] ?? 0, 1)); break;
    case 'reindex': reindexDocument(sanitize_int($_GET['id'] ?? 0, 1)); break;
    case 'delete':  deleteDocument(sanitize_int($_GET['id'] ?? 0, 1), $user); break;
    default:        json_error('Invalid action', 400);
}

function uploadDocument(array $user): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

    if (empty($_FILES['document'])) json_error('No file uploaded.');

    $file = $_FILES['document'];

    // Validate
    $validation = validate_upload($file);
    if (!$validation['valid']) json_error($validation['error']);

    $title       = sanitize_string($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME), 255);
    $description = sanitize_string($_POST['description'] ?? '', 1000);
    $categoryId  = sanitize_int($_POST['category_id'] ?? 0, 0) ?: null;

    // Generate secure filename
    $storedName = secure_filename($file['name']);
    $uploadPath = UPLOAD_DIR . $storedName;

    // Ensure upload directory exists and is protected
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
        // Create .htaccess to prevent direct access
        file_put_contents(UPLOAD_DIR . '.htaccess', "Deny from all\n");
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        json_error('Failed to store uploaded file.');
    }

    // Save document record
    db_begin();
    try {
        db_query(
            "INSERT INTO knowledge_documents (title, description, category_id, original_name, stored_name, file_path, file_type, file_size, mime_type, status, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
            [
                $title, $description, $categoryId,
                $file['name'], $storedName, $uploadPath,
                $validation['ext'], $file['size'], $validation['mime'],
                $user['id']
            ]
        );
        $docId = (int)db_last_id();

        // Process immediately
        $result = processDocument($docId, $uploadPath, $validation['ext'], $title);

        if (!$result['success']) {
            db_query(
                "UPDATE knowledge_documents SET status='failed', error_message=? WHERE id=?",
                [$result['error'], $docId]
            );
        }

        db_commit();
        log_audit($user['id'], 'upload_document', 'knowledge_documents', $docId);

        json_response([
            'success'    => true,
            'document_id' => $docId,
            'message'    => $result['success']
                ? "Document uploaded and indexed with {$result['chunks']} chunks."
                : 'Document uploaded but indexing had issues: ' . $result['error'],
            'indexed'    => $result['success'],
            'chunks'     => $result['chunks'] ?? 0,
            'warning'    => $result['warning'] ?? null,
        ]);

    } catch (Exception $e) {
        db_rollback();
        @unlink($uploadPath);
        app_log('error', 'Document upload failed', ['error' => $e->getMessage()]);
        json_error(DEBUG_MODE ? $e->getMessage() : 'Document upload failed.', 500);
    }
}

function processDocument(int $docId, string $filePath, string $fileType, string $title): array {
    $parser = new DocumentParser();

    // Update status to processing
    db_query("UPDATE knowledge_documents SET status='processing' WHERE id=?", [$docId]);

    // Extract text
    $parseResult = $parser->parse($filePath, $fileType);
    if (!$parseResult['success']) {
        return $parseResult;
    }

    $extractedText = $parseResult['text'];
    $warning       = $parseResult['warning'] ?? null;

    // Chunk the text
    $chunks = $parser->chunkText($extractedText);

    if (empty($chunks)) {
        db_query(
            "UPDATE knowledge_documents SET status='failed', error_message='No text content could be extracted.' WHERE id=?",
            [$docId]
        );
        return ['success' => false, 'error' => 'No text could be extracted from the document.'];
    }

    // Create a knowledge item for the document
    $itemResult = db_query(
        "INSERT INTO knowledge_items (title, content, source_type, document_id, created_by) VALUES (?, ?, 'document', ?, 1)",
        [$title, $extractedText, $docId]
    );
    $itemId = (int)db_last_id();

    // Store chunks
    foreach ($chunks as $i => $chunk) {
        db_query(
            "INSERT INTO knowledge_chunks (document_id, item_id, chunk_index, content, char_count) VALUES (?, ?, ?, ?, ?)",
            [$docId, $itemId, $i, $chunk, strlen($chunk)]
        );
    }

    $chunkCount = count($chunks);
    db_query(
        "UPDATE knowledge_documents SET status='indexed', chunk_count=?, error_message=NULL WHERE id=?",
        [$chunkCount, $docId]
    );

    return ['success' => true, 'chunks' => $chunkCount, 'warning' => $warning];
}

function listDocuments(): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $docs = db_fetch_all(
        "SELECT kd.*, kc.name AS category_name, u.display_name AS uploaded_by_name
         FROM knowledge_documents kd
         LEFT JOIN knowledge_categories kc ON kd.category_id = kc.id
         LEFT JOIN users u ON kd.uploaded_by = u.id
         ORDER BY kd.created_at DESC
         LIMIT $limit OFFSET $offset"
    );

    $total = db_fetch_one("SELECT COUNT(*) AS cnt FROM knowledge_documents")['cnt'] ?? 0;

    json_response([
        'success'   => true,
        'documents' => $docs,
        'total'     => (int)$total,
        'page'      => $page,
        'pages'     => (int)ceil($total / $limit),
    ]);
}

function getDocument(int $id): void {
    if (!$id) json_error('ID required');
    $doc = db_fetch_one(
        "SELECT kd.*, kc.name AS category_name
         FROM knowledge_documents kd
         LEFT JOIN knowledge_categories kc ON kd.category_id = kc.id
         WHERE kd.id = ?",
        [$id]
    );
    if (!$doc) json_error('Document not found', 404);
    json_response(['success' => true, 'document' => $doc]);
}

function reindexDocument(int $id): void {
    if (!$id) json_error('ID required');

    $doc = db_fetch_one("SELECT * FROM knowledge_documents WHERE id=?", [$id]);
    if (!$doc) json_error('Document not found', 404);
    if (!file_exists($doc['file_path'])) json_error('Document file not found on disk.');

    // Clear existing chunks and knowledge item
    db_query("DELETE FROM knowledge_chunks WHERE document_id=?", [$id]);
    db_query("DELETE FROM knowledge_items WHERE document_id=?", [$id]);

    $result = processDocument($id, $doc['file_path'], $doc['file_type'], $doc['title']);

    if (!$result['success']) {
        json_error('Re-indexing failed: ' . $result['error']);
    }

    json_response([
        'success' => true,
        'message' => "Document re-indexed with {$result['chunks']} chunks.",
        'chunks'  => $result['chunks'],
    ]);
}

function deleteDocument(int $id, array $user): void {
    if (!$id) json_error('ID required');

    $doc = db_fetch_one("SELECT * FROM knowledge_documents WHERE id=?", [$id]);
    if (!$doc) json_error('Document not found', 404);

    // Delete physical file
    if (file_exists($doc['file_path'])) {
        @unlink($doc['file_path']);
    }

    // DB cascade will delete chunks and knowledge_items
    db_query("DELETE FROM knowledge_documents WHERE id=?", [$id]);
    log_audit($user['id'], 'delete_document', 'knowledge_documents', $id);

    json_response(['success' => true, 'message' => 'Document deleted.']);
}
