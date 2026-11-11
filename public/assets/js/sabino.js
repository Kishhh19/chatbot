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
function menuText(kind = null) {
  const kinds = kind ? [kind] : ['Drinks', 'Food'];
  const sections = kinds.map(currentKind => {
    const groups = shop.menu.filter(item => item.kind === currentKind).reduce((result, item) => {
      (result[item.category] ||= []).push(item); return result;
    }, {});
    const blocks = [`**${currentKind.toUpperCase()}**`];
    Object.entries(groups).forEach(([category, items]) => {
      const lines = [`**${category}**`];
      items.forEach(item => lines.push(`- ${item.name} — ₱${item.price}${item.sharing_price ? ` solo / ₱${item.sharing_price} sharing` : ''}`));
      blocks.push(lines.join('\n'));
    });
    return blocks.join('\n\n');
  });
  return sections.join('\n\n') + `\n\nAvailability can change. [Call or text Sabino](${shop.phone_link}) for today’s items.`;
}
function coffeeGuide(q) {
  let content = '';
  let suggestions = ['Coffee basics', 'Recommend a drink', 'Show drinks menu'];
  if (/\b(strong(?:er|est)? coffee.*caffeine|caffeine.*strong(?:er|est)? coffee|dark roast.*caffeine|caffeine.*dark roast)\b/u.test(q)) {
    content = '**Strong flavor and high caffeine are not the same thing.** Dark roast can taste bolder, but caffeine depends more on the bean, dose, number of shots, and serving size. For more caffeine, ask about an extra shot; for bold flavor, try Sabino’s **Americano (₱130)**.';
  } else if (/\b(coffee.*sleep|sleep.*coffee|coffee.*bed|bedtime|how late.*coffee|caffeine.*hours?)\b/u.test(q)) {
    content = 'Caffeine can remain active for several hours and may affect sleep even when you no longer feel energized. If you’re sensitive, avoid coffee late in the day, ask whether decaf is available, or choose a caffeine-free drink.';
    suggestions = ['Low-caffeine choices', 'Non-coffee options', 'Show drinks menu'];
  } else if (/\b(caffeine|caffeinated|decaf|wake me|energy|more caffeine|less caffeine|low caffeine|how many shots|espresso shots?)\b/u.test(q)) {
    content = /\b(decaf|less caffeine|low caffeine|sensitive|night|evening)\b/u.test(q)
      ? 'For less caffeine, ask whether **decaf espresso** is available, choose a single-shot milk drink, or pick a non-coffee option. Matcha still contains caffeine. Exact amounts depend on the recipe.'
      : 'Caffeine depends on bean type, dose, serving size, and number of espresso shots. Milk changes taste and texture but does not remove the shot’s caffeine. Ask Sabino how many shots are included or whether an extra shot is available.';
    suggestions = ['Strong coffee recommendation', 'Low-caffeine choices', 'Show drinks menu'];
  } else if (/\b(latte vs cappuccino|cappuccino vs latte|difference.*latte.*cappuccino)\b/u.test(q)) {
    content = 'A **latte** has more steamed milk and light foam, so it tastes smoother and milder. A **cappuccino** has less milk and more foam, so the espresso tastes more pronounced.';
  } else if (/\b(flat white vs cappuccino|cappuccino vs flat white|difference.*flat white.*cappuccino)\b/u.test(q)) {
    content = 'A **flat white** has silky microfoam and less milk than a latte, so it is smooth but coffee-forward. A **cappuccino** has a thicker, airier foam cap.';
  } else if (/\b(americano vs brewed coffee|brewed coffee vs americano|difference.*americano.*brewed)\b/u.test(q)) {
    content = 'An **Americano** is espresso diluted with water; **brewed coffee** is made by passing or steeping water through ground coffee. They can look similar, but their brewing methods and flavor are different.';
  } else if (/\b(cold brew vs iced coffee|iced coffee vs cold brew|difference.*cold brew.*iced)\b/u.test(q)) {
    content = '**Iced coffee** is hot-brewed coffee cooled over ice, while **cold brew** steeps in cool water for many hours and is usually smoother. Either can be strong; caffeine depends on the recipe and serving size.';
  } else if (/\b(what is|what's|explain).*\b(americano|espresso|latte|cappuccino|flat white|macchiato|mocha|cold brew|spanish latte|matcha|cortado|ristretto|lungo|microfoam)\b/u.test(q)) {
    const definitions = {
      espresso: 'Espresso is a small, concentrated coffee brewed under pressure. It has a bold flavor and forms the base of many café drinks.',
      americano: 'An Americano is espresso diluted with hot water. It tastes similar to black coffee while keeping espresso’s aroma.',
      latte: 'A latte combines espresso with plenty of steamed milk and a thin foam layer. It is creamy, mild, and easy to flavor.',
      cappuccino: 'A cappuccino combines espresso, steamed milk, and thicker foam. It tastes stronger and airier than a latte.',
      'flat white': 'A flat white uses espresso with a smaller amount of silky microfoam than a latte, giving it a stronger coffee taste.',
      macchiato: 'A traditional macchiato is espresso marked with a small amount of milk or foam. Café versions can vary.',
      mocha: 'A mocha is a latte-style coffee drink with chocolate: creamy, sweet, and still espresso-forward.',
      'cold brew': 'Cold brew is coffee steeped in cool water for many hours. It is usually smooth and less sharp, though caffeine varies.',
      'spanish latte': 'A Spanish latte is a sweet, creamy espresso drink commonly made with milk and sweetened condensed milk.',
      matcha: 'Matcha is finely ground green tea whisked with water or milk. It is not coffee, but it normally contains caffeine.',
      cortado: 'A cortado is espresso balanced with a small, roughly equal amount of warm milk. It is stronger and less milky than a latte.',
      ristretto: 'A ristretto is a shorter espresso extraction using less water. It is concentrated and often sweeter than a longer shot.',
      lungo: 'A lungo is an espresso pulled with more water for longer. It makes a larger shot and can taste more bitter.',
      microfoam: 'Microfoam is steamed milk with extremely small bubbles, creating a glossy, velvety texture used in lattes and flat whites.'
    };
    const term = Object.keys(definitions).find(name => q.includes(name));
    content = definitions[term] || 'That drink can vary by café, so ask the barista how it is prepared.';
  } else if (/\b(light|medium|dark) roast|roast levels?|which roast/u.test(q)) {
    content = '**Light roast** is often brighter and fruitier; **medium roast** balances acidity, sweetness, and body; **dark roast** tastes bolder and roastier. Roast level describes flavor more reliably than caffeine.';
  } else if (/\b(arabica vs robusta|robusta vs arabica|difference.*arabica.*robusta)\b/u.test(q)) {
    content = '**Arabica** is commonly sweeter, more aromatic, and more acidic. **Robusta** is usually earthier, more bitter, fuller-bodied, and higher in caffeine.';
  } else if (/\b(acidity|coffee body|tasting notes?|flavo(?:u)?r notes?)\b/u.test(q)) {
    content = 'In coffee, **acidity** means lively brightness, **body** is how heavy the drink feels, and **tasting notes** describe natural flavor reminders such as chocolate, nuts, caramel, or berries—not necessarily added ingredients.';
  } else if (/\b(crema|foam on espresso)\b/u.test(q)) {
    content = '**Crema** is the golden-brown foam on freshly pulled espresso, formed by oils and trapped gas. It adds aroma and texture, but thick crema alone does not guarantee a good shot.';
  } else if (/\b(sour coffee|coffee.*sour|bitter coffee|coffee.*bitter|under.?extract|over.?extract|extraction)\b/u.test(q)) {
    content = 'Sharply **sour** coffee may be under-extracted; harsh, dry, very **bitter** coffee may be over-extracted. Grind size, water temperature, dose, and brew time all matter, so change one variable at a time.';
  } else if (/\b(store coffee|coffee storage|keep beans|fresh beans|coffee freshness|freeze coffee)\b/u.test(q)) {
    content = 'Keep beans in an **airtight, opaque container** away from heat, light, moisture, and strong odors. Buy manageable amounts and grind just before brewing.';
  } else if (/\b(grind size|water temperature|brew temperature|coffee ratio|coffee.*water ratio)\b/u.test(q)) {
    content = 'Use a **fine grind for espresso**, medium for drip or pour-over, and coarse for French press or cold brew. Start near **90–96°C** and a **1:15–1:17 coffee-to-water ratio** for filter coffee, then adjust to taste.';
  } else if (/\b(milky|milk.forward|lots? of milk)\b/u.test(q)) {
    content = 'For a milky drink, start with **Latte (₱150)**. Choose **Spanish Latte (₱160)** if you also want sweetness, or **Pistachio Latte (₱250)** for a richer flavored option.';
    suggestions = ['Strong coffee recommendation', 'Sweet coffee recommendation', 'Show drinks menu'];
  } else if (/\b(hard coffee|strong coffee|bold coffee|intense coffee|matapang|black coffee)\b/u.test(q)) {
    content = 'If by “hard coffee” you mean bold and coffee-forward, choose **Americano (₱130)**. For coffee mixed with matcha, try **Dirty Matcha (₱200)**. Bold taste does not always mean more caffeine, so ask how many shots are included.';
  } else if (/\b(beginner|first coffee|new to coffee)\b/u.test(q)) {
    content = 'For a first coffee, try **Latte (₱150)** for mild and creamy, or **Spanish Latte (₱160)** if you prefer sweet. If you already enjoy black coffee, begin with **Americano (₱130)**.';
  } else if (/\b(fruity|fruit drink)\b/u.test(q)) {
    content = 'For fruity drinks, try Sabino’s **Italian Sodas (₱150)** or **fruit Teas (₱150)**. Lychee and Peach are gently sweet; Green Apple, Kiwi, and Strawberry Lemon are brighter.';
  } else if (/\b(chocolatey|chocolate drink|chocolate coffee)\b/u.test(q)) {
    content = 'For chocolate without coffee, choose **Dark Chocolate (₱190)**. For a dessert-like coffee, **Tiramisu Latte (₱200)** is the closest fit on Sabino’s menu.';
  } else return null;
  return {content, suggestions};
}
function answer(question) {
  const q = question.trim().toLocaleLowerCase();
  let content;
  let suggestions = ['Full menu', 'Opening hours', 'Find Sabino'];
  const guide = coffeeGuide(q);
  if (/\b(where|address|location|located|directions|map|find|visit|saan|nasaan|pupunta|salapungan)\b/u.test(q)) {
    content = `Sabino is at **${shop.address}**. [Open directions](${shop.maps}).`;
    suggestions = ['Opening hours', 'Contact Sabino', 'Full menu'];
  } else if (/\b(hours?|opening|opens?|closing|closes?|closed|today|tomorrow|sunday|monday|saturday|oras|bukas|sarado|schedule)\b/u.test(q)) {
    content = `**Reported hours:** ${shop.hours} [Call to confirm](${shop.phone_link}).`;
    suggestions = ['Find Sabino', 'Contact Sabino', 'Full menu'];
  } else if (/\b(phone|number|contact|call|text|message|instagram|ig|social|tawag|telepono)\b/u.test(q)) {
    content = `You can [call or text Sabino](${shop.phone_link}) at **${shop.phone}**, or visit [their Instagram](${shop.instagram}).`;
    suggestions = ['Find Sabino', 'Opening hours', 'Full menu'];
  } else if (/\b(do you know|who is|who's|kilala mo|staff|owner|manager|barista|employee|crew)\b/u.test(q)) {
    content = `I don’t have a verified staff or owner directory for Sabino. Please [contact Sabino](${shop.phone_link}) directly for staff-related questions.`;
    suggestions = ['Recommend a drink', 'Full menu', 'Contact Sabino'];
  } else if (/\b(customi[sz]e|less sugar|more sugar|sweetness|no sugar|sugar.free|extra shot|add shot|milk option|oat milk|soy milk|almond milk|non.dairy|dairy.free|no ice|less ice|extra ice|syrup|substitute|adjust)\b/u.test(q)) {
    content = 'Many cafés can adjust **sweetness, ice, milk, syrups, and espresso shots**. Sabino’s exact modifiers and charges are not listed on the menu board, so please ask the barista when ordering.';
    suggestions = ['Recommend a drink', 'Show drinks menu', 'Allergy information'];
  } else if (/\b(allerg\w*|lactose|dairy|gluten|vegan|vegetarian|ingredient|calories|nutrition|nut free|pregnan|medical)\b/u.test(q)) {
    content = `Tell the barista the **specific ingredient you must avoid** before ordering. Shared tools can cause cross-contact, and I don’t have Sabino’s verified allergen list. Please [contact the shop](${shop.phone_link}) for a safe recommendation.`;
    suggestions = ['Show drinks menu', 'Show food menu', 'Contact Sabino'];
  } else if (/\b(deliver(?:y|ies|ed)?|pickup|takeout|take away|order online|advance order|pre.order)\b/u.test(q)) {
    content = `I can’t confirm Sabino’s current delivery or pickup setup. Please [call or text ${shop.phone}](${shop.phone_link}) to ask about same-day availability and pickup.`;
    suggestions = ['Contact Sabino', 'Full menu', 'Opening hours'];
  } else if (/\b(reserve|reservation|book|table|event|group|function)\b/u.test(q)) {
    content = `For tables, groups, or events, please [contact Sabino](${shop.phone_link}) first. I don’t have a confirmed reservation policy.`;
    suggestions = ['Contact Sabino', 'Opening hours', 'Find Sabino'];
  } else if (/\b(payment|pay|gcash|maya|card|cash|credit|debit)\b/u.test(q)) {
    content = `Sabino’s accepted payment methods aren’t listed on the menu board. Please [ask the shop](${shop.phone_link}) whether they currently accept cash, GCash/Maya, or cards.`;
    suggestions = ['Contact Sabino', 'Full menu', 'Find Sabino'];
  } else if (/\b(wifi|wi.fi|parking|pets?|pet.friendly|outlet|socket|work|study|laptop|restroom|toilet|accessib)\b/u.test(q)) {
    content = `I don’t have verified details about Sabino’s amenities. Please [call or text the shop](${shop.phone_link}) before visiting if one is essential.`;
    suggestions = ['Contact Sabino', 'Find Sabino', 'Opening hours'];
  } else if (guide) {
    content = guide.content;
    suggestions = guide.suggestions;
  } else if (/\b(latte vs cappuccino|cappuccino vs latte|difference.*latte.*cappuccino|difference.*cappuccino.*latte)\b/u.test(q)) {
    content = 'A **latte** has more steamed milk and light foam, while a **cappuccino** has less milk and more foam. Sabino’s listed coffee menu includes Latte at ₱150.';
    suggestions = ['Show coffee menu', 'Recommend a drink', 'Can drinks be customized?'];
  } else if (/\b(beans?|roast|arabica|robusta|single origin|blend|flavor notes|acidity|brew|pour.over|french press|aeropress|v60|drip|grind size)\b/u.test(q)) {
    content = 'Coffee flavor changes with the bean, roast, grind, and brewing method. Sabino’s current board does not list its bean or roaster details, so ask the barista what they are serving today.';
    suggestions = ['Show coffee menu', 'Recommend a drink', 'Coffee basics'];
  } else if (/\b(pair|pairs|paired|pairing|goes with|eat with|food with|snack with)\b/u.test(q)) {
    content = 'From Sabino’s menu, try **PB & J with a Latte**, **Carbonara with an Americano**, or **Wings with an Italian Soda or Tea**.';
    suggestions = ['Show food menu', 'Show drinks menu', 'Recommend a drink'];
  } else if (/\b(recommend|suggest|best|sweet|strong|hard coffee|bold|intense|smooth|creamy|milky|bitter|fruity|refreshing|not too sweet|less sweet|budget|under|below|less than|cheap|favorite|favourite|beginner|first coffee|non.coffee|without coffee|hindi kape|matapang|matamis)\b/u.test(q)) {
    const budgetMatch = q.match(/(?:under|below|less than|budget(?: of)?|up to)\s*(?:₱|php\s*)?(\d{2,4})/u);
    if (budgetMatch) {
      const budget = Number(budgetMatch[1]);
      const choices = [...new Set(shop.menu.filter(item => item.kind === 'Drinks' && item.price <= budget).map(item => `${item.name} (₱${item.price})`))];
      content = choices.length ? `For a drink budget of ₱${budget}, Sabino currently lists ${choices.join(', ')}.` : `Sabino’s current drink menu has no listed option within ₱${budget}.`;
    } else if (/\b(non.coffee|without coffee|hindi kape)\b/u.test(q)) {
      content = 'For a non-coffee choice, try **Dark Chocolate (₱190)**, **Matcha Latte (₱190)**, **Ube (₱200)**, Italian Soda (₱150), or fruit Tea (₱150).';
    } else if (/\b(matcha)\b/u.test(q)) {
      content = 'Choose **Matcha Latte (₱190)** for creamy green tea without coffee, or **Dirty Matcha (₱200)** for matcha plus espresso. Matcha still contains caffeine.';
    } else if (/\b(not too sweet|less sweet)\b/u.test(q)) {
      content = 'For something not too sweet, start with an **Americano (₱130)** or **Latte (₱150)**.';
    } else if (/\b(refreshing|cold|iced)\b/u.test(q)) {
      content = 'For something refreshing, try any **Italian Soda (₱150)** or fruit **Tea (₱150)**.';
    } else if (/\b(creamy|smooth|mild|milky)\b/u.test(q)) {
      content = 'For a milky, creamy drink, choose **Latte (₱150)**, **Spanish Latte (₱160)**, or **Pistachio Latte (₱250)**.';
    } else if (/\b(sweet|matamis|chocolate)\b/u.test(q)) {
      content = 'For a sweeter coffee, try **Spanish Latte (₱160)**, **Caramel Macchiato (₱190)**, or **Tiramisu Latte (₱200)**.';
    } else if (/\b(strong|matapang|black)\b/u.test(q)) {
      content = 'For bold coffee, choose **Americano (₱130)**. For coffee with matcha, try **Dirty Matcha (₱200)**.';
    } else {
      content = 'Try **Americano (₱130)** for black coffee, **Latte (₱150)** for something creamy, or **Lychee Mango Tea (₱150)** for something fruity.';
    }
    suggestions = ['Show drinks menu', 'Show food menu', 'Opening hours'];
  } else if (/\b(coffee|espresso|caffeine|beans?|roast|brew|kape)\b/u.test(q) && !/\b(show|menu|price|cost|how much|magkano)\b/u.test(q)) {
    content = 'I can answer that coffee question, but I may need one more detail. Are you asking about **choosing a drink, caffeine, beans and roast, brewing, milk, flavor, or customization**? Try “Which Sabino drink is milky?” or “Why is coffee sour?”';
    suggestions = ['Recommend a milky drink', 'Strong coffee recommendation', 'Coffee basics'];
  } else if (/\b(menu|prices?|cost|much|drinks?|beverages?|coffee|latte|americano|soda|tea|specialty|food|starters?|salad|soup|rice meals?|pasta|sandwich|burger|wings?|add.ons?|presyo|magkano|kape|pagkain)\b/u.test(q)) {
    let kind = null;
    if (/\b(food|starters?|salad|soup|rice meals?|pasta|sandwich|burger|wings?|add.ons?|pagkain)\b/u.test(q)) kind = 'Food';
    else if (/\b(drinks?|beverages?|coffee|latte|americano|soda|tea|specialty|kape)\b/u.test(q)) kind = 'Drinks';
    const found = shop.menu.filter(item => item.name.length >= 5 && q.includes(item.name.toLocaleLowerCase()));
    if (found.length) {
      content = 'Here’s the exact price from Sabino’s menu:\n\n' + found.map(item => `- **${item.name}** — ₱${item.price}${item.sharing_price ? ` solo / ₱${item.sharing_price} sharing` : ''} · ${item.category}`).join('\n');
    } else content = menuText(kind);
    suggestions = kind === 'Food' ? ['Show drinks menu', 'Recommend a drink', 'Find Sabino'] : (kind === 'Drinks' ? ['Show food menu', 'Recommend a drink', 'Find Sabino'] : ['Recommend a drink', 'Show food menu', 'Find Sabino']);
  } else if (/\b(hello|hi|hey|good morning|good afternoon|kumusta|salamat|thanks|help|faq|sabinox|sabino|who are you)\b/u.test(q)) {
    content = 'Hi! I’m **Sabinox**, Sabino’s café helper. I can show the current drinks and food menus with exact prices, recommend a drink, explain coffee, and help with location, hours, or contact details.';
    suggestions = ['Show drinks menu', 'Show food menu', 'Recommend a drink'];
  } else {
    content = `I may not have understood the exact term, but I can help with **drink recommendations, coffee definitions, caffeine, beans and roast, brewing, customization, food pairings, or visiting Sabino**. Tell me whether you want something milky, strong, sweet, bitter, fruity, or refreshing. For an unverified shop detail, [call or text Sabino](${shop.phone_link}).`;
    suggestions = ['Recommend a milky drink', 'Strong coffee recommendation', 'Coffee basics'];
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
    if (part.includes('\n- ')) {
      const [heading, ...lines] = part.split('\n');
      return `<p>${heading}</p><ul>${lines.map(line => `<li>${line.replace(/^- /, '')}</li>`).join('')}</ul>`;
    }
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
document.querySelectorAll('[data-menu-filter]').forEach(button => button.addEventListener('click', () => {
  document.querySelectorAll('[data-menu-filter]').forEach(item => item.classList.toggle('active', item === button));
  document.querySelectorAll('[data-menu-kind]').forEach(section => section.classList.toggle('active', section.dataset.menuKind === button.dataset.menuFilter));
}));
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

