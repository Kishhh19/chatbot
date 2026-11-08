'use strict';
const chatForm = document.getElementById('chat-form');
const input = document.getElementById('message-input');
const messages = document.getElementById('messages');
const followups = document.getElementById('followups');
const intro = document.getElementById('intro');
const scrollPane = document.getElementById('chat-scroll');
const sendButton = document.getElementById('send-button');
let busy = false;
let touched = false;

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
}
function formatReply(value) {
  let html = escapeHtml(value);
  html = html.replace(/\[([^\]]+)\]\((https:\/\/[^\s)]+|tel:\+\d+)\)/g, (_match, label, url) => `<a href="${url}"${url.startsWith('https:') ? ' target="_blank" rel="noopener noreferrer"' : ''}>${label}</a>`);
  html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  return html.split(/\n\n+/).map(part => {
    if (part.startsWith('- ')) return `<ul>${part.split('\n').map(line => `<li>${line.replace(/^- /, '')}</li>`).join('')}</ul>`;
    return `<p>${part.replace(/\n/g, '<br>')}</p>`;
  }).join('');
}
function appendMessage(role, content, temporary = false) {
  intro.classList.add('hidden');
  const article = document.createElement('article');
  article.className = `message ${role}${temporary ? ' typing' : ''}`;
  const bubble = role === 'user' ? escapeHtml(content) : formatReply(content);
  article.innerHTML = `<div class="msg-avatar" aria-hidden="true">${role === 'user' ? 'Y' : 'S'}</div><div class="msg-body"><div class="msg-name">${role === 'user' ? 'You' : 'Sabinox'}</div><div class="msg-bubble">${bubble}</div></div>`;
  messages.appendChild(article);
  scrollPane.scrollTop = scrollPane.scrollHeight;
  return article;
}
function renderFollowups(items) {
  followups.replaceChildren();
  items.forEach(item => {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = item + ' ↗';
    button.addEventListener('click', () => sendMessage(item));
    followups.appendChild(button);
  });
}
function setBusy(value) {
  busy = value;
  input.disabled = value;
  sendButton.disabled = value;
}
async function sendMessage(override) {
  const question = (override ?? input.value).trim();
  if (!question || busy) return;
  touched = true;
  setBusy(true);
  input.value = '';
  input.style.height = 'auto';
  followups.replaceChildren();
  appendMessage('user', question);
  const typing = appendMessage('assistant', 'Thinking…', true);
  try {
    const response = await fetch('api/sabino.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({message:question})});
    const data = await response.json();
    typing.remove();
    if (!response.ok || !data.success) throw new Error(data.error || 'Something went wrong. Please try again.');
    appendMessage('assistant', data.content);
    renderFollowups(data.suggestions || []);
  } catch (error) {
    typing.remove();
    const note = document.createElement('p');
    note.className = 'error-note';
    note.textContent = error.message || 'Unable to connect. Please try again.';
    messages.appendChild(note);
  } finally {
    setBusy(false);
    input.focus();
    scrollPane.scrollTop = scrollPane.scrollHeight;
  }
}
chatForm.addEventListener('submit', event => {event.preventDefault(); sendMessage();});
input.addEventListener('keydown', event => {if (event.key === 'Enter' && !event.shiftKey) {event.preventDefault(); sendMessage();}});
input.addEventListener('input', () => {input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 135) + 'px';});
document.querySelectorAll('[data-question]').forEach(button => button.addEventListener('click', () => sendMessage(button.dataset.question)));
document.getElementById('new-chat').addEventListener('click', async () => {
  if (busy) return;
  touched = true;
  try {
    const response = await fetch('api/sabino.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'reset'})});
    if (!response.ok) throw new Error();
    messages.replaceChildren();
    followups.replaceChildren();
    intro.classList.remove('hidden');
    input.focus();
  } catch {alert('Could not start a new chat. Please try again.');}
});
fetch('api/sabino.php').then(response => response.json()).then(data => {
  if (!data.success || touched) return;
  (data.messages || []).forEach(message => appendMessage(message.role, message.content));
}).catch(() => {});
