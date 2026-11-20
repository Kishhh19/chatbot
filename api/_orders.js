import crypto from 'node:crypto';
import { catalogById } from './_catalog.js';

const secret = () => process.env.ORDER_SIGNING_SECRET || process.env.DIFY_API_KEY || '';
const encode = value => Buffer.from(JSON.stringify(value)).toString('base64url');

export function normalizeOrder(input) {
  const customer = String(input?.customer || 'Guest').trim().slice(0, 60) || 'Guest';
  const reference = String(input?.reference || '').trim().slice(0, 40);
  const items = (Array.isArray(input?.items) ? input.items : []).slice(0, 30).map(row => {
    const item = catalogById.get(String(row.id || ''));
    const quantity = Math.max(1, Math.min(20, Number.parseInt(row.quantity, 10) || 1));
    return item ? { id: item.id, name: item.name, price: item.price, quantity, subtotal: item.price * quantity } : null;
  }).filter(Boolean);
  if (!items.length) throw new Error('Your order is empty.');
  return { customer, reference, items, total: items.reduce((sum, item) => sum + item.subtotal, 0) };
}

export function signOrder(payload) {
  if (!secret()) throw new Error('Order signing is not configured.');
  const body = encode(payload);
  const signature = crypto.createHmac('sha256', secret()).update(body).digest('base64url');
  return `${body}.${signature}`;
}

export function verifyOrder(token) {
  const [body, supplied] = String(token || '').split('.');
  if (!body || !supplied || !secret()) throw new Error('Invalid order QR.');
  const expected = crypto.createHmac('sha256', secret()).update(body).digest('base64url');
  if (supplied.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(supplied), Buffer.from(expected))) throw new Error('Invalid order QR.');
  const payload = JSON.parse(Buffer.from(body, 'base64url').toString('utf8'));
  if (!payload.orderId || !Array.isArray(payload.items)) throw new Error('Invalid order QR.');
  return payload;
}

export function sendJson(res, status, body) {
  res.statusCode = status;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.setHeader('Cache-Control', 'no-store');
  res.end(JSON.stringify(body));
}
