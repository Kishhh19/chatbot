<?php
/**
 * API: Chat Endpoint
 * POST /api/chat.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../includes/rag.php';
require_once __DIR__ . '/../includes/sabino.php';

session_init();
header('Content-Type: application/json');

// Auth check
$user = require_auth(false);
if (!$user) json_error('Unauthorized', 401);

// Rate limiting
if (!check_rate_limit('chat_' . $user['id'], 'chat', 60, 60)) {
    json_error('Too many requests. Please slow down.', 429);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) json_error('Invalid JSON body');

// Validate inputs
$message         = sanitize_string($input['message'] ?? '', 10000);
$conversationId  = sanitize_int($input['conversation_id'] ?? 0, 1);
$regenerate      = (bool)($input['regenerate'] ?? false);

if (empty($message)) json_error('Message cannot be empty.');
if (!$conversationId) json_error('Invalid conversation ID.');

try {
    // Verify conversation belongs to this user
    $conversation = db_fetch_one(
        "SELECT * FROM conversations WHERE id = ? AND user_id = ?",
        [$conversationId, $user['id']]
    );
    if (!$conversation) json_error('Conversation not found.', 404);

    // Load chatbot settings
    $settings = [];
    $rows = db_fetch_all("SELECT setting_key, setting_value FROM chatbot_settings");
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $shop = sabino_data();
    $sabinoInstructions = "You are Sabinox, the helpful assistant for Sabino café. Confirmed address: {$shop['address']}. Phone: {$shop['phone']}. Reported hours: {$shop['hours']} The sample menu and prices are illustrative Philippine café examples, not confirmed Sabino offerings. Never claim they are official. If shop details are unconfirmed, direct the guest to call the shop. ";
    $systemInstructions = $sabinoInstructions . ($settings['system_instructions'] ?? '');
    $knowledgeMode      = $settings['knowledge_mode']      ?? 'hybrid';
    $temperature        = (float)($settings['temperature']  ?? 0.7);
    $maxTokens          = (int)($settings['max_tokens']     ?? 2000);
    $contextChunks      = (int)($settings['context_chunks'] ?? MAX_CONTEXT_CHUNKS);
    $showSources        = ($settings['show_sources'] ?? 'true') === 'true';
    $botName            = $settings['bot_name'] ?? 'AI Assistant';

    // Save user message
    db_query(
        "INSERT INTO messages (conversation_id, role, content) VALUES (?, 'user', ?)",
        [$conversationId, $message]
    );
    $userMsgId = db_last_id();

    // Auto-title conversation if it's the first message
    $msgCount = db_fetch_one(
        "SELECT COUNT(*) AS cnt FROM messages WHERE conversation_id = ?",
        [$conversationId]
    );
    if ((int)($msgCount['cnt'] ?? 0) <= 1) {
        $autoTitle = mb_substr(strip_tags($message), 0, 60);
        if (strlen($message) > 60) $autoTitle .= '…';
        db_query(
            "UPDATE conversations SET title = ? WHERE id = ?",
            [$autoTitle, $conversationId]
        );
    }

    // Answer core café questions from the same verified facts used by the public helper.
    $faq = sabino_reply($message);
    if ($faq['matched']) {
        db_query(
            "INSERT INTO messages (conversation_id, role, content, model_used) VALUES (?, 'assistant', ?, ?)",
            [$conversationId, $faq['content'], 'Sabinox FAQ']
        );
        $assistantMsgId = db_last_id();
        db_query("UPDATE conversations SET updated_at = NOW() WHERE id = ?", [$conversationId]);
        json_response([
            'success' => true,
            'message_id' => (int)$assistantMsgId,
            'content' => $faq['content'],
            'sources' => [],
            'usage' => ['total_tokens' => 0],
            'model' => 'Sabinox FAQ',
            'has_context' => true,
        ]);
    }

    // Load conversation history (exclude current message)
    $history = db_fetch_all(
        "SELECT role, content FROM messages 
         WHERE conversation_id = ? AND id != ? 
         ORDER BY created_at ASC 
         LIMIT ?",
        [$conversationId, $userMsgId, MAX_CONVERSATION_HISTORY]
    );

    // RAG retrieval
    $rag    = new RAGEngine($contextChunks);
    $chunks = $rag->retrieve($message, $contextChunks);

    // Build prompt
    $messages = $rag->buildPrompt(
        $chunks,
        $history,
        $message,
        $systemInstructions,
        $knowledgeMode,
        $showSources
    );

    // Call AI
    $ai = new AIClient();
    $aiResult = $ai->chat($messages, [
        'temperature' => $temperature,
        'max_tokens'  => $maxTokens,
    ]);

    if (!$aiResult['success']) {
        json_error($aiResult['error'] ?? 'AI service error.', 503);
    }

    $aiContent  = $aiResult['content'];
    $tokensUsed = $aiResult['usage']['total_tokens'] ?? null;
    $modelUsed  = $aiResult['model'] ?? null;
    $sources    = $showSources ? $rag->buildSourceList($chunks) : [];

    // Save assistant message
    db_query(
        "INSERT INTO messages (conversation_id, role, content, sources, tokens_used, model_used) VALUES (?, 'assistant', ?, ?, ?, ?)",
        [
            $conversationId,
            $aiContent,
            $sources ? json_encode($sources) : null,
            $tokensUsed,
            $modelUsed
        ]
    );
    $assistantMsgId = db_last_id();

    // Update conversation timestamp
    db_query("UPDATE conversations SET updated_at = NOW() WHERE id = ?", [$conversationId]);

    json_response([
        'success'    => true,
        'message_id' => (int)$assistantMsgId,
        'content'    => $aiContent,
        'sources'    => $sources,
        'usage'      => $aiResult['usage'],
        'model'      => $modelUsed,
        'has_context' => !empty($chunks),
    ]);

} catch (Exception $e) {
    app_log('error', 'Chat API error', ['error' => $e->getMessage(), 'user' => $user['id']]);
    json_error(DEBUG_MODE ? $e->getMessage() : 'An error occurred. Please try again.', 500);
}
