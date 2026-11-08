<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * RAG (Retrieval-Augmented Generation) Engine
 * Handles knowledge retrieval and context building
 */
class RAGEngine {
    private int $maxChunks;

    public function __construct(int $maxChunks = MAX_CONTEXT_CHUNKS) {
        $this->maxChunks = $maxChunks;
    }

    /**
     * Retrieve relevant knowledge chunks for a query using full-text + keyword search
     *
     * @param string $query      User's question
     * @param int    $limit      Max chunks to return
     * @return array             Array of knowledge chunks with metadata
     */
    public function retrieve(string $query, int $limit = null): array {
        $limit = $limit ?? $this->maxChunks;
        $cleanQuery = sanitize_string($query, 1000);

        $results = [];

        // 1. Full-text search on knowledge_items (manually entered knowledge)
        $itemResults = $this->searchKnowledgeItems($cleanQuery, $limit);
        $results = array_merge($results, $itemResults);

        // 2. Full-text search on knowledge_chunks (from documents)
        $chunkResults = $this->searchKnowledgeChunks($cleanQuery, $limit);
        $results = array_merge($results, $chunkResults);

        // 3. Deduplicate and rank
        $results = $this->rankAndDeduplicate($results, $cleanQuery);

        return array_slice($results, 0, $limit);
    }

