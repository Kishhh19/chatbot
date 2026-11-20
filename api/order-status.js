import { sendJson, verifyOrder } from './_orders.js';

export default async function handler(req, res) {
  if (req.method !== 'POST') return sendJson(res, 405, { error: 'Method not allowed.' });
  try {
    const order = verifyOrder(req.body?.token);
    let paymentStatus = order.checkoutId ? 'pending' : 'not_configured';
    if (order.checkoutId && process.env.PAYMONGO_SECRET_KEY) {
      const response = await fetch(`https://api.paymongo.com/v1/checkout_sessions/${encodeURIComponent(order.checkoutId)}`, {
        headers: { Authorization: `Basic ${Buffer.from(`${process.env.PAYMONGO_SECRET_KEY}:`).toString('base64')}` },
        signal: AbortSignal.timeout(10000)
      });
      if (response.ok) {
        const payment = await response.json();
        const attrs = payment.data?.attributes || {};
        paymentStatus = attrs.payment_intent?.attributes?.status || (attrs.payments?.length ? 'paid' : 'pending');
      }
    }
    return sendJson(res, 200, { order, payment_status: paymentStatus });
  } catch (error) {
    return sendJson(res, 400, { error: error.message || 'Unable to verify this order.' });
  }
}
