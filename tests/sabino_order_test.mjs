import fs from 'node:fs';
import vm from 'node:vm';

const html = fs.readFileSync(new URL('../public/index.html', import.meta.url), 'utf8');
const dataMatch = html.match(/window\.SABINO_DATA = (\{.*?\});<\/script>/s);
if (!dataMatch) throw new Error('Could not read Sabino menu data.');

const source = fs.readFileSync(new URL('../assets/js/sabino-static.js', import.meta.url), 'utf8');
const start = source.indexOf('const quantityWords');
const end = source.indexOf('function coffeeGuide');
if (start < 0 || end < 0) throw new Error('Could not locate order parser.');

const context = vm.createContext({shop: JSON.parse(dataMatch[1])});
vm.runInContext(`${source.slice(start, end)}\nthis.orderTools = {parseMenuItems, orderIntentAnswer};`, context);
const {parseMenuItems, orderIntentAnswer} = context.orderTools;

const cases = [
  ['americano for coffee and I want a pasta carbonara', [['Americano', 1], ['Carbonara', 1]], 400],
  ['Order 2 lattes, 1 carbonara, and 3 nachos', [['Latte', 2], ['Carbonara', 1], ['Nachos', 3]], 1380],
  ['Add an Americano, Tapa, and Garlic Bread', [['Americano', 1], ['Tapa', 1], ['Garlic Bread', 1]], 400],
  ['I want sharing buffalo and 2 spanish lattes', [['Buffalo (Sharing)', 1], ['Spanish Latte', 2]], 870]
];

for (const [question, expected, total] of cases) {
  const items = parseMenuItems(question);
  const actual = items.map(item => [item.name, item.quantity]);
  if (JSON.stringify(actual) !== JSON.stringify(expected)) {
    throw new Error(`${question}\nExpected ${JSON.stringify(expected)}\nReceived ${JSON.stringify(actual)}`);
  }
  const reply = orderIntentAnswer(question);
  if (!reply?.content.includes(`Estimated total: ₱${total}`) || reply.suggestions?.[0]?.items?.length !== expected.length) {
    throw new Error(`Order summary failed for: ${question}`);
  }
}

console.log(`Passed ${cases.length} multi-item order checks.`);