    /**
     * Search manually-entered knowledge items
     */
    private function searchKnowledgeItems(string $query, int $limit): array {
        try {
            // Full-text search
            $sql = "
                SELECT 
                    ki.id,
                    ki.title,
                    ki.content,
                    ki.tags,
                    kc.name AS category_name,
                    'knowledge_item' AS source_type,
                    ki.id AS source_id,
                    MATCH(ki.title, ki.content, ki.tags) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance_score
                FROM knowledge_items ki
                LEFT JOIN knowledge_categories kc ON ki.category_id = kc.id
                WHERE ki.is_active = 1
                    AND MATCH(ki.title, ki.content, ki.tags) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance_score DESC
                LIMIT ?
            ";
            $rows = db_fetch_all($sql, [$query, $query, $limit]);

            // If no full-text results, try LIKE fallback
            if (empty($rows)) {
                $rows = $this->likeSearchItems($query, $limit);
            }

            return array_map(fn($r) => $this->formatItemResult($r), $rows);
        } catch (Exception $e) {
            app_log('error', 'RAG item search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fallback LIKE search for knowledge items
     */
    private function likeSearchItems(string $query, int $limit): array {
        $terms = $this->extractKeywords($query);
        if (empty($terms)) return [];

        $conditions = [];
        $params = [];
        foreach (array_slice($terms, 0, 5) as $term) {
            $conditions[] = "(ki.title LIKE ? OR ki.content LIKE ? OR ki.tags LIKE ?)";
            $params[] = "%$term%";
            $params[] = "%$term%";
            $params[] = "%$term%";
        }
        $params[] = $limit;

        $sql = "
            SELECT ki.id, ki.title, ki.content, ki.tags,
                   kc.name AS category_name,
                   'knowledge_item' AS source_type, ki.id AS source_id,
                   0.5 AS relevance_score
            FROM knowledge_items ki
            LEFT JOIN knowledge_categories kc ON ki.category_id = kc.id
            WHERE ki.is_active = 1 AND (" . implode(' OR ', $conditions) . ")
            LIMIT ?
        ";

        return db_fetch_all($sql, $params);
    }

    /**
     * Search document chunks
     */
    private function searchKnowledgeChunks(string $query, int $limit): array {
        try {
            $sql = "
                SELECT 
                    kc.id,
                    COALESCE(kd.title, ki.title, 'Knowledge Item') AS title,
                    kc.content,
                    '' AS tags,
                    COALESCE(cat.name, cat2.name) AS category_name,
                    CASE WHEN kc.document_id IS NOT NULL THEN 'document' ELSE 'knowledge_item' END AS source_type,
                    COALESCE(kc.document_id, kc.item_id) AS source_id,
                    MATCH(kc.content) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance_score
                FROM knowledge_chunks kc
                LEFT JOIN knowledge_documents kd ON kc.document_id = kd.id
                LEFT JOIN knowledge_items ki ON kc.item_id = ki.id
                LEFT JOIN knowledge_categories cat ON kd.category_id = cat.id
                LEFT JOIN knowledge_categories cat2 ON ki.category_id = cat2.id
                WHERE (kd.status = 'indexed' OR kc.document_id IS NULL)
                    AND (ki.is_active = 1 OR kc.document_id IS NOT NULL)
                    AND MATCH(kc.content) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance_score DESC
                LIMIT ?
            ";
            $rows = db_fetch_all($sql, [$query, $query, $limit]);

            if (empty($rows)) {
                $rows = $this->likeSearchChunks($query, $limit);
            }

            return array_map(fn($r) => $this->formatChunkResult($r), $rows);
        } catch (Exception $e) {
            app_log('error', 'RAG chunk search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fallback LIKE search for chunks
     */
    private function likeSearchChunks(string $query, int $limit): array {
        $terms = $this->extractKeywords($query);
        if (empty($terms)) return [];

        $conditions = [];
        $params = [];
        foreach (array_slice($terms, 0, 5) as $term) {
            $conditions[] = "kc.content LIKE ?";
            $params[] = "%$term%";
        }
        $params[] = $limit;

        $sql = "
            SELECT kc.id,
                   COALESCE(kd.title, ki.title, 'Knowledge Item') AS title,
                   kc.content, '' AS tags,
                   COALESCE(cat.name, cat2.name) AS category_name,
                   CASE WHEN kc.document_id IS NOT NULL THEN 'document' ELSE 'knowledge_item' END AS source_type,
                   COALESCE(kc.document_id, kc.item_id) AS source_id,
                   0.3 AS relevance_score
            FROM knowledge_chunks kc
            LEFT JOIN knowledge_documents kd ON kc.document_id = kd.id
            LEFT JOIN knowledge_items ki ON kc.item_id = ki.id
            LEFT JOIN knowledge_categories cat ON kd.category_id = cat.id
            LEFT JOIN knowledge_categories cat2 ON ki.category_id = cat2.id
            WHERE (kd.status = 'indexed' OR kc.document_id IS NULL)
              AND (ki.is_active = 1 OR kc.document_id IS NOT NULL)
              AND (" . implode(' OR ', $conditions) . ")
            LIMIT ?
        ";

        return db_fetch_all($sql, $params);
    }

    /**
     * Format knowledge item result
     */
    private function formatItemResult(array $row): array {
        return [
            'id'           => $row['id'],
            'title'        => $row['title'],
            'content'      => $row['content'],
            'tags'         => $row['tags'] ?? '',
            'category'     => $row['category_name'] ?? 'General',
            'source_type'  => 'knowledge_item',
            'source_id'    => $row['source_id'] ?? $row['id'],
            'score'        => (float)($row['relevance_score'] ?? 0),
        ];
    }

    /**
     * Format chunk result
     */
    private function formatChunkResult(array $row): array {
        return [
            'id'           => $row['id'],
            'title'        => $row['title'],
            'content'      => $row['content'],
            'tags'         => '',
            'category'     => $row['category_name'] ?? 'Document',
            'source_type'  => 'document',
            'source_id'    => $row['source_id'],
            'score'        => (float)($row['relevance_score'] ?? 0),
        ];
    }

    /**
     * Rank by relevance, deduplicate similar content
     */
    private function rankAndDeduplicate(array $results, string $query): array {
        // Boost score with keyword frequency
        $keywords = $this->extractKeywords($query);
        foreach ($results as &$result) {
            $contentLower = strtolower($result['content'] . ' ' . $result['title']);
            $boost = 0;
            foreach ($keywords as $kw) {
                $boost += substr_count($contentLower, strtolower($kw)) * 0.1;
            }
            $result['score'] += min($boost, 2.0);
        }
        unset($result);

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Deduplicate by title+source
        $seen = [];
        $unique = [];
        foreach ($results as $result) {
            $key = $result['source_type'] . ':' . $result['source_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $result;
            }
        }

        return $unique;
    }

    /**
     * Build the context string to inject into AI prompt
     */
    public function buildContext(array $chunks): string {
        if (empty($chunks)) return '';

        $context = "=== KNOWLEDGE BASE ===\n\n";
        foreach ($chunks as $i => $chunk) {
            $context .= "--- Source " . ($i + 1) . ": {$chunk['title']}";
            if ($chunk['category']) $context .= " [{$chunk['category']}]";
            $context .= " ---\n";
            $context .= trim($chunk['content']) . "\n\n";
        }
        $context .= "=== END OF KNOWLEDGE BASE ===";
        return $context;
    }

    /**
     * Build source attribution list
     */
    public function buildSourceList(array $chunks): array {
        $sources = [];
        $seen = [];
        foreach ($chunks as $chunk) {
            $key = $chunk['source_type'] . ':' . $chunk['source_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $sources[] = [
                    'title'       => $chunk['title'],
                    'category'    => $chunk['category'],
                    'source_type' => $chunk['source_type'],
                    'source_id'   => $chunk['source_id'],
                ];
            }
        }
        return $sources;
    }

    /**
     * Extract meaningful keywords from query
     */
    private function extractKeywords(string $query): array {
        $stopWords = ['a','an','the','is','are','was','were','be','been','being','have','has','had',
                      'do','does','did','will','would','could','should','may','might','shall','can',
                      'to','of','in','for','on','with','at','by','from','up','about','into','through',
                      'what','when','where','who','which','how','why','that','this','these','those',
                      'and','or','but','not','if','then','than','so','yet','both','either','neither'];

        $words = preg_split('/\s+/', strtolower($query));
        $keywords = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        return array_values(array_unique($keywords));
    }

    /**
     * Build the full prompt array for the AI
     */
    public function buildPrompt(
        array  $chunks,
        array  $conversationHistory,
        string $userMessage,
        string $systemInstructions,
        string $knowledgeMode = 'hybrid',
        bool   $showSources = true
    ): array {

        $contextStr = $this->buildContext($chunks);
        $hasContext = !empty($chunks);

        // Build system message
        $systemParts = [];
        $systemParts[] = $systemInstructions;

        if ($hasContext) {
            $systemParts[] = "\n\n" . $contextStr;
        }

        // Natural, conversational instruction rules
        $systemParts[] = "\n\nCRITICAL CONVERSATIONAL GUIDELINES:";
        $systemParts[] = "\n- Adapt naturally to the conversation history. Maintain continuity, context, and a helpful, intelligent persona across all chat turns.";
        $systemParts[] = "\n- Provide direct, concise, and clear answers. Do NOT say 'According to the knowledge base', 'Based on the provided documents', or 'The knowledge base does not contain information about'. Never mention 'knowledge base' or 'documents' in your response unless directly asked about them.";
        $systemParts[] = "\n- Integrate provided factual knowledge seamlessly with general AI capabilities to deliver accurate and natural answers.";

        $systemParts[] = "\n\nCurrent date/time: " . date('Y-m-d H:i:s');

        $messages = [
            ['role' => 'system', 'content' => implode('', $systemParts)],
        ];

        // Add conversation history (limited)
        foreach (array_slice($conversationHistory, -MAX_CONVERSATION_HISTORY) as $msg) {
            if ($msg['role'] === 'user' || $msg['role'] === 'assistant') {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }
}
