<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/sabino.php';

session_init();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(['success' => true, 'messages' => $_SESSION['sabino_chat'] ?? []]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) json_error('Invalid request', 400);
if (($input['action'] ?? '') === 'reset') {
    $_SESSION['sabino_chat'] = [];
    json_response(['success' => true, 'messages' => []]);
}
$message = $input['message'] ?? null;
if (!is_string($message)) json_error('Please enter a valid message.', 400);
$message = trim($message);
if ($message === '' || mb_strlen($message) > 1000) json_error('Please enter a message under 1,000 characters.', 400);

$now = time();
$_SESSION['sabino_requests'] = array_values(array_filter($_SESSION['sabino_requests'] ?? [], fn($time) => $time > $now - 60));
if (count($_SESSION['sabino_requests']) >= 20) json_error('Please wait a moment before sending another message.', 429);
$_SESSION['sabino_requests'][] = $now;

$answer = sabino_reply($message);
$history = $_SESSION['sabino_chat'] ?? [];
$history[] = ['role' => 'user', 'content' => $message];
$history[] = ['role' => 'assistant', 'content' => $answer['content']];
$_SESSION['sabino_chat'] = array_slice($history, -40);
json_response(['success' => true, 'content' => $answer['content'], 'suggestions' => $answer['suggestions']]);
