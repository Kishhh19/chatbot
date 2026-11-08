/* AI Chatbot Platform - Main Application JS */
'use strict';

// ── State ──────────────────────────────────────────────
const state = {
  currentConvId: null,
  conversations: [],
  isLoading: false,
  renameTargetId: null,
};

// ── Init ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadConversations();
  setupInput();
  setupSearch();
  setupMarkdown();
});

function setupMarkdown() {
  if (window.marked) {
    marked.setOptions({
      breaks: true,
      gfm: true,
      sanitize: false,
    });
  }
}

// ── Sidebar ────────────────────────────────────────────
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
  document.getElementById('close-sidebar-btn').style.display = 'flex';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}
function toggleUserMenu() {
  document.getElementById('user-menu').classList.toggle('hidden');
}

// ── Conversations ──────────────────────────────────────
async function loadConversations(search = '') {
  try {
    const url = `${APP.apiBase}conversations.php${search ? '?search=' + encodeURIComponent(search) : ''}`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) return;
    state.conversations = data.conversations;
    renderConversationList(data.conversations);
  } catch (e) {
    console.error('Failed to load conversations', e);
  }
}

function renderConversationList(convs) {
  const el = document.getElementById('conversations-list');
  if (!convs.length) {
    el.innerHTML = '<div style="padding:12px 8px;font-size:13px;color:var(--text-muted)">No conversations yet.</div>';
    return;
  }
  el.innerHTML = convs.map(c => `
    <div class="sidebar-item ${c.id == state.currentConvId ? 'active' : ''}"
         id="conv-${c.id}" onclick="openConversation(${c.id})">
      <span class="nav-icon" style="width:16px;text-align:center;font-size:12px">&#9632;</span>
      <span class="item-label">${escHtml(c.title)}</span>
      <span class="item-actions">
        <button class="btn-icon" style="padding:3px 5px;font-size:11px" onclick="startRename(event,${c.id})" title="Rename">Rename</button>
        <button class="btn-icon" style="padding:3px 5px;font-size:11px;color:var(--danger)" onclick="deleteConv(event,${c.id})" title="Delete">Delete</button>
      </span>
    </div>`).join('');
}

async function openConversation(id) {
  state.currentConvId = id;
  closeSidebar();
  highlightConv(id);

  try {
    const res = await fetch(`${APP.apiBase}conversations.php?id=${id}`);
    const data = await res.json();
    if (!data.success) { showToast('Failed to load conversation', 'error'); return; }

    document.getElementById('chat-header-title').textContent = data.conversation.title;
    renderMessages(data.messages);
  } catch (e) {
    showToast('Error loading conversation', 'error');
  }
}

function highlightConv(id) {
  document.querySelectorAll('.sidebar-item[id^="conv-"]').forEach(el => {
    el.classList.toggle('active', el.id === `conv-${id}`);
  });
}

async function newChat() {
  try {
    const res = await fetch(`${APP.apiBase}conversations.php?action=create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: 'New Conversation' }),
    });
    const data = await res.json();
    if (!data.success) { showToast('Failed to create conversation', 'error'); return; }

    state.currentConvId = data.conversation.id;
    state.conversations.unshift(data.conversation);
    renderConversationList(state.conversations);
    highlightConv(data.conversation.id);

    document.getElementById('chat-header-title').textContent = 'New Conversation';
    document.getElementById('messages-area').innerHTML = '';
    document.getElementById('welcome-screen') && showWelcome();
    closeSidebar();
  } catch (e) {
    showToast('Network error', 'error');
  }
}

function showWelcome() {
  const area = document.getElementById('messages-area');
  area.innerHTML = document.querySelector('.welcome-screen')?.outerHTML || '';
}

async function deleteConv(e, id) {
  e.stopPropagation();
  if (!confirm('Delete this conversation? This cannot be undone.')) return;
  try {
    const res = await fetch(`${APP.apiBase}conversations.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      state.conversations = state.conversations.filter(c => c.id != id);
      renderConversationList(state.conversations);
      if (state.currentConvId == id) {
        state.currentConvId = null;
        document.getElementById('messages-area').innerHTML = '';
        document.getElementById('chat-header-title').textContent = 'New Conversation';
      }
      showToast('Conversation deleted');
    }
  } catch (e) { showToast('Failed to delete', 'error'); }
}

function startRename(e, id) {
  e.stopPropagation();
  state.renameTargetId = id;
  document.getElementById('rename-input').value = state.conversations.find(c => c.id == id)?.title || '';
  openModal('rename-modal');
  setTimeout(() => document.getElementById('rename-input').focus(), 100);
}

