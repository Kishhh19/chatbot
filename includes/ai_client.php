<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * AI Client - Abstraction layer for multiple AI providers
 * Supports: OpenAI, Anthropic, OpenRouter (and any OpenAI-compatible API)
 */
class AIClient {
    private string $provider;
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private array  $defaultOptions;

    public function __construct(?array $settings = null) {
        // Load from DB or fall back to env
        $this->loadSettings($settings);
    }

    private function loadSettings(?array $settings): void {
        if ($settings) {
            $this->provider = $settings['provider'] ?? 'openai';
            $this->apiKey   = $settings['api_key']  ?? '';
            $this->model    = $settings['model']     ?? 'gpt-4o-mini';
            $this->apiUrl   = $settings['api_url']   ?? 'https://api.openai.com/v1';
        } else {
            // Try DB first, then env
            try {
                $row = db_fetch_one("SELECT * FROM api_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            } catch (Exception $e) {
                $row = null;
            }

            $this->provider = $row['provider'] ?? (getenv('AI_PROVIDER') ?: 'openai');
            $this->apiKey   = $row['api_key']  ?? (getenv('AI_API_KEY') ?: '');
            $this->model    = $row['model']    ?? (getenv('AI_MODEL')   ?: 'gpt-4o-mini');
            $this->apiUrl   = $row['api_url']  ?? (getenv('AI_API_URL') ?: 'https://api.openai.com/v1');
        }

        // Normalize URL
        $this->apiUrl = rtrim($this->apiUrl, '/');
    }

    /**
     * Send a chat completion request
     *
     * @param array  $messages   Array of {role, content} messages
     * @param array  $options    temperature, max_tokens, etc.
     * @return array {success, content, usage, error}
     */
    public function chat(array $messages, array $options = []): array {
        if (empty($this->apiKey) || $this->apiKey === 'your-api-key-here') {
            return $this->chatLocalSimulation($messages, $options);
        }

        switch ($this->provider) {
            case 'anthropic':
                return $this->chatAnthropic($messages, $options);
            default:
                return $this->chatOpenAICompatible($messages, $options);
        }
    }

    /**
     * Local Simulation Mode - Handles requests locally when no API key is set
     */
    private function chatLocalSimulation(array $messages, array $options): array {
        $systemContent = '';
        $userQuery = '';
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemContent = $msg['content'];
            } elseif ($msg['role'] === 'user') {
                $userQuery = $msg['content'];
            }
        }

        // Parse context from system instructions
        $hasContext = false;
        $contextSection = '';
        if (preg_match('/=== KNOWLEDGE BASE ===(.*?)=== END OF KNOWLEDGE BASE ===/s', $systemContent, $matches)) {
            $contextSection = trim($matches[1]);
            $hasContext = !empty($contextSection);
        }

        $reply = '';
        if ($hasContext) {
            $reply .= "### Local Simulation Mode\n";
            $reply .= "I am running in **offline simulation mode** because no external AI API key is configured. Here is the matching information retrieved from your local database for your query: *\"" . htmlspecialchars($userQuery, ENT_QUOTES, 'UTF-8') . "\"*:\n\n";
            
            // Clean up source formatting for user display
            $sources = explode('--- Source ', $contextSection);
            foreach ($sources as $source) {
                if (empty(trim($source))) continue;
                $lines = explode("\n", trim($source), 2);
                $header = trim($lines[0] ?? '');
                $body = trim($lines[1] ?? '');
                
                // Clean header trailing dashes
                $header = preg_replace('/^(\d+:\s*)?/i', '', $header);
                $header = rtrim($header, ' -');
                
                $reply .= "#### " . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . "\n";
                $reply .= "> " . str_replace("\n", "\n> ", htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . "\n\n";
            }
            
            $reply .= "---\n";
            $reply .= "*Configure your AI connection (OpenAI, Anthropic, or OpenRouter) in **Admin > Bot Settings** to enable full generative AI responses.*";
        } else {
            $reply .= "### Local Simulation Mode\n";
            $reply .= "I am running in **offline simulation mode** because no external AI API key is configured.\n\n";
            $reply .= "I searched the local Knowledge Base but did not find any matching documents or manual entries for: *\"" . htmlspecialchars($userQuery, ENT_QUOTES, 'UTF-8') . "\"*.\n\n";
            $reply .= "#### What you can do:\n";
            $reply .= "1. Add matching articles in **Admin > Knowledge Base**.\n";
            $reply .= "2. Upload relevant files (PDF, TXT, DOCX, CSV) in **Admin > Documents**.\n";
            $reply .= "3. Set your live OpenAI or Anthropic credentials in **Admin > Bot Settings** to get full generative AI responses.";
        }

        return [
            'success' => true,
            'content' => $reply,
            'usage'   => ['total_tokens' => 0],
            'model'   => 'Local-Simulation',
        ];
    }

