'use strict';
(() => {
  const shop = window.SABINO_DATA;
  if (!shop?.menu) return;
  const itemHost = document.getElementById('order-items');
  const cartHost = document.getElementById('cart-lines');
  const countHost = document.getElementById('cart-count');
  const totalHost = document.getElementById('cart-total');
  const reviewButton = document.getElementById('review-order');
  const dialog = document.getElementById('order-dialog');
  const dialogContent = document.getElementById('order-dialog-content');
  const customerInput = document.getElementById('order-customer');
  const referenceInput = document.getElementById('order-reference');
  const searchInput = document.getElementById('order-search');
  let activeKind = 'Drinks';
  const cart = new Map();

  const catalog = shop.menu.flatMap(item => {
    const regular = {...item, id: `${item.kind}|${item.category}|${item.name}`};
    return item.sharing_price ? [regular, {...item, id: `${item.kind}|${item.category}|${item.name} (Sharing)`, name: `${item.name} (Sharing)`, price: item.sharing_price, sharing_price: undefined}] : [regular];
  });
  const money = value => `₱${Number(value).toLocaleString('en-PH')}`;
  const escape = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const selected = () => [...cart.values()];
  const total = () => selected().reduce((sum, row) => sum + row.price * row.quantity, 0);

  function renderMenu() {
    const query = searchInput.value.trim().toLowerCase();
    const matches = catalog.filter(item => item.kind === activeKind && (!query || `${item.name} ${item.category}`.toLowerCase().includes(query)));
    const groups = Object.groupBy ? Object.groupBy(matches, item => item.category) : matches.reduce((all, item) => ((all[item.category] ||= []).push(item), all), {});
    itemHost.innerHTML = Object.entries(groups).map(([category, items]) => `<section class="order-group"><h3>${escape(category)}</h3><div>${items.map(item => `<article><span><strong>${escape(item.name)}</strong><small>${money(item.price)}</small></span><button type="button" data-add-item="${escape(item.id)}" aria-label="Add ${escape(item.name)}">+</button></article>`).join('')}</div></section>`).join('') || '<p class="order-empty">No menu item matches that search.</p>';
  }

  function renderCart() {
    const rows = selected();
    const units = rows.reduce((sum, row) => sum + row.quantity, 0);
    countHost.textContent = `${units} item${units === 1 ? '' : 's'}`;
    totalHost.textContent = money(total());
    reviewButton.disabled = !rows.length;
    cartHost.innerHTML = rows.length ? rows.map(row => `<div class="cart-line"><span><strong>${escape(row.name)}</strong><small>${money(row.price)} each</small></span><span class="quantity"><button type="button" data-cart-change="${escape(row.id)}" data-delta="-1" aria-label="Remove one">−</button><b>${row.quantity}</b><button type="button" data-cart-change="${escape(row.id)}" data-delta="1" aria-label="Add one">+</button></span><strong>${money(row.price * row.quantity)}</strong></div>`).join('') : '<p class="empty-cart">Your order is waiting for something good.</p>';
  }

  function reviewHtml() {
    return `<div class="dialog-kicker">PLEASE CHECK BEFORE CONFIRMING</div><h2 id="order-dialog-title">Is everything correct?</h2><p class="dialog-lede">Check each item and quantity. Once confirmed, this exact order is placed in a secure QR for Sabino staff.</p><div class="dialog-order-lines">${selected().map(row => `<div><span>${row.quantity} × ${escape(row.name)}</span><strong>${money(row.quantity * row.price)}</strong></div>`).join('')}</div><div class="dialog-total"><span>Total</span><strong>${money(total())}</strong></div><div class="dialog-actions"><button class="secondary" type="button" data-edit-order>Go back & edit</button><button class="primary" type="button" data-confirm-order>Confirm correct order</button></div>`;
  }

  async function confirmOrder(button) {
    button.disabled = true;
    button.textContent = 'Creating secure order…';
    try {
      const response = await fetch('/api/create-checkout', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ customer: customerInput.value, reference: referenceInput.value, items: selected().map(row => ({id: row.id, quantity: row.quantity})) }) });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Unable to create your order.');
      const scanUrl = `${location.origin}/?view-order#order=${encodeURIComponent(data.token)}`;
      dialogContent.innerHTML = `<div class="dialog-kicker">ORDER CONFIRMED FOR STAFF REVIEW</div><h2 id="order-dialog-title">Show this QR to the team.</h2><p class="order-number">${escape(data.order.orderId)}</p><div id="order-qr" class="order-qr"></div><p class="dialog-lede">Staff can scan this code to verify your items, total, and payment status. Preparation begins only when Sabino accepts the order.</p><div class="payment-status ${data.payment_available ? 'ready' : ''}">${data.payment_available ? 'Secure online payment is ready.' : 'Online payment is not active yet. Please pay using a method confirmed by staff.'}</div><div class="dialog-actions"><button class="secondary" type="button" data-copy-order>Copy order link</button>${data.payment_available ? `<a class="primary" href="${escape(data.checkout_url)}">Pay securely</a>` : ''}</div>`;
      if (window.QRCode) new QRCode(document.getElementById('order-qr'), {text: scanUrl, width: 220, height: 220, colorDark:'#142d22', colorLight:'#ffffff', correctLevel: QRCode.CorrectLevel.M});
      dialogContent.querySelector('[data-copy-order]')?.addEventListener('click', async event => { await navigator.clipboard.writeText(scanUrl); event.currentTarget.textContent = 'Link copied'; });
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Confirm correct order';
      const note = document.createElement('p'); note.className = 'dialog-error'; note.textContent = error.message; dialogContent.appendChild(note);
    }
  }

  async function showScannedOrder(token) {
    dialog.showModal();
    dialogContent.innerHTML = '<div class="dialog-kicker">VERIFYING SECURE ORDER</div><h2 id="order-dialog-title">Checking the QR…</h2>';
    try {
      const response = await fetch('/api/order-status', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({token})});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'This order could not be verified.');
      const order = data.order;
      const paymentLabel = data.payment_status === 'paid' || data.payment_status === 'succeeded' ? 'PAID' : data.payment_status === 'not_configured' ? 'PAY AT SHOP' : 'PAYMENT PENDING';
      dialogContent.innerHTML = `<div class="dialog-kicker">VERIFIED SABINO ORDER</div><h2 id="order-dialog-title">${escape(order.orderId)}</h2><p class="dialog-lede"><strong>${escape(order.customer)}</strong>${order.reference ? ` · ${escape(order.reference)}` : ''}<br>Created ${new Date(order.createdAt).toLocaleString('en-PH')}</p><div class="dialog-order-lines">${order.items.map(row => `<div><span>${row.quantity} × ${escape(row.name)}</span><strong>${money(row.subtotal)}</strong></div>`).join('')}</div><div class="dialog-total"><span>Total</span><strong>${money(order.total)}</strong></div><div class="payment-status ${paymentLabel === 'PAID' ? 'paid' : ''}">${paymentLabel}</div><p class="staff-note">Staff: confirm availability and accept this order before preparation.</p>`;
    } catch (error) {
      dialogContent.innerHTML = `<div class="dialog-kicker">ORDER NOT VERIFIED</div><h2 id="order-dialog-title">This QR is not valid.</h2><p class="dialog-error">${escape(error.message)}</p>`;
    }
  }

  itemHost.addEventListener('click', event => { const button = event.target.closest('[data-add-item]'); if (!button) return; const item = catalog.find(row => row.id === button.dataset.addItem); const current = cart.get(item.id); cart.set(item.id, {...item, quantity: Math.min(20, (current?.quantity || 0) + 1)}); renderCart(); });
  cartHost.addEventListener('click', event => { const button = event.target.closest('[data-cart-change]'); if (!button) return; const row = cart.get(button.dataset.cartChange); if (!row) return; row.quantity += Number(button.dataset.delta); if (row.quantity < 1) cart.delete(row.id); renderCart(); });
  document.querySelectorAll('[data-order-kind]').forEach(button => button.addEventListener('click', () => { activeKind = button.dataset.orderKind; document.querySelectorAll('[data-order-kind]').forEach(tab => tab.classList.toggle('active', tab === button)); renderMenu(); }));
  searchInput.addEventListener('input', renderMenu);
  reviewButton.addEventListener('click', () => { dialogContent.innerHTML = reviewHtml(); dialog.showModal(); });
  dialog.addEventListener('click', event => { if (event.target.hasAttribute('data-edit-order')) dialog.close(); const button = event.target.closest('[data-confirm-order]'); if (button) confirmOrder(button); });
  document.getElementById('order-dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  renderMenu(); renderCart();
  const token = new URLSearchParams(location.hash.slice(1)).get('order');
  if (token) showScannedOrder(token);
})();
