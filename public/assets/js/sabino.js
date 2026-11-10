'use strict';
const chatForm = document.getElementById('chat-form');
const input = document.getElementById('message-input');
const messages = document.getElementById('messages');
const followups = document.getElementById('followups');
const intro = document.getElementById('intro');
const scrollPane = document.getElementById('chat-scroll');
const sendButton = document.getElementById('send-button');
const chatPanel = document.getElementById('chat-panel');
const chatLauncher = document.getElementById('chat-launcher');
const chatBackdrop = document.getElementById('chat-backdrop');
const chatClose = document.getElementById('chat-close');
const navToggle = document.querySelector('.nav-toggle');
const siteNav = document.querySelector('.site-nav');
let busy = false;
let touched = false;
const shop = window.SABINO_DATA;
const storageKey = 'sabinox-chat';

function openChat(question = '') {
  chatPanel.classList.add('open');
  chatBackdrop.classList.add('open');
  chatPanel.setAttribute('aria-hidden', 'false');
  chatLauncher.setAttribute('aria-expanded', 'true');
  document.body.classList.add('chat-open');
  window.setTimeout(() => input.focus(), 180);
  if (question) sendMessage(question);
}
function closeChat() {
  chatPanel.classList.remove('open');
  chatBackdrop.classList.remove('open');
  chatPanel.setAttribute('aria-hidden', 'true');
  chatLauncher.setAttribute('aria-expanded', 'false');
  document.body.classList.remove('chat-open');
  chatLauncher.focus();
}

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
  if (/\b(where|address|location|located|directions|map|find|visit|saan|nasaan|pupunta|salapungan)\b/u.test(q)) {
    content = `Sabino is at **${shop.address}**. [Open directions](${shop.maps}).`;
    suggestions = ['Opening hours', 'Contact Sabino', 'Sample menu'];
  } else if (/\b(hours?|opening|opens?|closing|closes?|closed|today|tomorrow|sunday|monday|saturday|oras|bukas|sarado|schedule)\b/u.test(q)) {
    content = `**Reported hours:** ${shop.hours} [Call to confirm](${shop.phone_link}).`;
    suggestions = ['Find Sabino', 'Contact Sabino', 'Sample menu'];
  } else if (/\b(phone|number|contact|call|text|message|instagram|ig|social|tawag|telepono)\b/u.test(q)) {
    content = `You can [call or text Sabino](${shop.phone_link}) at **${shop.phone}**, or visit [their Instagram](${shop.instagram}).`;
    suggestions = ['Find Sabino', 'Opening hours', 'Sample menu'];
  } else if (/\b(do you know|who is|who's|kilala mo|staff|owner|manager|barista|employee|crew)\b/u.test(q)) {
    content = `I don’t have a verified staff or owner directory for Sabino, so I can’t confirm whether a named person works there. For a staff-related question, please [contact Sabino](${shop.phone_link}) directly. I can still help with coffee choices and general café questions.`;
    suggestions = ['Recommend a drink', 'Coffee basics', 'Contact Sabino'];
  } else if (/\b(what milk|which milk|milk.*coffee|whole milk|skim milk)\b/u.test(q)) {
    content = '**Whole milk** gives the richest texture, while lower-fat milk tastes lighter. **Oat milk** is creamy and naturally sweet, soy milk has more body and protein, and almond milk is lighter with a nutty taste. Availability and steaming results vary, so ask which milks Sabino currently carries and mention any allergy.';
    suggestions = ['Can drinks be customized?', 'Latte vs cappuccino', 'Allergy information'];
  } else if (/\b(customi[sz]e|less sugar|more sugar|sweetness|no sugar|sugar.free|extra shot|add shot|milk option|oat milk|soy milk|almond milk|non.dairy|dairy.free|no ice|less ice|extra ice|syrup|whipped cream|substitute|adjust)\b/u.test(q)) {
    content = 'Most cafés can adjust **sweetness, ice, milk, syrups, and espresso shots**. You can ask for options such as less sweet, no added sugar, an extra shot, or a non-dairy milk. Sabino’s exact modifiers and possible add-on charges aren’t confirmed yet, so please ask the barista when ordering.';
    suggestions = ['What milk suits coffee?', 'How strong is an extra shot?', 'Recommend a drink'];
  } else if (/\b(allerg\w*|lactose|dairy|gluten|vegan|vegetarian|ingredient|calories|nutrition|nut free|pregnan|medical)\b/u.test(q)) {
    content = `For allergies or dietary needs, tell the barista the **specific ingredient you must avoid** before ordering. Milk, chocolate, flavored syrups, and pastries may contain dairy, nuts, soy, gluten, or other allergens, and shared tools can cause cross-contact. I don’t have Sabino’s verified ingredient or allergen list, so please [contact the shop](${shop.phone_link}) for a safe recommendation.`;
    suggestions = ['Non-coffee options', 'Can drinks be customized?', 'Contact Sabino'];
  } else if (/\b(deliver(?:y|ies|ed)?|pickup|takeout|take away|order online|advance order|pre.order)\b/u.test(q)) {
    content = `I can’t confirm Sabino’s current delivery or pickup setup yet. The quickest option is to [call or text ${shop.phone}](${shop.phone_link}) and ask about same-day availability, preparation time, payment, and pickup instructions.`;
    suggestions = ['Contact Sabino', 'Sample menu', 'Opening hours'];
  } else if (/\b(reserve|reservation|book|table|event|group|function)\b/u.test(q)) {
    content = `For tables, groups, or events, please [contact Sabino](${shop.phone_link}) first. Ask about party size, preferred date and time, minimum spend, deposits, and how long the table can be held. I don’t have a confirmed reservation policy yet.`;
    suggestions = ['Contact Sabino', 'Opening hours', 'Find Sabino'];
  } else if (/\b(payment|pay|gcash|maya|card|cash|credit|debit)\b/u.test(q)) {
    content = `Sabino’s accepted payment methods haven’t been confirmed. It’s best to bring a backup payment method and [ask the shop](${shop.phone_link}) whether they currently accept cash, GCash/Maya, or cards.`;
    suggestions = ['Contact Sabino', 'Sample menu', 'Find Sabino'];
  } else if (/\b(wifi|wi.fi|parking|pets?|pet.friendly|outlet|socket|work|study|laptop|restroom|toilet|accessib)\b/u.test(q)) {
    content = `I don’t have verified details yet about Sabino’s Wi-Fi, parking, outlets, pet policy, restrooms, accessibility, or laptop rules. Please [call or text the shop](${shop.phone_link}) before visiting if one of these is essential. Tell me which amenity you need and I’ll help you form the right question.`;
    suggestions = ['Contact Sabino', 'Find Sabino', 'Opening hours'];
  } else if (/\b(caffeine|caffeinated|decaf|wake me|energy|strongest|more caffeine|less caffeine|low caffeine|how many shots|espresso shots?|how strong.*cold brew|cold brew.*strong)\b/u.test(q)) {
    content = /\b(decaf|less caffeine|low caffeine|sensitive|night|evening)\b/u.test(q)
      ? 'For less caffeine, ask whether **decaf espresso** is available, choose a single-shot milk drink, or pick a non-coffee option such as hot chocolate. Matcha still contains caffeine. Exact caffeine depends on the beans, dose, and number of shots, so ask the barista if you’re sensitive.'
      : 'Caffeine depends on bean type, dose, and serving size. An espresso-based drink usually has the caffeine of its espresso shot even when milk is added; an extra shot increases it. Cold brew can be stronger because it is often brewed as a concentrate, but recipes vary. Ask Sabino how many shots are in the drink you choose.';
    suggestions = ['Strong coffee recommendation', 'Non-coffee options', 'Latte vs cappuccino'];
  } else if (/\b(latte vs cappuccino|cappuccino vs latte|difference.*latte.*cappuccino|difference.*cappuccino.*latte)\b/u.test(q)) {
    content = 'A **latte** has more steamed milk and only a light foam layer, so it tastes smoother and milder. A **cappuccino** uses less milk and more foam, so the espresso tastes more pronounced. Pick a latte for creamy and gentle; cappuccino for airy and coffee-forward.';
    suggestions = ['What is a flat white?', 'Strong coffee recommendation', 'Can drinks be customized?'];
  } else if (/\b(what is|what's|difference|explain).*\b(americano|espresso|latte|cappuccino|flat white|macchiato|mocha|cold brew|spanish latte|matcha)\b/u.test(q)) {
    const drink = q.match(/\b(americano|espresso|latte|cappuccino|flat white|macchiato|mocha|cold brew|spanish latte|matcha)\b/u)?.[1];
    const definitions = {
      espresso: 'Espresso is a small, concentrated coffee brewed under pressure. It has a bold flavor and forms the base of many café drinks.',
      americano: 'An Americano is espresso diluted with hot water. It tastes similar to black coffee while keeping espresso’s aroma and character.',
      latte: 'A latte combines espresso with plenty of steamed milk and a thin foam layer. It is creamy, mild, and easy to flavor.',
      cappuccino: 'A cappuccino combines espresso, steamed milk, and a thicker foam layer. It tastes stronger and airier than a latte.',
      'flat white': 'A flat white uses espresso with a smaller amount of silky microfoam than a latte, giving it a stronger coffee taste and smooth texture.',
      macchiato: 'A traditional macchiato is espresso marked with a small amount of milk or foam. Café versions can vary, so ask how the shop prepares it.',
      mocha: 'A mocha is a latte-style drink with chocolate. It is creamy, sweet, and still has an espresso flavor.',
      'cold brew': 'Cold brew is coffee steeped in cool water for many hours. It is usually smooth and less sharp, though its caffeine strength varies by recipe.',
      'spanish latte': 'A Spanish latte is a sweet, creamy espresso drink commonly made with milk and sweetened condensed milk.',
      matcha: 'Matcha is finely ground green tea whisked with water or milk. It is not coffee, but it normally contains caffeine.'
    };
    content = definitions[drink] || 'That drink can vary by café, so ask the barista how Sabino prepares it.';
    suggestions = ['Latte vs cappuccino', 'Recommend a drink', 'How much caffeine?'];
  } else if (/\b(beans?|roast|arabica|robusta|single origin|blend|flavor notes|acidity)\b/u.test(q)) {
    content = 'Coffee flavor changes with the bean and roast. **Arabica** is often sweeter and more aromatic; **Robusta** is usually bolder and higher in caffeine. Lighter roasts can taste brighter or fruitier, while darker roasts lean toward chocolate, caramel, or smoky notes. I don’t have Sabino’s current bean or roaster details yet, so ask the barista what they are serving today.';
    suggestions = ['What is acidity in coffee?', 'Strong coffee recommendation', 'How is coffee brewed?'];
  } else if (/\b(brew|pour.over|french press|aeropress|v60|drip|grind size|how.*make coffee)\b/u.test(q)) {
    content = 'Brewing changes the cup: **espresso** is concentrated and pressure-brewed, **pour-over** is clean and aromatic, **French press** is fuller-bodied, and **cold brew** is smooth from long steeping. Grind size, water temperature, dose, and brew time all affect flavor. Ask Sabino which brewing methods are currently available.';
    suggestions = ['Tell me about coffee beans', 'What is cold brew?', 'Recommend a drink'];
  } else if (/\b(pair|pairs|paired|pairing|goes with|eat with|food with|pastry with|snack with)\b/u.test(q)) {
    content = 'For an easy pairing, try a **croissant with a latte or cappuccino**, a **chocolate cookie with Americano or cold brew**, or a lighter pastry with matcha. Rich food usually pairs well with a cleaner black coffee, while buttery pastries suit milk drinks. The pastries shown here are sample items, so check Sabino’s current display.';
    suggestions = ['Sample pastries', 'Recommend a drink', 'Coffee basics'];
  } else if (/\b(hot or iced|hot vs iced|iced or hot|temperature|too hot)\b/u.test(q)) {
    content = 'Choose **hot** for more aroma and a softer milk texture; choose **iced** for a colder, more refreshing drink. Ice can dilute the cup as it melts, so an iced drink may be brewed stronger. You can usually request less ice, but Sabino’s exact options should be confirmed when ordering.';
    suggestions = ['Can drinks be customized?', 'Recommend a refreshing drink', 'How strong is cold brew?'];
  } else if (/\b(coffee basics|coffee 101|learn about coffee|new to coffee)\b/u.test(q)) {
    content = 'A quick coffee guide: **espresso** is the concentrated base; add water for an Americano, steamed milk for a latte, less milk plus more foam for a cappuccino, or chocolate for a mocha. Cold brew is steeped cold for hours. Start with a latte if you want mild and creamy, or an Americano if you prefer black coffee.';
    suggestions = ['Latte vs cappuccino', 'Tell me about coffee beans', 'Recommend a drink'];
  } else if (/\b(recommend|suggest|best|sweet|strong|smooth|creamy|bitter|refreshing|not too sweet|less sweet|budget|under|below|less than|cheap|favorite|favourite|non.coffee|without coffee|hindi kape|matapang|matamis)\b/u.test(q)) {
    const budgetMatch = q.match(/(?:under|below|less than|budget(?: of)?|up to)\s*(?:₱|php\s*)?(\d{2,4})/u);
    if (budgetMatch) {
      const budget = Number(budgetMatch[1]);
      const choices = shop.sample_menu.filter(item => item.type !== 'Pastry' && item.price <= budget).map(item => `${item.name} (sample ₱${item.price})`);
      content = choices.length
        ? `For a sample drink budget of ₱${budget}, consider ${choices.join(', ')}. These are estimates only, not confirmed Sabino prices or offerings.`
        : `The sample drink list has no option within ₱${budget}. Please ask Sabino about its current menu and prices.`;
    } else if (/\b(non.coffee|without coffee|hindi kape|matcha|chocolate)\b/u.test(q)) {
      content = 'If you want a drink without coffee, the **sample menu** has Matcha Latte (₱155) and Hot Chocolate (₱125). These are suggestions only; Sabino’s actual selection and prices are unconfirmed.';
    } else if (/\b(not too sweet|less sweet)\b/u.test(q)) {
      content = 'For something not too sweet, try an **Americano**, **cappuccino**, or an unsweetened **café latte**. A latte is the smoothest choice; ask for no syrup or reduced sweetness. The names and prices shown are sample ideas, so confirm Sabino’s current menu.';
    } else if (/\b(refreshing|cold|iced)\b/u.test(q)) {
      content = 'For a refreshing choice, try an **iced Americano** for crisp black coffee, **cold brew** for a smoother taste, or an **iced matcha latte** without coffee. Ask what Sabino has available today and whether sweetness can be adjusted.';
    } else if (/\b(creamy|smooth|mild)\b/u.test(q)) {
      content = 'For creamy and smooth, choose a **café latte**. A **Spanish latte** is creamier and sweeter, while a **flat white** is silky with a stronger coffee taste. Ask Sabino which versions are available.';
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
    content = 'Hi! I’m **Sabinox**, Sabino’s café helper. I can help with drink recommendations, coffee basics, customization, caffeine, food pairings, the sample menu, and confirmed shop details such as the location and phone number. What are you curious about?';
    suggestions = ['Recommend a drink', 'Coffee basics', 'Visit Sabino'];
  } else {
    content = `I’m not certain what you mean yet, but I can help with **coffee drinks, customization, caffeine, beans, brewing, food pairings, ordering questions, and visiting Sabino**. Try asking something like “What’s the difference between a latte and cappuccino?” or “Recommend a not-too-sweet drink.” For a Sabino-specific detail I can’t verify, [call or text the shop](${shop.phone_link}).`;
    suggestions = ['Recommend a drink', 'Latte vs cappuccino', 'Can I customize a drink?'];
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
document.querySelectorAll('[data-chat-open]').forEach(button => button.addEventListener('click', () => openChat(button.dataset.question || '')));
document.querySelectorAll('.chat-panel [data-question]').forEach(button => button.addEventListener('click', () => sendMessage(button.dataset.question)));
chatLauncher.addEventListener('click', () => openChat());
chatClose.addEventListener('click', closeChat);
chatBackdrop.addEventListener('click', closeChat);
document.addEventListener('keydown', event => { if (event.key === 'Escape' && chatPanel.classList.contains('open')) closeChat(); });
navToggle.addEventListener('click', () => {
  const open = siteNav.classList.toggle('open');
  navToggle.setAttribute('aria-expanded', String(open));
});
siteNav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { siteNav.classList.remove('open'); navToggle.setAttribute('aria-expanded', 'false'); }));
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