    /**
     * OpenAI / OpenRouter / any OpenAI-compatible endpoint
     */
    private function chatOpenAICompatible(array $messages, array $options): array {
        $payload = [
            'model'       => $options['model']      ?? $this->model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens']  ?? 2000,
        ];

        if (isset($options['stream']) && $options['stream']) {
            $payload['stream'] = true;
        }

        $endpoint = $this->apiUrl . '/chat/completions';
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        // OpenRouter specific
        if (strpos($this->apiUrl, 'openrouter') !== false) {
            $headers[] = 'HTTP-Referer: ' . APP_URL;
            $headers[] = 'X-Title: ' . APP_NAME;
        }

        return $this->makeRequest($endpoint, $payload, $headers);
    }

    /**
     * Anthropic Claude API
     */
    private function chatAnthropic(array $messages, array $options): array {
        // Extract system message
        $system   = '';
        $filtered = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $filtered[] = $msg;
            }
        }

        $payload = [
            'model'      => $options['model']      ?? $this->model,
            'messages'   => $filtered,
            'max_tokens' => $options['max_tokens']  ?? 2000,
        ];
        if ($system) $payload['system'] = $system;

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ];

        $result = $this->makeRequest('https://api.anthropic.com/v1/messages', $payload, $headers);

        // Normalize Anthropic response to OpenAI format
        if ($result['success'] && isset($result['raw']['content'][0]['text'])) {
            $result['content'] = $result['raw']['content'][0]['text'];
            $result['usage']   = $result['raw']['usage'] ?? [];
        }

        return $result;
    }

    /**
     * Make HTTP request via cURL
     */
    private function makeRequest(string $url, array $payload, array $headers): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            app_log('error', 'cURL error', ['error' => $curlError, 'url' => $url]);
            return ['success' => false, 'error' => 'Network error: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? $data['error']['msg'] ?? ('HTTP ' . $httpCode);
            app_log('error', 'AI API error', ['code' => $httpCode, 'error' => $errMsg]);

            if ($httpCode === 401) return ['success' => false, 'error' => 'Invalid API key. Please check your configuration.'];
            if ($httpCode === 429) return ['success' => false, 'error' => 'AI API rate limit exceeded. Please try again later.'];
            if ($httpCode === 402) return ['success' => false, 'error' => 'AI API quota exceeded. Please check your billing.'];
            if ($httpCode >= 500) return ['success' => false, 'error' => 'AI service is temporarily unavailable.'];

            return ['success' => false, 'error' => 'AI API error: ' . $errMsg];
        }

        // Extract content for OpenAI-compatible
        $content = $data['choices'][0]['message']['content'] ?? null;
        if ($content === null && !isset($data['content'])) {
            return ['success' => false, 'error' => 'Unexpected response format from AI API.'];
        }

        return [
            'success' => true,
            'content' => $content ?? '',
            'usage'   => $data['usage'] ?? [],
            'model'   => $data['model'] ?? $this->model,
            'raw'     => $data,
        ];
    }

    /**
     * Get embeddings for text (for future vector search support)
     */
    public function getEmbedding(string $text): array {
        if ($this->provider === 'anthropic') {
            return ['success' => false, 'error' => 'Embeddings not supported for Anthropic provider.'];
        }

        $payload = [
            'model' => 'text-embedding-3-small',
            'input' => substr($text, 0, 8000),
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $result = $this->makeRequest($this->apiUrl . '/embeddings', $payload, $headers);
        if (!$result['success']) return $result;

        return [
            'success'   => true,
            'embedding' => $result['raw']['data'][0]['embedding'] ?? [],
        ];
    }

    public function getProvider(): string { return $this->provider; }
    public function getModel(): string    { return $this->model; }
}