async function confirmRename() {
  const title = document.getElementById('rename-input').value.trim();
  if (!title || !state.renameTargetId) return;
  try {
    const res = await fetch(`${APP.apiBase}conversations.php?id=${state.renameTargetId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title }),
    });
    const data = await res.json();
    if (data.success) {
      const conv = state.conversations.find(c => c.id == state.renameTargetId);
      if (conv) conv.title = title;
      renderConversationList(state.conversations);
      if (state.currentConvId == state.renameTargetId) {
        document.getElementById('chat-header-title').textContent = title;
      }
      closeModal('rename-modal');
      showToast('Renamed successfully');
    }
  } catch (e) { showToast('Rename failed', 'error'); }
}

// ── Messages ───────────────────────────────────────────
function renderMessages(messages) {
  const area = document.getElementById('messages-area');
  area.innerHTML = '';
  if (!messages.length) return;
  messages.forEach(msg => appendMessage(msg.role, msg.content, msg.sources, msg.created_at, false));
  scrollToBottom();
}

function appendMessage(role, content, sources, timestamp, animate = true) {
  const area = document.getElementById('messages-area');

  // Remove welcome screen if present
  const ws = area.querySelector('.welcome-screen');
  if (ws) ws.remove();

  const div = document.createElement('div');
  div.className = `message ${role}${animate ? '' : ''}`;

  const initial = role === 'user' ? (APP.user.name[0] || 'U').toUpperCase() : 'AI';
  const bubbleContent = role === 'user' ? escHtml(content).replace(/\n/g, '<br>') : renderMarkdown(content);
  const time = timestamp ? formatTime(timestamp) : formatTime(new Date().toISOString());

  let sourcesHtml = '';
  if (sources && sources.length && role === 'assistant') {
    const items = sources.map(s =>
      `<div class="source-item">${escHtml(s.title)}${s.category ? ` <span class="badge badge-gray">${escHtml(s.category)}</span>` : ''}</div>`
    ).join('');
    sourcesHtml = `<div class="sources-block"><div class="sources-title">Sources</div>${items}</div>`;
  }

  let avatarHtml = '';
  if (role === 'user') {
    avatarHtml = `<div class="message-avatar">${initial}</div>`;
  } else {
    avatarHtml = `
      <div class="message-avatar" style="background:linear-gradient(135deg,var(--accent),#8b5cf6)">
        <svg width="14" height="14" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
      </div>`;
  }

  div.innerHTML = `
    ${avatarHtml}
    <div class="message-body">
      <div class="message-bubble">${bubbleContent}</div>
      ${sourcesHtml}
      <div class="message-meta">
        <span>${time}</span>
        <div class="message-actions">
          <button class="btn-icon" style="font-size:12px" onclick="copyMsg(this)" title="Copy">Copy</button>
          ${role === 'assistant' ? `<button class="btn-icon" style="font-size:12px" onclick="regenerate()" title="Regenerate">Regenerate</button>` : ''}
        </div>
      </div>
    </div>`;

  area.appendChild(div);
  if (animate) scrollToBottom();
}

function appendTyping() {
  const area = document.getElementById('messages-area');
  const div = document.createElement('div');
  div.className = 'message assistant';
  div.id = 'typing-indicator';
  div.innerHTML = `
    <div class="message-avatar" style="background:linear-gradient(135deg,var(--accent),#8b5cf6)">
      <svg width="14" height="14" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
    </div>
    <div class="message-body">
      <div class="typing-indicator">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    </div>`;
  area.appendChild(div);
  scrollToBottom();
}

// ── Send message ───────────────────────────────────────
async function sendMessage(overrideMsg) {
  if (state.isLoading) return;

  const input = document.getElementById('message-input');
  const msg = (overrideMsg || input.value).trim();
  if (!msg) return;

  // Create conversation if needed
  if (!state.currentConvId) {
    await newChat();
    if (!state.currentConvId) return;
  }

  input.value = '';
  autoResize(input);
  setInputState(true);

  appendMessage('user', msg, null, null, true);
  appendTyping();

  try {
    const res = await fetch(`${APP.apiBase}chat.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, conversation_id: state.currentConvId }),
    });
    const data = await res.json();
    document.getElementById('typing-indicator')?.remove();

    if (data.success) {
      appendMessage('assistant', data.content, data.sources || [], null, true);
      loadConversations();
    } else {
      showErrorMessage(data.error || 'Failed to get a response.');
    }
  } catch (e) {
    document.getElementById('typing-indicator')?.remove();
    showErrorMessage('Network error. Please check your connection.');
  } finally {
    setInputState(false);
    input.focus();
  }
}

