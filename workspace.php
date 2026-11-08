<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

session_init();
$user = require_auth(true);
apply_security_headers();

// Load bot settings
try {
    $rows = db_fetch_all("SELECT setting_key, setting_value FROM chatbot_settings");
    $botSettings = [];
    foreach ($rows as $row) $botSettings[$row['setting_key']] = $row['setting_value'];
} catch (Exception $e) {
    $botSettings = [];
}

$botName       = 'Sabinox';
$welcomeMsg    = 'I can help with Sabino’s location, reported hours, contact details, and a sample menu.';
$isAdmin       = $user['role'] === 'admin';
$displayName   = e($user['display_name']);
$userInitial   = mb_strtoupper(mb_substr($user['display_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $botName ?> · Sabino</title>
<meta name="description" content="Chat with Sabinox, your Sabino café helper.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <!-- Mobile overlay -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
      </div>
      <span class="sidebar-brand"><?= $botName ?></span>
      <button class="btn-icon" onclick="closeSidebar()" style="margin-left:auto;display:none" id="close-sidebar-btn" title="Close">✕</button>
    </div>

    <div class="sidebar-body">
      <!-- New Chat -->
      <div class="sidebar-section">
        <button class="btn btn-primary w-full" id="new-chat-btn" onclick="newChat()">
          <span>New Chat</span>
        </button>
      </div>

      <!-- Search conversations -->
      <div class="sidebar-section">
        <div class="search-box">
          <span class="search-icon" style="font-size:13px">&#128269;</span>
          <input class="form-control" type="search" id="conv-search" placeholder="Search conversations…">
        </div>
      </div>

      <!-- Conversation list -->
      <div class="sidebar-section">
        <div class="sidebar-section-title">Conversations</div>
        <div id="conversations-list">
          <div class="empty-state" style="padding:20px;font-size:13px">Loading…</div>
        </div>
      </div>
    </div>

    <div class="sidebar-footer">
      <?php if ($isAdmin): ?>
      <a href="admin/dashboard.php" class="sidebar-item" style="margin-bottom:4px">
        <span class="nav-icon" style="width:20px;text-align:center">&#9632;</span><span class="item-label">Admin Dashboard</span>
      </a>
      <?php endif; ?>
      <div class="sidebar-user" id="user-menu-btn" onclick="toggleUserMenu()">
        <div class="avatar"><?= $userInitial ?></div>
        <div class="user-info">
          <div class="user-name"><?= $displayName ?></div>
          <div class="user-role"><?= e($user['role']) ?></div>
        </div>
        <span style="color:var(--text-muted)">⋯</span>
      </div>
      <div id="user-menu" class="hidden" style="background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-md);margin-top:6px;padding:6px">
        <button class="sidebar-item" onclick="logout()" style="color:var(--danger)">Sign Out</button>
      </div>
    </div>
  </aside>

  <!-- Main chat area -->
  <main class="main-content">
    <div class="chat-header">
      <div class="flex items-center gap-2">
        <button class="btn-icon" id="sidebar-toggle" onclick="openSidebar()" title="Menu">☰</button>
        <span class="chat-title" id="chat-header-title">New Conversation</span>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-ghost btn-sm" id="clear-chat-btn" onclick="clearConversation()" title="Clear chat"><span class="btn-text">Clear Chat</span></button>
      </div>
    </div>

    <div class="messages-area" id="messages-area">
      <!-- Welcome screen shown when no conversation selected -->
      <div class="welcome-screen" id="welcome-screen">
        <div class="welcome-icon">
          <svg width="36" height="36" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
        </div>
        <h1 class="welcome-title">Hi, I'm <?= $botName ?></h1>
        <p class="welcome-desc"><?= $welcomeMsg ?></p>
        <div class="suggestion-chips" id="suggestion-chips">
          <div class="chip" onclick="sendSuggestion('Where is Sabino?')">Where is Sabino?</div>
          <div class="chip" onclick="sendSuggestion('What are Sabino opening hours?')">Opening hours</div>
          <div class="chip" onclick="sendSuggestion('Show the sample menu and prices')">Sample menu</div>
        </div>
      </div>
    </div>

    <div class="chat-input-area">
      <div class="input-container">
        <textarea id="message-input" placeholder="Ask me anything…" rows="1" maxlength="10000"></textarea>
        <button class="send-btn" id="send-btn" onclick="sendMessage()" title="Send (Enter)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
      <div class="input-hint">Enter to send · Shift+Enter for new line</div>
    </div>
  </main>
</div>

<!-- Rename modal -->
<div class="modal-overlay" id="rename-modal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title">Rename Conversation</span>
      <button class="btn-icon" onclick="closeModal('rename-modal')">✕</button>
    </div>
    <div class="form-group">
      <input class="form-control" id="rename-input" placeholder="Conversation title…" maxlength="255">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('rename-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmRename()">Save</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script src="assets/js/marked.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
// Pass PHP data to JS
window.APP = {
  user: <?= json_encode(['id'=>$user['id'],'name'=>$user['display_name'],'role'=>$user['role']]) ?>,
  botName: <?= json_encode($botName) ?>,
  apiBase: 'api/',
};
</script>
</body>
</html>
