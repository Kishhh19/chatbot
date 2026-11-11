<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/sabino.php';
$staticBuild = defined('SABINO_STATIC_BUILD') && SABINO_STATIC_BUILD;
if ($staticBuild) { $user = null; } else { session_init(); apply_security_headers(); $user = get_current_user_data(); }
$shop = sabino_data();
$menuGroups = [];
foreach ($shop['menu'] as $item) { $menuGroups[$item['kind']][$item['category']][] = $item; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#142d22">
<meta name="description" content="Meet Sabino, a creative neighborhood coffee shop in Angeles City, Pampanga. Browse the full drinks and food menu, plan your visit, and ask Sabinox for help.">
<title>Sabino · Coffee, community & creative energy</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=League+Spartan:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sabino.css">
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'CafeOrCoffeeShop','name'=>$shop['name'],'address'=>['@type'=>'PostalAddress','streetAddress'=>'693 D. Fajardo Street','addressLocality'=>'Angeles City','addressRegion'=>'Pampanga','addressCountry'=>'PH'],'telephone'=>$shop['phone'],'sameAs'=>[$shop['instagram']]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header" id="top"><div class="nav-wrap">
  <a class="wordmark" href="#top" aria-label="Sabino home"><span class="cactus-mark" aria-hidden="true"><svg viewBox="0 0 64 64"><path d="M32 53V17c0-4 2-7 5-7s5 3 5 7v12c0 3 2 5 5 5s5-2 5-5v-5M32 41H21c-5 0-9-4-9-9v-8c0-3 2-5 5-5s5 2 5 5v5"/></svg></span><span><strong>SABINO</strong><small>ANGELES CITY</small></span></a>
  <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><span></span><span></span></button>
  <nav class="site-nav" aria-label="Main navigation"><a href="#story">Our place</a><a href="#menu">Menu</a><a href="#visit">Visit</a><button class="nav-chat" type="button" data-chat-open>Ask Sabinox <span>↗</span></button></nav>
</div></header>

<main id="main-content">
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy"><p class="kicker"><span></span> FOR THOSE WHO KNOW</p><h1 id="hero-title">Coffee with<br><em>character.</em></h1><p class="hero-lede">A neighborhood coffee stop in Salapungan with a creative pulse, a warm welcome, and a cactus at its heart.</p>
    <div class="hero-actions"><a class="button button-light" href="#menu">Explore the full menu <span>↓</span></a><button class="button button-ghost" type="button" data-chat-open data-question="Recommend a drink">Find my drink <span>✦</span></button></div>
    <div class="hero-details"><span>693 D. Fajardo St.</span><span>Salapungan, Angeles City</span></div>
  </div>
  <div class="hero-art" aria-label="Sabino-inspired coffee poster artwork"><div class="sun-disc"></div><div class="poster poster-main"><p>SABINO</p><svg class="hero-cactus" viewBox="0 0 160 220" aria-hidden="true"><path d="M80 196V54c0-19 11-32 26-32s25 13 25 32v45c0 12 7 20 17 20M80 146H45c-19 0-33-15-33-35V76"/><path d="M148 119V86M12 76V51"/></svg><strong>WHO<br>KNOWS?</strong><small>COFFEE · CULTURE · COMMUNITY</small></div><div class="poster poster-ticket"><span>OPEN LATE</span><b>9—10</b><small>MON—FRI</small></div><div class="poster poster-cup"><div class="cup-icon" aria-hidden="true">☕</div><strong>STAY<br>AWHILE</strong></div><div class="art-sticker">✦<br><span>ANGELES<br>CITY</span></div></div>
</section>

<div class="ticker" aria-hidden="true"><div>COFFEE <span>✦</span> GOOD COMPANY <span>✦</span> CREATIVE ENERGY <span>✦</span> SALAPUNGAN <span>✦</span> COFFEE <span>✦</span> GOOD COMPANY <span>✦</span> CREATIVE ENERGY <span>✦</span> SALAPUNGAN <span>✦</span></div></div>

<section class="story section" id="story">
  <div class="section-label"><span>01</span><p>OUR PLACE</p></div>
  <div class="story-copy"><p class="kicker dark"><span></span> A LITTLE OFFBEAT, ALWAYS WELCOMING</p><h2>More than a cup.<br><em>A place with a pulse.</em></h2><p>Sabino’s identity lives where coffee, art, music, and the local community meet. The cactus mark says it best: distinctive, resilient, and impossible to miss.</p><p>Drop in for your usual, try something new, or make a little room in your day to stay.</p><a class="text-link" href="<?= e($shop['instagram']) ?>" target="_blank" rel="noopener noreferrer">Follow @sabinowhoknows <span>↗</span></a></div>
  <div class="story-grid" aria-hidden="true"><div class="story-card card-orange"><span>THE</span><strong>CACTUS</strong><svg viewBox="0 0 90 120"><path d="M45 108V30c0-11 6-18 14-18s14 7 14 18v25c0 7 4 11 10 11M45 78H26c-11 0-19-9-19-20V38"/></svg></div><div class="story-card card-cream"><span>COFFEE IS</span><strong>BETTER<br>TOGETHER</strong><i>✦</i></div><div class="story-card card-green"><b>693</b><span>D. FAJARDO<br>STREET</span><small>COME THROUGH</small></div></div>
</section>

<section class="menu-section section" id="menu">
  <div class="section-label light"><span>02</span><p>FULL MENU</p></div>
  <div class="menu-heading"><div><p class="kicker light"><span></span> SABINO’S CURRENT MENU</p><h2>Pick your<br><em>kind of comfort.</em></h2></div><div class="menu-note"><strong>Drinks, meals & good company</strong><p>Browse the prices from Sabino’s in-shop menu. Availability may change during the day, so ask the team about sold-out items and specials.</p></div></div>
  <div class="menu-switcher" role="group" aria-label="Choose menu section"><button class="active" type="button" data-menu-filter="Drinks">Drinks <span><?= count(array_filter($shop['menu'], fn($item) => $item['kind'] === 'Drinks')) ?></span></button><button type="button" data-menu-filter="Food">Food <span><?= count(array_filter($shop['menu'], fn($item) => $item['kind'] === 'Food')) ?></span></button></div>
  <div class="menu-catalog"><?php foreach ($menuGroups as $kind => $categories): ?><section class="menu-kind<?= $kind === 'Drinks' ? ' active' : '' ?>" data-menu-kind="<?= e($kind) ?>" aria-label="<?= e($kind) ?> menu"><div class="menu-kind-title"><span><?= e($kind) ?></span><p><?= $kind === 'Drinks' ? 'Coffee, soda, specialty & tea' : 'From starters to full meals' ?></p></div><div class="menu-category-grid"><?php $categoryIndex = 0; foreach ($categories as $category => $items): $categoryIndex++; ?><article class="menu-category"><header><h3><?= e($category) ?></h3><span><?= str_pad((string)$categoryIndex, 2, '0', STR_PAD_LEFT) ?></span></header><div><?php foreach ($items as $item): ?><div class="menu-row"><span><?= e($item['name']) ?></span><strong><?php if (isset($item['sharing_price'])): ?><small>Solo</small> ₱<?= (int)$item['price'] ?> <small>Sharing</small> ₱<?= (int)$item['sharing_price'] ?><?php else: ?>₱<?= (int)$item['price'] ?><?php endif; ?></strong></div><?php endforeach; ?></div></article><?php endforeach; ?></div></section><?php endforeach; ?></div>
  <div class="menu-footer"><p>Not sure what suits you?</p><button type="button" data-chat-open data-question="Recommend a drink based on my taste">Let Sabinox pick your drink <span>↗</span></button></div>
</section>

<section class="visit section" id="visit">
  <div class="section-label"><span>03</span><p>COME SAY HI</p></div><div class="visit-intro"><p class="kicker dark"><span></span> YOUR NEXT COFFEE BREAK</p><h2>Find the cactus.<br><em>Stay for the coffee.</em></h2></div>
  <div class="visit-grid"><article class="visit-card address-card"><span class="card-number">01</span><p>FIND US</p><h3><?= e($shop['address']) ?></h3><a href="<?= e($shop['maps']) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps <span>↗</span></a></article><article class="visit-card hours-card"><span class="card-number">02</span><p>OPENING HOURS</p><div class="hours-list"><?php foreach ($shop['hours_short'] as $hours): ?><div><span><?= e($hours['days']) ?></span><strong><?= e($hours['time']) ?></strong></div><?php endforeach; ?></div><small>Please confirm holiday and special-event hours.</small></article><article class="visit-card contact-card"><span class="card-number">03</span><p>CONTACT</p><h3><?= e($shop['phone']) ?></h3><div><a href="<?= e($shop['phone_link']) ?>">Call or text <span>↗</span></a><a href="<?= e($shop['instagram']) ?>" target="_blank" rel="noopener noreferrer">Instagram <span>↗</span></a></div></article></div>
</section>

<section class="sabinox-banner"><div><span class="mini-face">S<span>✦</span></span><p>MEET SABINOX</p></div><h2>Questions about coffee?<br><em>Ask the cactus.</em></h2><p>Get drink recommendations, compare café favorites, check visiting details, or browse Sabino’s full drinks and food menu.</p><button class="button button-dark" type="button" data-chat-open>Start a conversation <span>↗</span></button></section>
</main>

<footer class="site-footer"><div class="footer-word">SABINO<span>✦</span></div><div class="footer-row"><p>693 D. Fajardo Street, Salapungan<br>Angeles City, Pampanga</p><div><a href="#top">Back to top ↑</a><?php if (!$user && !$staticBuild): ?><a href="login.php">Staff sign in</a><?php elseif ($user && $user['role'] === 'admin'): ?><a href="admin/dashboard.php">Admin</a><?php endif; ?></div><p>© <?= date('Y') ?> Sabino<br>Made for good coffee days.</p></div></footer>

<button class="chat-launcher" id="chat-launcher" type="button" aria-controls="chat-panel" aria-expanded="false"><span class="launcher-mark">S<span>✦</span></span><span class="launcher-copy"><strong>Ask Sabinox</strong><small>How can I help?</small></span><i>↑</i></button>
<section class="chat-panel" id="chat-panel" role="dialog" aria-modal="false" aria-labelledby="chat-title" aria-hidden="true">
  <header class="chat-header"><div class="assistant-identity"><span class="assistant-avatar">S<span>✦</span></span><span><strong id="chat-title">Sabinox</strong><small><i></i> Sabino’s café helper</small></span></div><div><button id="new-chat" type="button">New chat</button><button id="chat-close" type="button" aria-label="Close Sabinox">×</button></div></header>
  <div class="chat-scroll" id="chat-scroll"><div class="chat-inner"><section class="chat-intro" id="intro"><span class="intro-mark">✦</span><h2>Hey there.<br><em>What can I help with?</em></h2><p>Ask about visiting Sabino, coffee choices, or the full menu.</p><div class="quick-actions"><button type="button" data-question="Recommend a drink">Pick me a drink</button><button type="button" data-question="Opening hours">Opening hours</button><button type="button" data-question="Show drinks menu">Drinks menu</button><button type="button" data-question="Show food menu">Food menu</button></div></section><div id="messages" class="messages" aria-live="polite" aria-relevant="additions"></div><div id="followups" class="followups" aria-label="Suggested questions"></div></div></div>
  <div class="composer-wrap"><form id="chat-form" class="composer"><label class="sr-only" for="message-input">Message Sabinox</label><textarea id="message-input" rows="1" maxlength="1000" placeholder="Ask Sabinox about Sabino..."></textarea><button id="send-button" type="submit" aria-label="Send message"><svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button></form><p>Menu prices are current from Sabino’s board. <a href="<?= e($shop['phone_link']) ?>">Check availability.</a></p></div>
</section><div class="chat-backdrop" id="chat-backdrop"></div>

<?php if ($staticBuild): ?><script>window.SABINO_DATA = <?= json_encode($shop, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><?php endif; ?>
<script src="assets/js/sabino.js" defer></script>
</body></html>
