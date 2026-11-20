import crypto from 'node:crypto';
import { normalizeOrder, sendJson, signOrder } from './_orders.js';

export default async function handler(req, res) {
  if (req.method !== 'POST') return sendJson(res, 405, { error: 'Method not allowed.' });
  try {
    const order = normalizeOrder(req.body);
    const orderId = `SAB-${Date.now().toString(36).toUpperCase()}-${crypto.randomBytes(2).toString('hex').toUpperCase()}`;
    const createdAt = new Date().toISOString();
    let checkoutId = null;
    let checkoutUrl = null;
    let paymentError = null;
    const paymongoKey = process.env.PAYMONGO_SECRET_KEY;

    if (paymongoKey) {
      const origin = `https://${req.headers['x-forwarded-host'] || req.headers.host}`;
      const methods = String(process.env.PAYMONGO_PAYMENT_METHODS || 'gcash,paymaya,qrph,card').split(',').map(v => v.trim()).filter(Boolean);
      const response = await fetch('https://api.paymongo.com/v1/checkout_sessions', {
        method: 'POST',
        headers: { Authorization: `Basic ${Buffer.from(`${paymongoKey}:`).toString('base64')}`, 'Content-Type': 'application/json' },
        body: JSON.stringify({ data: { attributes: {
          billing: { name: order.customer },
          cancel_url: `${origin}/?payment=cancelled`,
          success_url: `${origin}/?payment=success&order=${encodeURIComponent(orderId)}`,
          description: `Sabino order ${orderId}`,
          line_items: order.items.map(item => ({ currency: 'PHP', amount: item.price * 100, name: item.name, quantity: item.quantity })),
          payment_method_types: methods,
          reference_number: orderId,
          send_email_receipt: false,
          show_description: true,
          show_line_items: true
        } } }),
        signal: AbortSignal.timeout(12000)
      });
      const payment = await response.json();
      if (response.ok) {
        checkoutId = payment.data?.id || null;
        checkoutUrl = payment.data?.attributes?.checkout_url || null;
      } else paymentError = payment.errors?.[0]?.detail || 'Online payment is temporarily unavailable.';
    }

    const payload = { ...order, orderId, createdAt, checkoutId };
    const token = signOrder(payload);
    return sendJson(res, 200, { order: payload, token, checkout_url: checkoutUrl, payment_available: Boolean(checkoutUrl), payment_error: paymentError });
  } catch (error) {
    return sendJson(res, 400, { error: error.message || 'Unable to create the order.' });
  }
}