function sendSuggestion(text) {
  document.getElementById('message-input').value = text;
  sendMessage();
}

async function regenerate() {
  if (!state.currentConvId || state.isLoading) return;
  // Get last user message
  const msgs = document.querySelectorAll('.message.user .message-bubble');
  if (!msgs.length) return;
  const lastMsg = msgs[msgs.length - 1].textContent.trim();

  // Remove last assistant message from UI
  const assistantMsgs = document.querySelectorAll('.message.assistant');
  if (assistantMsgs.length) assistantMsgs[assistantMsgs.length - 1].remove();

  await sendMessage(lastMsg);
}

function showErrorMessage(msg) {
  const area = document.getElementById('messages-area');
  const div = document.createElement('div');
  div.innerHTML = `<div class="alert alert-error" style="margin:0 0 8px">${escHtml(msg)}</div>`;
  area.appendChild(div);
  scrollToBottom();
}

function clearConversation() {
  if (!state.currentConvId) return;
  if (!confirm('Clear all messages? This will delete the conversation.')) return;
  deleteConv({ stopPropagation: () => {} }, state.currentConvId);
}

// ── Input setup ────────────────────────────────────────
function setupInput() {
  const input = document.getElementById('message-input');
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
  input.addEventListener('input', () => autoResize(input));
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 200) + 'px';
}

function setInputState(loading) {
  state.isLoading = loading;
  document.getElementById('message-input').disabled = loading;
  document.getElementById('send-btn').disabled = loading;
}

// ── Search ─────────────────────────────────────────────
function setupSearch() {
  let debounceTimer;
  document.getElementById('conv-search').addEventListener('input', e => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadConversations(e.target.value), 300);
  });
}

// ── Auth ───────────────────────────────────────────────
async function logout() {
  try {
    await fetch('api/auth.php?action=logout', { method: 'POST' });
  } finally {
    window.location.href = 'login.php';
  }
}

// ── Modals ─────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ── Toasts ─────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<span>${escHtml(msg)}</span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Utilities ──────────────────────────────────────────
function scrollToBottom() {
  const area = document.getElementById('messages-area');
  requestAnimationFrame(() => { area.scrollTop = area.scrollHeight; });
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function renderMarkdown(text) {
  if (window.marked) {
    try {
      const template = document.createElement('template');
      template.innerHTML = marked.parse(escHtml(String(text || '')));
      const allowed = new Set(['P','BR','STRONG','EM','UL','OL','LI','A','CODE','PRE','BLOCKQUOTE','H1','H2','H3','H4','HR','TABLE','THEAD','TBODY','TR','TH','TD']);
      function clean(node) {
        Array.from(node.children).forEach(child => {
          if (!allowed.has(child.tagName)) { child.replaceWith(document.createTextNode(child.textContent || '')); return; }
          const href = child.tagName === 'A' ? child.getAttribute('href') || '' : '';
          Array.from(child.attributes).forEach(attribute => child.removeAttribute(attribute.name));
          if (child.tagName === 'A') {
            if (/^(https?:\/\/|tel:\+)/i.test(href)) { child.setAttribute('href', href); child.setAttribute('rel', 'noopener noreferrer'); child.setAttribute('target', '_blank'); }
          }
          clean(child);
        });
      }
      clean(template.content);
      return template.innerHTML;
    } catch (e) {}
  }
  return escHtml(String(text || '')).replace(/\n/g, '<br>');
}

function formatTime(iso) {
  try {
    const d = new Date(iso);
    if (isNaN(d)) return '';
    const now = new Date();
    const diff = now - d;
    if (diff < 60000) return 'just now';
    if (diff < 3600000) return Math.floor(diff/60000) + 'm ago';
    if (diff < 86400000) return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    return d.toLocaleDateString([], {month:'short',day:'numeric'});
  } catch { return ''; }
}

async function copyMsg(btn) {
  const bubble = btn.closest('.message-body').querySelector('.message-bubble');
  const text = bubble?.innerText || '';
  try {
    await navigator.clipboard.writeText(text);
    btn.textContent = 'Copied';
    setTimeout(() => btn.textContent = 'Copy', 1500);
  } catch {}
}
