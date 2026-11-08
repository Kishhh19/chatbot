<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/sabino.php';
$staticBuild = defined('SABINO_STATIC_BUILD') && SABINO_STATIC_BUILD;
if ($staticBuild) {
    $user = null;
} else {
    session_init();
    apply_security_headers();
    $user = get_current_user_data();
}
$shop = sabino_data();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#201b17">
<meta name="description" content="Ask Sabinox about Sabino café in Angeles City: location, contact details, reported hours, and a sample menu.">
<title>Sabinox · Your Sabino café helper</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sabino.css">
</head>
<body>
<div class="site-shell">
  <aside class="rail" aria-label="Sabino information">
    <a class="brand" href="<?= $staticBuild ? 'index.html' : 'index.php' ?>" aria-label="Sabino home"><span class="brand-mark" aria-hidden="true">S<span>✦</span></span><span><strong>Sabino</strong><small>COFFEE & CONVERSATION</small></span></a>
    <div class="rail-content">
      <p class="eyebrow">YOUR CAFÉ COMPANION</p><h1>Good coffee<br><em>starts here.</em></h1>
      <p class="rail-intro">Meet Sabinox, your friendly guide to Sabino. Find your way, explore coffee ideas, and get the details before you drop by.</p>
      <div class="info-card"><span class="info-icon" aria-hidden="true">⌖</span><div><span class="info-label">FIND US</span><strong><?= e($shop['address']) ?></strong><a href="<?= e($shop['maps']) ?>" target="_blank" rel="noopener noreferrer">Get directions ↗</a></div></div>
      <div class="info-card"><span class="info-icon" aria-hidden="true">◷</span><div><span class="info-label">PLAN YOUR VISIT</span><strong>Monday–Saturday</strong><p>Opening is reported around 7 or 9 AM depending on the day. Sunday closed. Please confirm before visiting.</p></div></div>
      <div class="info-card"><span class="info-icon" aria-hidden="true">✆</span><div><span class="info-label">SAY HELLO</span><strong><?= e($shop['phone']) ?></strong><a href="<?= e($shop['phone_link']) ?>">Call Sabino ↗</a></div></div>
    </div>
    <div class="rail-footer"><a href="<?= e($shop['instagram']) ?>" target="_blank" rel="noopener noreferrer">Instagram ↗</a><span>Angeles City, Pampanga</span></div>
  </aside>
  <main class="main-panel">
    <header class="topbar"><div class="assistant-identity"><span class="assistant-avatar" aria-hidden="true">S<span>✦</span></span><span><strong>Sabinox</strong><small><span class="status-dot"></span> Sabino's café helper</small></span></div><div class="top-actions"><button class="text-button" id="new-chat" type="button">New chat</button><?php if (!$user && !$staticBuild): ?><a class="header-link" href="login.php">Staff sign in</a><?php elseif ($user && $user['role'] === 'admin'): ?><a class="header-link" href="admin/dashboard.php">Admin</a><?php endif; ?></div></header>
    <div class="chat-scroll" id="chat-scroll"><div class="chat-inner">
      <section class="intro" id="intro" aria-label="Welcome"><div class="intro-decoration" aria-hidden="true">✦</div><p class="eyebrow">A WARM WELCOME FROM SABINO</p><h2>What can I help you<br><em>discover today?</em></h2><p>I’m Sabinox. Ask about visiting Sabino, get a coffee suggestion, or take a look at our sample menu while the official one is on its way.</p>
        <div class="quick-actions" id="quick-actions"><button type="button" data-question="Sample menu"><span aria-hidden="true">☕</span> Explore sample menu <span aria-hidden="true">↗</span></button><button type="button" data-question="Opening hours"><span aria-hidden="true">◷</span> Opening hours <span aria-hidden="true">↗</span></button><button type="button" data-question="Find Sabino"><span aria-hidden="true">⌖</span> Find the café <span aria-hidden="true">↗</span></button><button type="button" data-question="Recommend a drink"><span aria-hidden="true">✦</span> Pick me a drink <span aria-hidden="true">↗</span></button></div>
      </section><div id="messages" class="messages" aria-live="polite" aria-relevant="additions"></div><div id="followups" class="followups" aria-label="Suggested questions"></div>
    </div></div>
    <div class="composer-wrap"><form id="chat-form" class="composer"><label class="sr-only" for="message-input">Message Sabinox</label><textarea id="message-input" rows="1" maxlength="1000" placeholder="Ask Sabinox anything about Sabino..." aria-label="Message Sabinox"></textarea><button id="send-button" type="submit" aria-label="Send message"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button></form><p class="composer-note">Sample menu and prices are estimates, not Sabino’s official offerings. <a href="<?= e($shop['phone_link']) ?>">Confirm with the shop</a>.</p></div>
  </main>
  <aside class="menu-panel" aria-label="Sample menu preview"><div class="menu-head"><span class="eyebrow">A LITTLE INSPIRATION</span><h2>On the<br><em>sample menu.</em></h2><p>Ideas to get you started. Sabino’s official menu and prices are coming later.</p></div><div class="sample-label">SAMPLE ONLY · PRICES ESTIMATED</div><div class="menu-list"><?php foreach (array_slice($shop['sample_menu'], 0, 6) as $item): ?><div class="menu-item"><span><strong><?= e($item['name']) ?></strong><small><?= e($item['note']) ?></small></span><b>₱<?= (int)$item['price'] ?></b></div><?php endforeach; ?></div><button class="menu-cta" type="button" data-question="Show the full sample menu">See full sample menu ↗</button><p class="menu-footnote">Need the real menu? <a href="<?= e($shop['phone_link']) ?>">Call Sabino</a> for current items and prices.</p></aside>
</div>
<?php if ($staticBuild): ?><script>window.SABINO_DATA = <?= json_encode($shop, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><?php endif; ?>
<script src="assets/js/sabino.js" defer></script>
</body></html>
