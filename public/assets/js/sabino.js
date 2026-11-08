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
const shop = window.SABINO_DATA;
const storageKey = 'sabinox-chat';

function readHistory() {
  try { return JSON.parse(sessionStorage.getItem(storageKey) || '[]'); } catch { return []; }
}
function writeHistory(history) {
  try { sessionStorage.setItem(storageKey, JSON.stringify(history.slice(-40))); } catch {}
}
function menuText(type = null) {
  const items = shop.sample_menu.filter(item => !type || item.type === type);
  return '**Sample menu and estimated prices only** — these are Philippine café examples, not confirmed Sabino offerings or prices.\n\n'
    + items.map(item => `- ${item.name} — ₱${item.price} · ${item.note}`).join('\n')
    + `\n\nPlease call **${shop.phone}** for Sabino’s current menu, availability, and exact prices.`;
}
function answer(question) {
  const q = question.trim().toLocaleLowerCase();
  let content;
  let suggestions = ['Sample menu', 'Opening hours', 'Find Sabino'];
  if (/\b(where|address|location|located|directions|map|find|saan|nasaan|pupunta|salapungan)\b/u.test(q)) {
    content = `Sabino is at **${shop.address}**. [Open directions](${shop.maps}).`;
    suggestions = ['Opening hours', 'Contact Sabino', 'Sample menu'];
  } else if (/\b(hours?|opening|opens?|closing|closes?|closed|today|tomorrow|sunday|monday|saturday|oras|bukas|sarado|schedule)\b/u.test(q)) {
    content = `**Reported hours:** ${shop.hours} [Call to confirm](${shop.phone_link}).`;
    suggestions = ['Find Sabino', 'Contact Sabino', 'Sample menu'];
  } else if (/\b(phone|number|contact|call|text|message|instagram|ig|social|tawag|telepono)\b/u.test(q)) {
    content = `You can [call or text Sabino](${shop.phone_link}) at **${shop.phone}**, or visit [their Instagram](${shop.instagram}).`;
    suggestions = ['Find Sabino', 'Opening hours', 'Sample menu'];
  } else if (/\b(orders?|deliver(?:y|ies|ed)?|pickup|takeout|reserve|reservations?|book|payments?|pay|gcash|wifi|parking|pets?|allerg\w*|ingredients?|vegan|decaf)\b/u.test(q)) {
    content = `I don’t have confirmed Sabino details for that yet. Please [contact the shop](${shop.phone_link}) at **${shop.phone}** so the team can give you an accurate answer. I can help with the location, reported hours, and a sample menu.`;
    suggestions = ['Contact Sabino', 'Find Sabino', 'Sample menu'];
  } else if (/\b(recommend|suggest|best|sweet|strong|budget|under|below|less than|cheap|favorite|favourite|non.coffee|without coffee|hindi kape|matapang|matamis)\b/u.test(q)) {
    const budgetMatch = q.match(/(?:under|below|less than|budget(?: of)?|up to)\s*(?:₱|php\s*)?(\d{2,4})/u);
    if (budgetMatch) {
      const budget = Number(budgetMatch[1]);
      const choices = shop.sample_menu.filter(item => item.type !== 'Pastry' && item.price <= budget).map(item => `${item.name} (sample ₱${item.price})`);
      content = choices.length
        ? `For a sample drink budget of ₱${budget}, consider ${choices.join(', ')}. These are estimates only, not confirmed Sabino prices or offerings.`
        : `The sample drink list has no option within ₱${budget}. Please ask Sabino about its current menu and prices.`;
    } else if (/\b(non.coffee|without coffee|hindi kape|matcha|chocolate)\b/u.test(q)) {
      content = 'If you want a drink without coffee, the **sample menu** has Matcha Latte (₱155) and Hot Chocolate (₱125). These are suggestions only; Sabino’s actual selection and prices are unconfirmed.';
    } else if (/\b(sweet|matamis|chocolate)\b/u.test(q)) {
      content = 'For a sweeter coffee idea, try a **Spanish Latte (sample ₱155)** or **Mocha (sample ₱155)**. Please check with Sabino for what they currently serve.';
    } else if (/\b(strong|matapang|black)\b/u.test(q)) {
      content = 'For a bold coffee idea, consider an **Americano (sample ₱100)** or **Cold Brew (sample ₱145)**. Actual Sabino availability and prices may differ.';
    } else {
      content = 'A simple starting point: **Americano (sample ₱100)** for black coffee, **Café Latte (sample ₱140)** for something creamy, or **Matcha Latte (sample ₱155)** without coffee. These are Philippine café examples, not Sabino’s confirmed menu.';
    }
    suggestions = ['Sample menu', 'Contact Sabino', 'Opening hours'];
  } else if (/\b(menu|prices?|cost|much|drinks?|coffee|latte|cappuccino|americano|mocha|pastries|pastry|croissant|cookie|food|snack|matcha|presyo|magkano|kape)\b/u.test(q)) {
    const type = /\b(pastry|pastries|croissant|cookie|food|snack)\b/u.test(q) ? 'Pastry' : (/\b(matcha|non.coffee|hot chocolate)\b/u.test(q) ? 'Non-coffee' : null);
    content = menuText(type);
    suggestions = ['Recommend a drink', 'Contact Sabino', 'Find Sabino'];
  } else if (/\b(hello|hi|hey|good morning|good afternoon|kumusta|salamat|thanks|help|faq|sabinox|sabino|who are you)\b/u.test(q)) {
    content = 'Hi! I’m **Sabinox**, Sabino’s café helper. I can share the shop’s address, contact details, reported hours, and a **clearly labeled sample menu** while the official menu is being prepared. What would you like to know?';
  } else {
    content = `I can help with **Sabino’s location, contact details, reported hours, and a sample menu**. I don’t have a confirmed answer to that question yet; for anything specific, [call or text Sabino](${shop.phone_link}) at **${shop.phone}**.`;
  }
  return {content, suggestions};
}

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
    const data = answer(question);
    typing.remove();
    appendMessage('assistant', data.content);
    renderFollowups(data.suggestions || []);
    writeHistory([...readHistory(), {role:'user', content:question}, {role:'assistant', content:data.content}]);
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
document.getElementById('new-chat').addEventListener('click', () => {
  if (busy) return;
  touched = true;
  try { sessionStorage.removeItem(storageKey); } catch {}
  messages.replaceChildren();
  followups.replaceChildren();
  intro.classList.remove('hidden');
  input.focus();
});
if (!touched) readHistory().forEach(message => appendMessage(message.role, message.content));
